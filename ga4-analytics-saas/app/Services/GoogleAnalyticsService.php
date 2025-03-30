<?php
// app/Services/GoogleAnalyticsService.php

namespace App\Services;

use App\Models\AnalyticsAccount;
use Carbon\Carbon;
use Google_Client;
use Google_Service_AnalyticsData;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleAnalyticsService
{
    /**
     * GA4からデータを取得
     */
    public function fetchData(AnalyticsAccount $account, Carbon $startDate, Carbon $endDate)
    {
        try {
            // Google Clientの初期化
            $client = new Google_Client();
            $client->setAuthConfig(config('services.google.client_secret_path'));
            $client->addScope(Google_Service_AnalyticsData::ANALYTICS_READONLY);

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

            // Analytics Data APIサービスの初期化
            $analytics = new Google_Service_AnalyticsData($client);

            // データ取得処理（実際のAPIリクエストはGA4 APIに依存）

            // 最終同期日時を更新
            $account->last_synced_at = now();
            $account->save();

            // ダミーデータを返す（開発用）
            return [
                'traffic_overview' => $this->getDummyTrafficData($startDate, $endDate),
                'pageview_heatmap' => $this->getDummyHeatmapData(),
                // その他のデータ...
            ];
        } catch (Exception $e) {
            Log::error('GA4データ取得エラー', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 開発用ダミートラフィックデータ
     */
    private function getDummyTrafficData($startDate, $endDate)
    {
        $data = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'users' => rand(100, 1000),
                'sessions' => rand(120, 1200),
                'pageviews' => rand(300, 3000),
                'bounce_rate' => rand(30, 70) / 100
            ];

            $current->addDay();
        }

        return $data;
    }

    /**
     * 開発用ダミーヒートマップデータ
     */
    private function getDummyHeatmapData()
    {
        $data = [];

        for ($hour = 0; $hour < 24; $hour++) {
            for ($day = 0; $day < 7; $day++) {
                $data[] = [
                    'day' => $day,
                    'hour' => $hour,
                    'value' => rand(1, 100)
                ];
            }
        }

        return $data;
    }
}
