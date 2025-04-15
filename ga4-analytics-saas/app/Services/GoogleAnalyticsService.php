<?php
// app/Services/GoogleAnalyticsService.php

namespace App\Services;

use App\Models\AnalyticsAccount;
use Carbon\Carbon;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
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
            if (empty($account->access_token)) {
                throw new \Exception('アクセストークンが設定されていません');
            }

            // サービスアカウントの認証情報を読み込む
            $keyFilePath = storage_path('app/google/service-account-credentials.json');
            if (!file_exists($keyFilePath)) {
                throw new \Exception('サービスアカウントの認証情報ファイルが見つかりません');
            }

            // Analytics Data APIクライアントの初期化
            $analytics = new BetaAnalyticsDataClient([
                'credentials' => $keyFilePath
            ]);

            // プロパティIDの確認と整形
            $propertyId = $this->formatPropertyId($account->property_id);

            // 基本的なメトリクスを取得
            $basicMetrics = $this->fetchBasicMetrics($analytics, $propertyId, $startDate, $endDate);

            // デバイスデータを取得
            $deviceData = $this->fetchDeviceData($analytics, $propertyId, $startDate, $endDate);

            // トラフィックソースデータを取得
            $sourceData = $this->fetchSourceData($analytics, $propertyId, $startDate, $endDate);

            // ページデータを取得
            $pageData = $this->fetchPageData($analytics, $propertyId, $startDate, $endDate);

            // 最終同期日時を更新
            $account->forceFill([
                'last_synced_at' => now()
            ])->save();

            Log::info('GA4データ取得完了', [
                'account_id' => $account->id,
                'last_synced_at' => $account->last_synced_at,
                'metrics' => $basicMetrics['metrics']
            ]);

            return [
                'metrics' => $basicMetrics['metrics'],
                'dimensions' => [
                    'devices' => $deviceData,
                    'sources' => $sourceData,
                    'pages' => $pageData
                ]
            ];
        } catch (Exception $e) {
            Log::error('GA4データ取得エラー', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * プロパティIDのフォーマットを確認
     */
    private function formatPropertyId($propertyId)
    {
        if (empty($propertyId)) {
            throw new \Exception('プロパティIDが設定されていません');
        }
        return str_starts_with($propertyId, 'properties/') ? $propertyId : 'properties/' . $propertyId;
    }

    /**
     * 基本的なメトリクスを取得
     */
    private function fetchBasicMetrics($analytics, $propertyId, $startDate, $endDate)
    {
        $request = new RunReportRequest([
            'property' => $propertyId,
            'date_ranges' => [
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ],
            'metrics' => [
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
            ],
        ]);

        $response = $analytics->runReport($request);
        $row = $response->getRows()[0] ?? null;

        return [
            'metrics' => [
                'users' => (int)($row ? $row->getMetricValues()[0]->getValue() : 0),
                'sessions' => (int)($row ? $row->getMetricValues()[1]->getValue() : 0),
                'pageviews' => (int)($row ? $row->getMetricValues()[2]->getValue() : 0),
                'bounceRate' => (float)($row ? $row->getMetricValues()[3]->getValue() : 0),
                'avgSessionDuration' => (float)($row ? $row->getMetricValues()[4]->getValue() : 0),
            ]
        ];
    }

    /**
     * デバイスデータを取得
     */
    private function fetchDeviceData($analytics, $propertyId, $startDate, $endDate)
    {
        $request = new RunReportRequest([
            'property' => $propertyId,
            'date_ranges' => [
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'deviceCategory']),
            ],
            'metrics' => [
                new Metric(['name' => 'totalUsers']),
            ],
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'device' => $row->getDimensionValues()[0]->getValue(),
                'users' => (int)$row->getMetricValues()[0]->getValue(),
            ];
        }, iterator_to_array($rows));
    }

    /**
     * トラフィックソースデータを取得
     */
    private function fetchSourceData($analytics, $propertyId, $startDate, $endDate)
    {
        $request = new RunReportRequest([
            'property' => $propertyId,
            'date_ranges' => [
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'sessionSource']),
            ],
            'metrics' => [
                new Metric(['name' => 'totalUsers']),
            ],
            'limit' => 5,
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'source' => $row->getDimensionValues()[0]->getValue(),
                'users' => (int)$row->getMetricValues()[0]->getValue(),
            ];
        }, iterator_to_array($rows));
    }

    /**
     * ページデータを取得
     */
    private function fetchPageData($analytics, $propertyId, $startDate, $endDate)
    {
        $request = new RunReportRequest([
            'property' => $propertyId,
            'date_ranges' => [
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'pagePath']),
                new Dimension(['name' => 'pageTitle']),
            ],
            'metrics' => [
                new Metric(['name' => 'screenPageViews']),
            ],
            'limit' => 10,
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'page' => $row->getDimensionValues()[0]->getValue(),
                'title' => $row->getDimensionValues()[1]->getValue(),
                'pageviews' => (int)$row->getMetricValues()[0]->getValue(),
            ];
        }, iterator_to_array($rows));
    }
}
