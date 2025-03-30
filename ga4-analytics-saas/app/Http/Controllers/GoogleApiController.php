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

        // GA4のスコープ
        $scope = urlencode('https://www.googleapis.com/auth/analytics.readonly');

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

        // GA4アカウントを保存（既存の場合は更新）
        $analytics = $website->analyticsAccount ?? new AnalyticsAccount();
        $analytics->website_id = $website->id;
        $analytics->property_id = 'GA4_PROPERTY_ID'; // 実際はAPI呼び出しで取得
        $analytics->access_token = $tokenData['access_token'];
        $analytics->refresh_token = $tokenData['refresh_token'] ?? '';
        $analytics->save();

        // ウェブサイトのステータスを更新
        if ($website->searchConsoleAccount) {
            $website->status = 'active';
            $website->save();
        }

        return redirect()->route('websites.show', $website->id)
                         ->with('success', 'Google Analytics連携が完了しました。');
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

        return redirect()->route('websites.show', $website->id)
                         ->with('success', 'Search Console連携が完了しました。');
    }
}
