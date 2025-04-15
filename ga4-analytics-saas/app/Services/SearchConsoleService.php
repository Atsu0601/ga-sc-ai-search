<?php
// app/Services/SearchConsoleService.php

namespace App\Services;

use App\Models\SearchConsoleAccount;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Illuminate\Support\Facades\Log;
use Exception;

class SearchConsoleService
{
    /**
     * Search Consoleからデータを取得
     */
    public function fetchData(SearchConsoleAccount $account, Carbon $startDate, Carbon $endDate)
    {
        try {
            // クライアントの設定を確認
            if (!$account || !$account->access_token) {
                Log::error('Search Console アカウント情報が不正です', [
                    'account' => $account
                ]);
                return null;
            }

            $client = new GoogleClient();

            // 認証情報の設定
            $client->setAccessToken([
                'access_token' => $account->access_token,
                'refresh_token' => $account->refresh_token
            ]);

            // トークンの有効性確認
            if ($client->isAccessTokenExpired()) {
                Log::info('アクセストークンの更新を試みます');
                try {
                    $client->fetchAccessTokenWithRefreshToken($account->refresh_token);
                    // 新しいトークンを保存
                    $account->access_token = $client->getAccessToken()['access_token'];
                    $account->save();
                } catch (\Exception $e) {
                    Log::error('トークン更新に失敗しました', [
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            }

            // Search Console APIの初期化とデータ取得
            $service = new \Google\Service\SearchConsole($client);

            // キーワードデータの取得
            $keywordsData = $this->fetchKeywordsData($service, $account->site_url, $startDate, $endDate);

            // クリック/インプレッションデータの取得
            $clickImpressionsData = $this->fetchClickImpressionsData($service, $account->site_url, $startDate, $endDate);

            // ページパフォーマンスデータの取得
            $pagePerformanceData = $this->fetchPagePerformanceData($service, $account->site_url, $startDate, $endDate);

            // 最終同期日時を更新
            $account->last_synced_at = now();
            $account->save();

            return [
                'top_keywords' => $keywordsData,
                'click_impressions' => $clickImpressionsData,
                'page_performance' => $pagePerformanceData
            ];
        } catch (\Exception $e) {
            Log::error('Search Consoleデータ取得エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * キーワードデータを取得
     */
    private function fetchKeywordsData($searchConsole, $siteUrl, $startDate, $endDate)
    {
        $request = new SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate->format('Y-m-d'));
        $request->setEndDate($endDate->format('Y-m-d'));
        $request->setDimensions(['query']);
        $request->setRowLimit(10);
        $request->setSearchType('web');

        $response = $searchConsole->searchanalytics->query($siteUrl, $request);
        $rows = $response->getRows() ?? [];

        return array_map(function ($row) {
            return [
                $row->getKeys()[0], // クエリ（キーワード）
                $row->getClicks(), // クリック数
                $row->getImpressions(), // インプレッション数
                number_format($row->getCtr() * 100, 1) . '%', // CTR
                number_format($row->getPosition(), 1) // 平均順位
            ];
        }, $rows);
    }

    /**
     * 日別のクリック/インプレッションデータを取得
     */
    private function fetchClickImpressionsData($searchConsole, $siteUrl, $startDate, $endDate)
    {
        $request = new SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate->format('Y-m-d'));
        $request->setEndDate($endDate->format('Y-m-d'));
        $request->setDimensions(['date']);
        $request->setSearchType('web');

        $response = $searchConsole->searchanalytics->query($siteUrl, $request);
        $rows = $response->getRows() ?? [];

        return array_map(function ($row) {
            return [
                'date' => $row->getKeys()[0],
                'clicks' => $row->getClicks(),
                'impressions' => $row->getImpressions(),
                'ctr' => $row->getCtr(),
                'position' => $row->getPosition()
            ];
        }, $rows);
    }

    /**
     * ページパフォーマンスデータを取得
     */
    private function fetchPagePerformanceData($searchConsole, $siteUrl, $startDate, $endDate)
    {
        $request = new SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate->format('Y-m-d'));
        $request->setEndDate($endDate->format('Y-m-d'));
        $request->setDimensions(['page']);
        $request->setRowLimit(10);
        $request->setSearchType('web');

        $response = $searchConsole->searchanalytics->query($siteUrl, $request);
        $rows = $response->getRows() ?? [];

        return array_map(function ($row) {
            return [
                'page' => $row->getKeys()[0],
                'clicks' => $row->getClicks(),
                'impressions' => $row->getImpressions(),
                'ctr' => $row->getCtr(),
                'position' => $row->getPosition()
            ];
        }, $rows);
    }
}
