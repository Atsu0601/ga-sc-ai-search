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
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
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

            // ユーザーエンゲージメントデータを取得
            $engagementData = $this->fetchEngagementData($analytics, $propertyId, $startDate, $endDate);

            // コンバージョンデータを取得
            $conversionData = $this->fetchConversionData($analytics, $propertyId, $startDate, $endDate);

            // eコマースデータを取得
            $ecommerceData = $this->fetchEcommerceData($analytics, $propertyId, $startDate, $endDate);

            // 地域データを取得
            $locationData = $this->fetchLocationData($analytics, $propertyId, $startDate, $endDate);

            // トレンドデータを取得（日別）
            $trendData = $this->fetchTrendData($analytics, $propertyId, $startDate, $endDate);

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
                    'pages' => $pageData,
                    'engagement' => $engagementData,
                    'keyevents' => $conversionData,
                    'ecommerce' => $ecommerceData,
                    'locations' => $locationData,
                    'trends' => $trendData
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
                new Metric(['name' => 'newUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'engagedSessions']),
                new Metric(['name' => 'engagementRate']),
                new Metric(['name' => 'keyEvents']),
                new Metric(['name' => 'eventCount']),
            ],
        ]);

        $response = $analytics->runReport($request);
        $row = $response->getRows()[0] ?? null;

        return [
            'metrics' => [
                'totalUsers' => (int)($row ? $row->getMetricValues()[0]->getValue() : 0),
                'newUsers' => (int)($row ? $row->getMetricValues()[1]->getValue() : 0),
                'sessions' => (int)($row ? $row->getMetricValues()[2]->getValue() : 0),
                'pageviews' => (int)($row ? $row->getMetricValues()[3]->getValue() : 0),
                'bounceRate' => (float)($row ? $row->getMetricValues()[4]->getValue() : 0),
                'avgSessionDuration' => (float)($row ? $row->getMetricValues()[5]->getValue() : 0),
                'engagedSessions' => (int)($row ? $row->getMetricValues()[6]->getValue() : 0),
                'engagementRate' => (float)($row ? $row->getMetricValues()[7]->getValue() : 0),
                'keyEvents' => (int)($row ? $row->getMetricValues()[8]->getValue() : 0),
                'eventCount' => (int)($row ? $row->getMetricValues()[9]->getValue() : 0),
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
                new Dimension(['name' => 'operatingSystem']),
                new Dimension(['name' => 'operatingSystemVersion']),
                new Dimension(['name' => 'browser']),
                new Dimension(['name' => 'screenResolution']),
                new Dimension(['name' => 'mobileDeviceModel']),
                new Dimension(['name' => 'mobileDeviceBranding']),
            ],
            'metrics' => [
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'newUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'engagementRate']),
            ],
            'limit' => 20,
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'deviceCategory' => $row->getDimensionValues()[0]->getValue(),
                'operatingSystem' => $row->getDimensionValues()[1]->getValue(),
                'operatingSystemVersion' => $row->getDimensionValues()[2]->getValue(),
                'browser' => $row->getDimensionValues()[3]->getValue(),
                'screenResolution' => $row->getDimensionValues()[4]->getValue(),
                'mobileDeviceModel' => $row->getDimensionValues()[5]->getValue(),
                'mobileDeviceBranding' => $row->getDimensionValues()[6]->getValue(),
                'users' => (int)$row->getMetricValues()[0]->getValue(),
                'newUsers' => (int)$row->getMetricValues()[1]->getValue(),
                'sessions' => (int)$row->getMetricValues()[2]->getValue(),
                'pageviews' => (int)$row->getMetricValues()[3]->getValue(),
                'bounceRate' => (float)$row->getMetricValues()[4]->getValue(),
                'avgSessionDuration' => (float)$row->getMetricValues()[5]->getValue(),
                'engagementRate' => (float)$row->getMetricValues()[6]->getValue(),
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
                new Dimension(['name' => 'sessionMedium']),
                new Dimension(['name' => 'sessionCampaignName']),
                new Dimension(['name' => 'sessionSourcePlatform']),
            ],
            'metrics' => [
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'newUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'keyEvents']),
                new Metric(['name' => 'engagementRate']),
                new Metric(['name' => 'advertiserAdCost']),
                new Metric(['name' => 'advertiserAdClicks']),
                new Metric(['name' => 'advertiserAdImpressions']),
                new Metric(['name' => 'advertiserAdCostPerClick']),
            ],
            'limit' => 20,
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'source' => $row->getDimensionValues()[0]->getValue(),
                'medium' => $row->getDimensionValues()[1]->getValue(),
                'campaign' => $row->getDimensionValues()[2]->getValue(),
                'sourcePlatform' => $row->getDimensionValues()[3]->getValue(),
                'users' => (int)$row->getMetricValues()[0]->getValue(),
                'newUsers' => (int)$row->getMetricValues()[1]->getValue(),
                'sessions' => (int)$row->getMetricValues()[2]->getValue(),
                'pageviews' => (int)$row->getMetricValues()[3]->getValue(),
                'keyEvents' => (int)$row->getMetricValues()[4]->getValue(),
                'engagementRate' => (float)$row->getMetricValues()[5]->getValue(),
                'adCost' => (float)$row->getMetricValues()[6]->getValue(),
                'adClicks' => (int)$row->getMetricValues()[7]->getValue(),
                'adImpressions' => (int)$row->getMetricValues()[8]->getValue(),
                'adCostPerClick' => (float)$row->getMetricValues()[9]->getValue(),
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
                new Dimension(['name' => 'pagePathPlusQueryString']),
                new Dimension(['name' => 'landingPage']),
                new Dimension(['name' => 'pageReferrer']),
            ],
            'metrics' => [
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'userEngagementDuration']),
                new Metric(['name' => 'engagedSessions']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'keyEvents'])
            ],
            'limit' => 20,
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'pagePath' => $row->getDimensionValues()[0]->getValue(),
                'pageTitle' => $row->getDimensionValues()[1]->getValue(),
                'hostName' => $row->getDimensionValues()[2]->getValue(),
                'landingPage' => $row->getDimensionValues()[3]->getValue(),
                'pageReferrer' => $row->getDimensionValues()[4]->getValue(),
                'pageviews' => (int)$row->getMetricValues()[0]->getValue(),
                'engagementDuration' => (float)$row->getMetricValues()[1]->getValue(),
                'engagedSessions' => (int)$row->getMetricValues()[2]->getValue(),
                'bounceRate' => (float)$row->getMetricValues()[3]->getValue(),
                'users' => (int)$row->getMetricValues()[4]->getValue(),
                'sessions' => (int)$row->getMetricValues()[5]->getValue(),
                'avgSessionDuration' => (float)$row->getMetricValues()[6]->getValue(),
                'keyEvents' => (int)$row->getMetricValues()[7]->getValue(),
            ];
        }, iterator_to_array($rows));
    }

    /**
     * ユーザーエンゲージメントデータを取得
     */
    private function fetchEngagementData($analytics, $propertyId, $startDate, $endDate)
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
                new Dimension(['name' => 'date']),
                new Dimension(['name' => 'landingPage']),
                new Dimension(['name' => 'searchTerm']),
            ],
            'metrics' => [
                new Metric(['name' => 'engagedSessions']),
                new Metric(['name' => 'engagementRate']),
                new Metric(['name' => 'userEngagementDuration']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'eventCount']),
                new Metric(['name' => 'eventsPerSession']),
                new Metric(['name' => 'screenPageViewsPerSession']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'sessionsPerUser']),
            ],
            'limit' => 20,
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'date' => $row->getDimensionValues()[0]->getValue(),
                'landingPage' => $row->getDimensionValues()[1]->getValue(),
                'searchTerm' => $row->getDimensionValues()[1]->getValue(),
                'engagedSessions' => (int)$row->getMetricValues()[0]->getValue(),
                'engagementRate' => (float)$row->getMetricValues()[1]->getValue(),
                'userEngagementDuration' => (float)$row->getMetricValues()[2]->getValue(),
                'avgSessionDuration' => (float)$row->getMetricValues()[3]->getValue(),
                'eventCount' => (int)$row->getMetricValues()[4]->getValue(),
                'eventsPerSession' => (float)$row->getMetricValues()[5]->getValue(),
                'pageViewsPerSession' => (float)$row->getMetricValues()[6]->getValue(),
                'sessions' => (int)$row->getMetricValues()[7]->getValue(),
                'sessionsPerUser' => (float)$row->getMetricValues()[8]->getValue(),
            ];
        }, iterator_to_array($rows));
    }

    /**
     * コンバージョンデータを取得
     */
    private function fetchConversionData($analytics, $propertyId, $startDate, $endDate)
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
                new Dimension(['name' => 'date']),
                new Dimension(['name' => 'eventName']),
            ],
            'metrics' => [
                new Metric(['name' => 'keyEvents']),
                new Metric(['name' => 'eventValue']),
                new Metric(['name' => 'eventCount']),
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'sessions']),
            ],
            'limit' => 20,
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'date' => $row->getDimensionValues()[0]->getValue(),
                'eventName' => $row->getDimensionValues()[1]->getValue(),
                'keyEvents' => (int)$row->getMetricValues()[0]->getValue(),
                'eventValue' => (float)$row->getMetricValues()[1]->getValue(),
                'eventCount' => (int)$row->getMetricValues()[2]->getValue(),
                'users' => (int)$row->getMetricValues()[3]->getValue(),
                'sessions' => (int)$row->getMetricValues()[4]->getValue(),
            ];
        }, iterator_to_array($rows));
    }

    /**
     * eコマースデータを取得
     */
    private function fetchEcommerceData($analytics, $propertyId, $startDate, $endDate)
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
                new Dimension(['name' => 'itemName']),
                new Dimension(['name' => 'itemId']),
                new Dimension(['name' => 'itemCategory']),
                new Dimension(['name' => 'itemBrand']),
                new Dimension(['name' => 'transactionId']),
            ],
            'metrics' => [
                new Metric(['name' => 'itemViewEvents']),
                new Metric(['name' => 'itemRevenue']),
                new Metric(['name' => 'purchaseToViewRate']),
                new Metric(['name' => 'cartToViewRate']),
                new Metric(['name' => 'ecommercePurchases']),
                new Metric(['name' => 'transactions']),
                new Metric(['name' => 'totalRevenue']),
                new Metric(['name' => 'averagePurchaseRevenue']),
            ],
            'limit' => 20,
        ]);

        try {
            $response = $analytics->runReport($request);
            $rows = $response->getRows();

            // RepeatedFieldを配列に変換
            return array_map(function ($row) {
                return [
                    'itemName' => $row->getDimensionValues()[0]->getValue(),
                    'itemId' => $row->getDimensionValues()[1]->getValue(),
                    'itemCategory' => $row->getDimensionValues()[2]->getValue(),
                    'itemBrand' => $row->getDimensionValues()[3]->getValue(),
                    'transactionId' => $row->getDimensionValues()[4]->getValue(),
                    'itemViewEvents' => (int)$row->getMetricValues()[0]->getValue(),
                    'itemRevenue' => (float)$row->getMetricValues()[1]->getValue(),
                    'purchaseToViewRate' => (float)$row->getMetricValues()[2]->getValue(),
                    'cartToViewRate' => (float)$row->getMetricValues()[3]->getValue(),
                    'ecommercePurchases' => (int)$row->getMetricValues()[4]->getValue(),
                    'transactions' => (int)$row->getMetricValues()[5]->getValue(),
                    'totalRevenue' => (float)$row->getMetricValues()[6]->getValue(),
                    'averagePurchaseRevenue' => (float)$row->getMetricValues()[7]->getValue(),
                ];
            }, iterator_to_array($rows));
        } catch (Exception $e) {
            // eコマースの設定がない場合は空の配列を返す
            Log::warning('eコマースデータ取得エラー', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 地域データを取得
     */
    private function fetchLocationData($analytics, $propertyId, $startDate, $endDate)
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
                new Dimension(['name' => 'country']),
                new Dimension(['name' => 'region']),
                new Dimension(['name' => 'city']),
                new Dimension(['name' => 'language']),
            ],
            'metrics' => [
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'newUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'keyEvents']),
            ],
            'limit' => 20,
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'country' => $row->getDimensionValues()[0]->getValue(),
                'region' => $row->getDimensionValues()[1]->getValue(),
                'city' => $row->getDimensionValues()[2]->getValue(),
                'language' => $row->getDimensionValues()[3]->getValue(),
                'users' => (int)$row->getMetricValues()[0]->getValue(),
                'newUsers' => (int)$row->getMetricValues()[1]->getValue(),
                'sessions' => (int)$row->getMetricValues()[2]->getValue(),
                'pageviews' => (int)$row->getMetricValues()[3]->getValue(),
                'bounceRate' => (float)$row->getMetricValues()[4]->getValue(),
                'avgSessionDuration' => (float)$row->getMetricValues()[5]->getValue(),
                'keyEvents' => (int)$row->getMetricValues()[6]->getValue(),
            ];
        }, iterator_to_array($rows));
    }

    /**
     * トレンドデータを取得（日別）
     */
    private function fetchTrendData($analytics, $propertyId, $startDate, $endDate)
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
                new Dimension(['name' => 'date']),
                new Dimension(['name' => 'sessionCampaignName']),
                new Dimension(['name' => 'sessionSource']),
                new Dimension(['name' => 'sessionMedium']),
            ],
            'metrics' => [
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'newUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'userEngagementDuration']),
                new Metric(['name' => 'conversions']),
                new Metric(['name' => 'advertiserAdCost']),
                new Metric(['name' => 'advertiserAdClicks']),
                new Metric(['name' => 'advertiserAdImpressions']),
            ],
        ]);

        $response = $analytics->runReport($request);
        $rows = $response->getRows();

        // RepeatedFieldを配列に変換
        return array_map(function ($row) {
            return [
                'date' => $row->getDimensionValues()[0]->getValue(),
                'campaignName' => $row->getDimensionValues()[1]->getValue(),
                'source' => $row->getDimensionValues()[2]->getValue(),
                'medium' => $row->getDimensionValues()[3]->getValue(),
                'users' => (int)$row->getMetricValues()[0]->getValue(),
                'newUsers' => (int)$row->getMetricValues()[1]->getValue(),
                'sessions' => (int)$row->getMetricValues()[2]->getValue(),
                'pageviews' => (int)$row->getMetricValues()[3]->getValue(),
                'bounceRate' => (float)$row->getMetricValues()[4]->getValue(),
                'engagementDuration' => (float)$row->getMetricValues()[5]->getValue(),
                'conversions' => (int)$row->getMetricValues()[6]->getValue(),
                'adCost' => (float)$row->getMetricValues()[7]->getValue(),
                'adClicks' => (int)$row->getMetricValues()[8]->getValue(),
                'adImpressions' => (int)$row->getMetricValues()[9]->getValue(),
            ];
        }, iterator_to_array($rows));
    }
}
