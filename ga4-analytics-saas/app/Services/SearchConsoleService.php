<?php
// app/Services/SearchConsoleService.php

namespace App\Services;

use App\Models\SearchConsoleAccount;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Webmasters;
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
            // Google Clientの初期化
            $client = new Google_Client();
            $client->setAuthConfig(config('services.google.client_secret_path'));
            $client->addScope(Google_Service_Webmasters::WEBMASTERS_READONLY);

            // アクセストークンの設定
            $client->setAccessToken($account->access_token);

            // トークンが期限切れの場合、リフレッシュトークンを使用して更新
            if ($client->isAccessTokenExpired() && $account->refresh_token) {
                $client->fetchAccessTokenWithRefreshToken($account->refresh_token);
                $tokens = $client->getAccessToken();

                // 新しいトークンを保存
                $account->access_token = $tokens['access_token'];
                if (isset($tokens['refresh_token'])) {
                    $account->refresh_token = $tokens['refresh_token'];
                }
                $account->save();
            }

            // Search Console APIサービスの初期化
            $searchConsole = new Google_Service_Webmasters($client);

            // データ取得処理（実際のAPIリクエストはSearch Console APIに依存）

            // 最終同期日時を更新
            $account->last_synced_at = now();
            $account->save();

            // ダミーデータを返す（開発用）
            return [
                'top_keywords' => $this->getDummyKeywordsData(),
                'click_impressions' => $this->getDummyClickImpressionsData($startDate, $endDate),
                'page_performance' => $this->getDummyPagePerformanceData(),
                // その他のデータ...
            ];
        } catch (Exception $e) {
            Log::error('Search Consoleデータ取得エラー', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 開発用ダミーキーワードデータ
     */
    private function getDummyKeywordsData()
    {
        $keywords = [
            ['GA4 分析ツール', rand(50, 200), rand(1000, 3000), rand(2, 8) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['Search Console 連携', rand(30, 150), rand(800, 2500), rand(2, 7) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['AI レポート 生成', rand(20, 100), rand(500, 2000), rand(2, 6) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['ウェブ アクセス 解析', rand(40, 180), rand(900, 2800), rand(3, 9) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['ウェブサイト パフォーマンス', rand(25, 120), rand(600, 2200), rand(2, 7) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['SEO 改善 ツール', rand(35, 160), rand(850, 2600), rand(3, 8) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['Google データ 分析', rand(45, 190), rand(950, 2900), rand(3, 8) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['アクセス 解析 レポート', rand(15, 90), rand(400, 1800), rand(2, 6) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['ウェブマーケティング ツール', rand(30, 140), rand(750, 2400), rand(3, 7) . '%', rand(1, 10) . '.' . rand(0, 9)],
            ['サイト 分析 AI', rand(10, 80), rand(300, 1500), rand(2, 5) . '%', rand(1, 10) . '.' . rand(0, 9)],
        ];

        return $keywords;
    }

    /**
     * 開発用ダミークリック/インプレッションデータ
     */
    private function getDummyClickImpressionsData($startDate, $endDate)
    {
        $data = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'clicks' => rand(50, 500),
                'impressions' => rand(1000, 5000),
                'ctr' => rand(1, 10) / 100,
                'position' => rand(1, 20) + (rand(0, 99) / 100)
            ];

            $current->addDay();
        }

        return $data;
    }

    /**
     * 開発用ダミーページパフォーマンスデータ
     */
    private function getDummyPagePerformanceData()
    {
        $pages = [
            '/home', '/about', '/services', '/products', '/blog',
            '/contact', '/pricing', '/resources', '/faq', '/support'
        ];

        $data = [];

        foreach ($pages as $page) {
            $data[] = [
                'page' => $page,
                'clicks' => rand(10, 200),
                'impressions' => rand(100, 2000),
                'ctr' => rand(1, 15) / 100,
                'position' => rand(1, 20) + (rand(0, 99) / 100)
            ];
        }

        return $data;
    }
}
