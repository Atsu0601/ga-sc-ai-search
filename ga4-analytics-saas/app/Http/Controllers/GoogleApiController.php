<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\AnalyticsAccount;
use App\Models\SearchConsoleAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class GoogleApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * GA4認証のリダイレクトURL
     */
    public function redirectToGoogleAnalytics(Website $website)
    {
        // この関数はWebサイトオーナーのみアクセス可能
        $this->authorize('update', $website);

        // Google OAuth2.0クライアントIDとシークレットを環境変数から取得
        $clientId = config('services.google.client_id');
        $redirectUri = route('google.analytics.callback');

        // セッションにウェブサイトIDを保存（コールバックで使用）
        session(['website_id' => $website->id]);

        // GA4のスコープ（Analytics Admin APIのスコープを追加）
        $scopes = [
            'https://www.googleapis.com/auth/analytics.readonly',
            'https://www.googleapis.com/auth/analytics.edit'
        ];
        $scope = urlencode(implode(' ', $scopes));

        // OAuth2.0認証URLを生成
        $url = "https://accounts.google.com/o/oauth2/auth?";
        $url .= "client_id={$clientId}";
        $url .= "&redirect_uri=" . urlencode($redirectUri);
        $url .= "&scope={$scope}";
        $url .= "&response_type=code";
        $url .= "&access_type=offline";
        $url .= "&prompt=consent";
        $url .= "&state=" . Str::random(40);

        return redirect($url);
    }

    /**
     * GA4コールバック処理
     */
    public function handleGoogleAnalyticsCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('websites.index')
                ->with('error', 'Google Analytics認証がキャンセルされました。');
        }

        $code = $request->code;
        $websiteId = session('website_id');

        if (!$code || !$websiteId) {
            return redirect()->route('websites.index')
                ->with('error', '認証情報が不足しています。');
        }

        $website = Website::findOrFail($websiteId);

        // この関数はWebサイトオーナーのみアクセス可能
        $this->authorize('update', $website);

        // アクセストークンを取得
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = route('google.analytics.callback');

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            return redirect()->route('websites.show', $website->id)
                ->with('error', 'アクセストークンの取得に失敗しました。');
        }

        $tokenData = $response->json();

        // 利用可能なプロパティを取得
        $properties = $this->getAvailableGA4Properties($tokenData['access_token']);

        // デバッグ情報をログに記録
        Log::info('GA4 properties fetched', [
            'properties_count' => count($properties),
            'properties' => $properties,
            'website_id' => $website->id
        ]);

        if (empty($properties)) {
            return redirect()->route('websites.show', $website->id)
                ->with('error', '利用可能なGA4プロパティが見つかりませんでした。');
        }

        // プロパティ選択画面を表示
        return view('websites.select-ga4-property', [
            'website' => $website,
            'properties' => $properties,
            'accessToken' => $tokenData['access_token'],
            'refreshToken' => $tokenData['refresh_token'] ?? ''
        ]);
    }

    /**
     * GA4プロパティの選択を処理
     */
    public function handlePropertySelection(Request $request, Website $website)
    {
        $this->authorize('update', $website);

        $request->validate([
            'property_id' => 'required|string',
            'access_token' => 'required|string',
            'refresh_token' => 'required|string',
        ]);

        try {
            // GA4アカウントを保存（既存の場合は更新）
            $analytics = $website->analyticsAccount ?? new AnalyticsAccount();
            $analytics->website_id = $website->id;
            $analytics->property_id = $request->property_id;
            $analytics->access_token = $request->access_token;
            $analytics->refresh_token = $request->refresh_token;
            $analytics->last_synced_at = now(); // 最終同期日時を更新
            $analytics->save();

            // ウェブサイトのステータスを更新
            if ($website->searchConsoleAccount) {
                $website->status = 'active';
                $website->save();
            }

            Log::info('Google Analytics連携完了', [
                'website_id' => $website->id,
                'property_id' => $request->property_id,
                'last_synced_at' => $analytics->last_synced_at
            ]);

            return redirect()->route('websites.show', $website->id)
                ->with('success', 'Google Analytics連携が完了しました。');
        } catch (\Exception $e) {
            Log::error('Google Analytics連携エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'website_id' => $website->id,
                'property_id' => $request->property_id
            ]);

            return redirect()->route('websites.show', $website->id)
                ->with('error', 'Google Analytics連携中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 利用可能なGA4プロパティを取得
     */
    private function getAvailableGA4Properties(string $accessToken): array
    {
        try {
            $properties = [];

            // Analytics Admin APIを使用してアカウントリストを取得
            Log::info('Fetching GA4 accounts', ['access_token' => substr($accessToken, 0, 10) . '...']);

            $accountResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get('https://analyticsadmin.googleapis.com/v1beta/accounts');

            if (!$accountResponse->successful()) {
                Log::error('GA4 accounts fetch failed', [
                    'status' => $accountResponse->status(),
                    'body' => $accountResponse->body()
                ]);
                return [];
            }

            $accounts = $accountResponse->json()['accounts'] ?? [];
            Log::info('GA4 accounts fetched', ['count' => count($accounts)]);

            // 各アカウントのプロパティを取得
            foreach ($accounts as $account) {
                $accountId = str_replace('accounts/', '', $account['name']);
                Log::info('Fetching properties for account', ['account_id' => $accountId]);

                $propertyResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken
                ])->get("https://analyticsadmin.googleapis.com/v1beta/properties", [
                    'filter' => "parent:accounts/{$accountId}"
                ]);

                if ($propertyResponse->successful()) {
                    $accountProperties = $propertyResponse->json()['properties'] ?? [];
                    Log::info('Properties fetched for account', [
                        'account_id' => $accountId,
                        'properties_count' => count($accountProperties)
                    ]);

                    foreach ($accountProperties as $property) {
                        $properties[] = [
                            'id' => str_replace('properties/', '', $property['name']),
                            'displayName' => $property['displayName'] ?? '',
                            'websiteUrl' => $property['websiteUrl'] ?? null,
                            'createTime' => $property['createTime'] ?? null,
                            'account' => [
                                'id' => $accountId,
                                'name' => $account['displayName'] ?? ''
                            ]
                        ];
                    }
                } else {
                    Log::error('Failed to fetch properties for account', [
                        'account_id' => $accountId,
                        'status' => $propertyResponse->status(),
                        'body' => $propertyResponse->body()
                    ]);
                }
            }

            Log::info('All GA4 properties fetched', ['total_properties' => count($properties)]);
            return $properties;
        } catch (\Exception $e) {
            Log::error('Error fetching GA4 properties', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Search Console認証のリダイレクトURL
     */
    public function redirectToSearchConsole(Website $website)
    {
        // この関数はWebサイトオーナーのみアクセス可能
        $this->authorize('update', $website);

        // Google OAuth2.0クライアントIDとシークレットを環境変数から取得
        $clientId = config('services.google.client_id');
        $redirectUri = route('google.searchconsole.callback');

        // セッションにウェブサイトIDを保存（コールバックで使用）
        session(['website_id' => $website->id]);

        // Search Consoleのスコープ
        $scope = urlencode('https://www.googleapis.com/auth/webmasters.readonly');

        // OAuth2.0認証URLを生成
        $url = "https://accounts.google.com/o/oauth2/auth?";
        $url .= "client_id={$clientId}";
        $url .= "&redirect_uri=" . urlencode($redirectUri);
        $url .= "&scope={$scope}";
        $url .= "&response_type=code";
        $url .= "&access_type=offline";
        $url .= "&prompt=consent";
        $url .= "&state=" . Str::random(40);

        return redirect($url);
    }

    /**
     * Search Consoleコールバック処理
     */
    public function handleSearchConsoleCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('websites.index')
                ->with('error', 'Search Console認証がキャンセルされました。');
        }

        $code = $request->code;
        $websiteId = session('website_id');

        if (!$code || !$websiteId) {
            return redirect()->route('websites.index')
                ->with('error', '認証情報が不足しています。');
        }

        $website = Website::findOrFail($websiteId);

        // この関数はWebサイトオーナーのみアクセス可能
        $this->authorize('update', $website);

        // アクセストークンを取得
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = route('google.searchconsole.callback');

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            return redirect()->route('websites.show', $website->id)
                ->with('error', 'アクセストークンの取得に失敗しました。');
        }

        $tokenData = $response->json();

        // Search Consoleアカウントを保存（既存の場合は更新）
        $searchConsole = $website->searchConsoleAccount ?? new SearchConsoleAccount();
        $searchConsole->website_id = $website->id;
        $searchConsole->site_url = $website->url;
        $searchConsole->access_token = $tokenData['access_token'];
        $searchConsole->refresh_token = $tokenData['refresh_token'] ?? '';
        $searchConsole->save();

        // ウェブサイトのステータスを更新
        if ($website->analyticsAccount) {
            $website->status = 'active';
            $website->save();
        }

        // 利用可能なSearch Consoleプロパティを取得
        $properties = $this->getAvailableSearchConsoleProperties($tokenData['access_token']);

        // プロパティ選択画面を表示
        return view('websites.select-search-console-property', [
            'website' => $website,
            'properties' => $properties,
            'accessToken' => $tokenData['access_token'],
            'refreshToken' => $tokenData['refresh_token'] ?? ''
        ]);
    }

    /**
     * Search Console接続を解除
     *
     * @param Website $website
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnectSearchConsole(Website $website)
    {
        $this->authorize('update', $website);

        if ($website->searchConsoleAccount) {
            $website->searchConsoleAccount->delete();
        }

        return redirect()->route('websites.show', $website->id)
            ->with('success', 'Search Consoleの接続を解除しました。');
    }

    /**
     * 利用可能なSearch Consoleプロパティを取得
     */
    private function getAvailableSearchConsoleProperties(string $accessToken): array
    {
        try {
            // Search Console APIを使用してサイトリストを取得
            Log::info('Fetching Search Console sites', ['access_token' => substr($accessToken, 0, 10) . '...']);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get('https://www.googleapis.com/webmasters/v3/sites');

            if (!$response->successful()) {
                Log::error('Search Console sites fetch failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $sites = $response->json()['siteEntry'] ?? [];

            $properties = [];
            foreach ($sites as $site) {
                $properties[] = [
                    'siteUrl' => $site['siteUrl'],
                    'permissionLevel' => $site['permissionLevel'] ?? 'unknown'
                ];
            }

            Log::info('Search Console properties fetched', ['count' => count($properties)]);
            return $properties;
        } catch (\Exception $e) {
            Log::error('Error fetching Search Console properties', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Search Consoleプロパティの選択を処理
     */
    public function handleSearchConsolePropertySelection(Request $request, Website $website)
    {
        $this->authorize('update', $website);

        $request->validate([
            'site_url' => 'required|string',
            'access_token' => 'required|string',
            'refresh_token' => 'required|string',
        ]);

        try {
            // Search Consoleから実データを取得
            $endDate = now();
            $startDate = $endDate->copy()->subDays(30);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $request->access_token
            ])->post('https://www.googleapis.com/webmasters/v3/sites/' . urlencode($request->site_url) . '/searchAnalytics/query', [
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
                'dimensions' => ['query', 'page'],
                'rowLimit' => 1000
            ]);

            if (!$response->successful()) {
                Log::error('Search Consoleデータ取得エラー', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'site_url' => $request->site_url
                ]);
                throw new \Exception('Search Consoleからのデータ取得に失敗しました');
            }

            // Search Consoleアカウントを保存（既存の場合は更新）
            $searchConsole = $website->searchConsoleAccount ?? new SearchConsoleAccount();
            $searchConsole->website_id = $website->id;
            $searchConsole->site_url = $request->site_url;
            $searchConsole->access_token = $request->access_token;
            $searchConsole->refresh_token = $request->refresh_token;
            $searchConsole->last_synced_at = now(); // 最終同期日時を更新
            $searchConsole->save();

            // ウェブサイトのステータスを更新
            if ($website->analyticsAccount) {
                $website->status = 'active';
                $website->save();
            }

            Log::info('Search Console連携完了', [
                'website_id' => $website->id,
                'site_url' => $request->site_url,
                'last_synced_at' => $searchConsole->last_synced_at
            ]);

            return redirect()->route('websites.show', $website->id)
                ->with('success', 'Search Console連携が完了し、データの同期が完了しました。');
        } catch (\Exception $e) {
            Log::error('Search Console連携エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'website_id' => $website->id,
                'site_url' => $request->site_url
            ]);

            return redirect()->route('websites.show', $website->id)
                ->with('error', 'Search Console連携中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * Google Analytics接続を解除
     *
     * @param Website $website
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnectAnalytics(Website $website)
    {
        $this->authorize('update', $website);

        if ($website->analyticsAccount) {
            $website->analyticsAccount->delete();

            // ウェブサイトのステータスを更新
            if (!$website->searchConsoleAccount) {
                $website->status = 'inactive';
                $website->save();
            }

            Log::info('Google Analytics接続を解除しました', [
                'website_id' => $website->id
            ]);
        }

        return redirect()->route('websites.show', $website->id)
            ->with('success', 'Google Analyticsの接続を解除しました。');
    }
}
