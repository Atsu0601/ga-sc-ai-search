<?php

namespace App\Services;

use App\Models\DataSnapshot;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\AnalysisReport;

class DataSnapshotService
{
    protected $googleAnalyticsService;
    protected $searchConsoleService;

    /**
     * コンストラクタ
     */
    public function __construct(
        GoogleAnalyticsService $googleAnalyticsService,
        SearchConsoleService $searchConsoleService
    ) {
        $this->googleAnalyticsService = $googleAnalyticsService;
        $this->searchConsoleService = $searchConsoleService;
    }

    /**
     * Webサイトの全てのデータスナップショットを作成
     */
    public function createAllSnapshots(Website $website, Carbon $date = null)
    {
        if (!$date) {
            $date = Carbon::yesterday();
        }

        try {
            $this->createAnalyticsSnapshot($website, $date);
            $this->createSearchConsoleSnapshot($website, $date);

            return true;
        } catch (\Exception $e) {
            Log::error('スナップショット作成エラー: ' . $e->getMessage(), [
                'website_id' => $website->id,
                'date' => $date->format('Y-m-d'),
            ]);

            return false;
        }
    }

    /**
     * Analytics（GA4）のスナップショットを作成
     */
    public function createAnalyticsSnapshot(Website $website, Carbon $date)
    {
        try {
            // API接続がない場合はスキップ
            if (!$website->analyticsAccount) {
                Log::info('Analytics接続が設定されていないため、スナップショット作成をスキップします', [
                    'website_id' => $website->id
                ]);
                return null;
            }

            // 既存のスナップショットを検索
            $existingSnapshot = DataSnapshot::where('website_id', $website->id)
                ->where('snapshot_type', 'analytics')
                ->where('snapshot_date', $date->format('Y-m-d'))
                ->first();

            if ($existingSnapshot) {
                // 既存のデータを新しい構造に変換
                if (!isset($existingSnapshot->data_json['metrics']['users'])) {
                    $existingData = $existingSnapshot->data_json;
                    $newData = $this->getSampleAnalyticsData($date);
                    $existingSnapshot->data_json = $newData;
                    $existingSnapshot->save();

                    Log::info('既存のスナップショットのデータ構造を更新しました', [
                        'website_id' => $website->id,
                        'snapshot_id' => $existingSnapshot->id,
                        'date' => $date->format('Y-m-d')
                    ]);
                }
                return $existingSnapshot;
            }

            // 対象の日のデータを取得
            $startDate = $date->copy()->startOfDay();
            $endDate = $date->copy()->endOfDay();

            Log::info('GA4データの取得を開始します', [
                'website_id' => $website->id,
                'analytics_account_id' => $website->analyticsAccount->id,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ]
            ]);

            // 開発環境ではサンプルデータを使用
            $data = $this->getSampleAnalyticsData($date);

            // スナップショットを保存
            $snapshot = new DataSnapshot();
            $snapshot->website_id = $website->id;
            $snapshot->snapshot_type = 'analytics';
            $snapshot->data_json = $data;
            $snapshot->snapshot_date = $date;
            $snapshot->save();

            Log::info('スナップショットを作成しました', [
                'website_id' => $website->id,
                'snapshot_id' => $snapshot->id,
                'date' => $date->format('Y-m-d'),
                'data_structure' => [
                    'has_metrics' => isset($data['metrics']),
                    'has_dimensions' => isset($data['dimensions']),
                    'metrics_keys' => array_keys($data['metrics']),
                ]
            ]);

            return $snapshot;
        } catch (\Exception $e) {
            Log::error('Analyticsスナップショット作成エラー', [
                'website_id' => $website->id,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('データスナップショットの作成中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * Search Consoleのスナップショットを作成
     */
    public function createSearchConsoleSnapshot(Website $website, Carbon $date)
    {
        // API接続がない場合はスキップ
        if (!$website->searchConsoleAccount) {
            return null;
        }

        // 既存のスナップショットを検索
        $existingSnapshot = DataSnapshot::where('website_id', $website->id)
            ->where('snapshot_type', 'search_console')
            ->where('snapshot_date', $date->format('Y-m-d'))
            ->first();

        if ($existingSnapshot) {
            return $existingSnapshot;
        }

        // 対象の日のデータを取得
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        try {
            // 本番環境ではSearchConsoleServiceから実際のデータを取得
            // 開発用にサンプルデータを使用
            $data = $this->getSampleSearchConsoleData($date);

            // スナップショットを保存
            $snapshot = new DataSnapshot();
            $snapshot->website_id = $website->id;
            $snapshot->snapshot_type = 'search_console';
            $snapshot->data_json = $data;
            $snapshot->snapshot_date = $date;
            $snapshot->save();

            return $snapshot;
        } catch (\Exception $e) {
            Log::error('Search Consoleスナップショット作成エラー: ' . $e->getMessage(), [
                'website_id' => $website->id,
                'date' => $date->format('Y-m-d'),
            ]);

            throw $e;
        }
    }

    /**
     * 指定された期間のスナップショットを取得
     */
    public function getSnapshotsByDateRange(Website $website, $type, Carbon $startDate, Carbon $endDate)
    {
        return DataSnapshot::where('website_id', $website->id)
            ->where('snapshot_type', $type)
            ->whereBetween('snapshot_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('snapshot_date')
            ->get();
    }

    /**
     * 開発用のサンプルAnalyticsデータを生成
     */
    private function getSampleAnalyticsData(Carbon $date)
    {
        // 曜日によって変動するデータを作成（土日は少なめに）
        $dayOfWeek = $date->dayOfWeek;
        $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
        $multiplier = $isWeekend ? 0.6 : 1.0;

        // ユニークユーザー（100〜500）
        $users = round(rand(100, 500) * $multiplier);

        // セッション（ユーザー数の1.2〜1.5倍）
        $sessions = round($users * (1.2 + (rand(0, 30) / 100)));

        // ページビュー（セッションの2〜4倍）
        $pageviews = round($sessions * (2 + (rand(0, 20) / 10)));

        return [
            'date' => $date->format('Y-m-d'),
            'metrics' => [
                'users' => $users,
                'sessions' => $sessions,
                'pageviews' => $pageviews,
                'bounceRate' => round(rand(30, 70) + (rand(-100, 100) / 100), 1),
                'avgSessionDuration' => rand(60, 300),
            ],
            'dimensions' => [
                'devices' => [
                    ['device' => 'desktop', 'users' => round($users * 0.6)],
                    ['device' => 'mobile', 'users' => round($users * 0.35)],
                    ['device' => 'tablet', 'users' => round($users * 0.05)],
                ],
                'sources' => [
                    ['source' => 'google', 'users' => round($users * 0.4)],
                    ['source' => 'direct', 'users' => round($users * 0.3)],
                    ['source' => 'referral', 'users' => round($users * 0.15)],
                    ['source' => 'social', 'users' => round($users * 0.1)],
                    ['source' => 'other', 'users' => round($users * 0.05)],
                ],
                'pages' => [
                    ['page' => '/', 'pageviews' => round($pageviews * 0.3)],
                    ['page' => '/blog', 'pageviews' => round($pageviews * 0.25)],
                    ['page' => '/products', 'pageviews' => round($pageviews * 0.2)],
                    ['page' => '/about', 'pageviews' => round($pageviews * 0.15)],
                    ['page' => '/contact', 'pageviews' => round($pageviews * 0.1)],
                ],
            ],
        ];
    }

    /**
     * 開発用のサンプルSearch Consoleデータを生成
     */
    private function getSampleSearchConsoleData(Carbon $date)
    {
        // 曜日によって変動するデータを作成（土日は少なめに）
        $dayOfWeek = $date->dayOfWeek;
        $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
        $multiplier = $isWeekend ? 0.7 : 1.0;

        // クリック数（50〜300）
        $clicks = round(rand(50, 300) * $multiplier);

        // インプレッション（クリック数の10〜20倍）
        $impressions = $clicks * (10 + rand(0, 10));

        // CTR（クリック数 ÷ インプレッション）
        $ctr = round(($clicks / $impressions) * 100, 2);

        // キーワード
        $keywords = [
            'GA4 分析ツール',
            'Search Console 連携',
            'AI レポート',
            'ウェブ アクセス 解析',
            'ウェブサイト パフォーマンス',
            'SEO 改善',
            'Google データ 分析',
            'アクセス 解析 レポート',
        ];

        shuffle($keywords);
        $topKeywords = array_slice($keywords, 0, 5);

        $queryData = [];
        foreach ($topKeywords as $index => $keyword) {
            $queryData[] = [
                'query' => $keyword,
                'clicks' => round($clicks * (0.5 - ($index * 0.1))),
                'impressions' => round($impressions * (0.4 - ($index * 0.05))),
                'ctr' => round((0.1 - ($index * 0.015)) * 100, 2),
                'position' => round(rand(1, 10) + ($index * 0.5), 1),
            ];
        }

        return [
            'date' => $date->format('Y-m-d'),
            'metrics' => [
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'position' => round(rand(10, 50) / 10, 1),
            ],
            'queries' => $queryData,
            'pages' => [
                ['page' => '/', 'clicks' => round($clicks * 0.35)],
                ['page' => '/blog', 'clicks' => round($clicks * 0.25)],
                ['page' => '/products', 'clicks' => round($clicks * 0.2)],
                ['page' => '/about', 'clicks' => round($clicks * 0.1)],
                ['page' => '/contact', 'clicks' => round($clicks * 0.1)],
            ],
        ];
    }

    public function createSnapshot(AnalysisReport $report)
    {
        // レポートデータからスナップショットを作成するロジック
        return [
            'analytics' => $report->data_json['analytics'] ?? null,
            'search_console' => $report->data_json['search_console'] ?? null,
        ];
    }
}
