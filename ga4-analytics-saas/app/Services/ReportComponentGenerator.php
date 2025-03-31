<?php

namespace App\Services;

use App\Models\AnalysisReport;
use App\Models\ReportComponent;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReportComponentGenerator
{
    protected $dataSnapshotService;

    /**
     * コンストラクタ
     */
    public function __construct(DataSnapshotService $dataSnapshotService)
    {
        $this->dataSnapshotService = $dataSnapshotService;
    }
    /**
     * レポートの全コンポーネントを生成
     */
    public function generateAllComponents(AnalysisReport $report): void
    {
        try {
            $website = $report->website;
            $startDate = $report->date_range_start;
            $endDate = $report->date_range_end;
            $reportType = $report->report_type;

            // 既存のコンポーネントを削除
            $report->components()->delete();

            // 指定された期間のAnalyticsスナップショットを取得
            $analyticsSnapshots = $this->dataSnapshotService->getSnapshotsByDateRange(
                $website,
                'analytics',
                $startDate,
                $endDate
            );

            // 指定された期間のSearch Consoleスナップショットを取得
            $searchConsoleSnapshots = $this->dataSnapshotService->getSnapshotsByDateRange(
                $website,
                'search_console',
                $startDate,
                $endDate
            );

            // レポートタイプに基づいてコンポーネントを生成
            switch ($reportType) {
                case 'executive':
                    $this->generateExecutiveComponents($report, $analyticsSnapshots, $searchConsoleSnapshots);
                    break;

                case 'technical':
                    $this->generateTechnicalComponents($report, $analyticsSnapshots, $searchConsoleSnapshots);
                    break;

                case 'content':
                    $this->generateContentComponents($report, $analyticsSnapshots, $searchConsoleSnapshots);
                    break;

                default:
                    throw new \InvalidArgumentException("Unknown report type: {$reportType}");
            }

            Log::info('レポートコンポーネント生成完了', [
                'report_id' => $report->id,
                'components_count' => $report->components()->count()
            ]);
        } catch (\Exception $e) {
            Log::error('レポートコンポーネント生成エラー', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * 経営者向けレポートコンポーネントを生成
     */
    private function generateExecutiveComponents(AnalysisReport $report, $analyticsSnapshots, $searchConsoleSnapshots): void
    {
        $order = 1;

        // 1. 概要テキスト
        $this->createTextComponent(
            $report,
            '概要',
            $this->generateSummaryText($report, $analyticsSnapshots, $searchConsoleSnapshots),
            $order++
        );

        // 2. ユーザートレンドチャート
        if (!$analyticsSnapshots->isEmpty()) {
            $this->createUserTrendChart($report, $analyticsSnapshots, $order++);
        }

        // 3. 検索トラフィックチャート
        if (!$searchConsoleSnapshots->isEmpty()) {
            $this->createSearchTrendChart($report, $searchConsoleSnapshots, $order++);
        }

        // 4. デバイス分布チャート
        if (!$analyticsSnapshots->isEmpty()) {
            $this->createDeviceDistributionChart($report, $analyticsSnapshots, $order++);
        }

        // 5. ユーザー獲得チャネル
        if (!$analyticsSnapshots->isEmpty()) {
            $this->createAcquisitionChannelsChart($report, $analyticsSnapshots, $order++);
        }

        // 6. 人気ページテーブル
        if (!$analyticsSnapshots->isEmpty()) {
            $this->createPopularPagesTable($report, $analyticsSnapshots, $order++);
        }

        // 7. 人気キーワードテーブル
        if (!$searchConsoleSnapshots->isEmpty()) {
            $this->createTopKeywordsTable($report, $searchConsoleSnapshots, $order++);
        }

        // 8. ビジネス指標分析テキスト
        $this->createTextComponent(
            $report,
            'ビジネス指標の分析',
            $this->generateBusinessMetricsText($report, $analyticsSnapshots, $searchConsoleSnapshots),
            $order++
        );
    }

    /**
     * 技術者向けレポートコンポーネントを生成
     */
    private function generateTechnicalComponents(AnalysisReport $report, $analyticsSnapshots, $searchConsoleSnapshots): void
    {
        $order = 1;

        // 1. 技術概要テキスト
        $this->createTextComponent(
            $report,
            '技術的概要',
            $this->generateTechnicalSummaryText($report, $analyticsSnapshots, $searchConsoleSnapshots),
            $order++
        );

        // 2. ページ速度分析
        $this->createTextComponent(
            $report,
            'ページ速度分析',
            $this->generatePageSpeedText($report),
            $order++
        );

        // 3. モバイルユーザビリティ
        if (!$analyticsSnapshots->isEmpty()) {
            $this->createMobileUsabilityComponent($report, $analyticsSnapshots, $order++);
        }

        // 4. ページエラー分析
        $this->createTextComponent(
            $report,
            'ページエラー分析',
            $this->generatePageErrorsText($report),
            $order++
        );

        // 5. SEO技術分析
        if (!$searchConsoleSnapshots->isEmpty()) {
            $this->createSeoTechnicalAnalysisComponent($report, $searchConsoleSnapshots, $order++);
        }

        // 6. ページビューヒートマップ
        if (!$analyticsSnapshots->isEmpty()) {
            $this->createPageviewHeatmap($report, $analyticsSnapshots, $order++);
        }
    }

    /**
     * コンテンツ向けレポートコンポーネントを生成
     */
    private function generateContentComponents(AnalysisReport $report, $analyticsSnapshots, $searchConsoleSnapshots): void
    {
        $order = 1;

        // 1. コンテンツ概要テキスト
        $this->createTextComponent(
            $report,
            'コンテンツ概要',
            $this->generateContentSummaryText($report, $analyticsSnapshots, $searchConsoleSnapshots),
            $order++
        );

        // 2. コンテンツパフォーマンス表
        if (!$analyticsSnapshots->isEmpty()) {
            $this->createContentPerformanceTable($report, $analyticsSnapshots, $order++);
        }

        // 3. キーワードパフォーマンス表
        if (!$searchConsoleSnapshots->isEmpty()) {
            $this->createKeywordPerformanceTable($report, $searchConsoleSnapshots, $order++);
        }

        // 4. エンゲージメント分析チャート
        if (!$analyticsSnapshots->isEmpty()) {
            $this->createEngagementAnalysisChart($report, $analyticsSnapshots, $order++);
        }

        // 5. コンテンツ推奨
        $this->createTextComponent(
            $report,
            'コンテンツ改善提案',
            $this->generateContentRecommendationsText($report, $analyticsSnapshots, $searchConsoleSnapshots),
            $order++
        );
    }

    /**
     * テキストコンポーネントを作成
     */
    private function createTextComponent(AnalysisReport $report, string $title, string $content, int $order): ReportComponent
    {
        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'text',
            'title' => $title,
            'data_json' => [
                'content' => $content
            ],
            'order' => $order
        ]);
    }

    /**
     * ユーザートレンドチャートを作成
     */
    private function createUserTrendChart(AnalysisReport $report, $analyticsSnapshots, int $order): ReportComponent
    {
        $labels = [];
        $usersData = [];
        $sessionsData = [];
        $pageviewsData = [];

        foreach ($analyticsSnapshots as $snapshot) {
            $labels[] = $snapshot->snapshot_date->format('m/d');
            $usersData[] = $snapshot->data_json['metrics']['users'] ?? 0;
            $sessionsData[] = $snapshot->data_json['metrics']['sessions'] ?? 0;
            $pageviewsData[] = $snapshot->data_json['metrics']['pageviews'] ?? 0;
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'chart',
            'title' => 'ユーザートレンド',
            'data_json' => [
                'type' => 'line',
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'ユーザー数',
                        'data' => $usersData,
                        'borderColor' => 'rgb(75, 192, 192)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'tension' => 0.1,
                    ],
                    [
                        'label' => 'セッション数',
                        'data' => $sessionsData,
                        'borderColor' => 'rgb(54, 162, 235)',
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'tension' => 0.1,
                    ],
                    [
                        'label' => 'ページビュー数',
                        'data' => $pageviewsData,
                        'borderColor' => 'rgb(153, 102, 255)',
                        'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                        'tension' => 0.1,
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => [
                            'position' => 'top',
                        ],
                        'title' => [
                            'display' => true,
                            'text' => '期間中のユーザートレンド'
                        ]
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'title' => [
                                'display' => true,
                                'text' => '数値'
                            ]
                        ],
                        'x' => [
                            'title' => [
                                'display' => true,
                                'text' => '日付'
                            ]
                        ]
                    ]
                ]
            ],
            'order' => $order
        ]);
    }

    /**
     * 検索トレンドチャートを作成
     */
    private function createSearchTrendChart(AnalysisReport $report, $searchConsoleSnapshots, int $order): ReportComponent
    {
        $labels = [];
        $clicksData = [];
        $impressionsData = [];
        $ctrData = [];

        foreach ($searchConsoleSnapshots as $snapshot) {
            $labels[] = $snapshot->snapshot_date->format('m/d');
            $clicksData[] = $snapshot->data_json['metrics']['clicks'] ?? 0;
            $impressionsData[] = $snapshot->data_json['metrics']['impressions'] ?? 0;
            $ctrData[] = $snapshot->data_json['metrics']['ctr'] ?? 0;
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'chart',
            'title' => '検索トラフィックトレンド',
            'data_json' => [
                'type' => 'line',
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'クリック数',
                        'data' => $clicksData,
                        'borderColor' => 'rgb(255, 99, 132)',
                        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                        'tension' => 0.1,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'インプレッション数',
                        'data' => $impressionsData,
                        'borderColor' => 'rgb(153, 102, 255)',
                        'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                        'tension' => 0.1,
                        'yAxisID' => 'y1',
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => [
                            'position' => 'top',
                        ],
                        'title' => [
                            'display' => true,
                            'text' => '期間中の検索トラフィックトレンド'
                        ]
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'position' => 'left',
                            'title' => [
                                'display' => true,
                                'text' => 'クリック数'
                            ]
                        ],
                        'y1' => [
                            'beginAtZero' => true,
                            'position' => 'right',
                            'grid' => [
                                'drawOnChartArea' => false,
                            ],
                            'title' => [
                                'display' => true,
                                'text' => 'インプレッション数'
                            ]
                        ],
                        'x' => [
                            'title' => [
                                'display' => true,
                                'text' => '日付'
                            ]
                        ]
                    ]
                ]
            ],
            'order' => $order
        ]);
    }

    /**
     * デバイス分布チャートを作成
     */
    private function createDeviceDistributionChart(AnalysisReport $report, $analyticsSnapshots, int $order): ReportComponent
    {
        // 期間内の最新スナップショットを使用
        $latestSnapshot = $analyticsSnapshots->sortByDesc('snapshot_date')->first();

        if (!$latestSnapshot) {
            return null;
        }

        $devices = $latestSnapshot->data_json['dimensions']['devices'] ?? [];

        $labels = [];
        $data = [];
        $backgroundColor = [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 205, 86, 0.7)',
        ];
        $borderColor = [
            'rgb(54, 162, 235)',
            'rgb(255, 99, 132)',
            'rgb(255, 205, 86)',
        ];

        foreach ($devices as $index => $device) {
            $labels[] = ucfirst($device['device']);
            $data[] = $device['users'];
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'chart',
            'title' => 'デバイス別ユーザー分布',
            'data_json' => [
                'type' => 'pie',
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'ユーザー数',
                        'data' => $data,
                        'backgroundColor' => $backgroundColor,
                        'borderColor' => $borderColor,
                        'borderWidth' => 1,
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => [
                            'position' => 'top',
                        ],
                        'title' => [
                            'display' => true,
                            'text' => 'デバイス別ユーザー分布'
                        ]
                    ]
                ]
            ],
            'order' => $order
        ]);
    }

    /**
     * ユーザー獲得チャネルチャートを作成
     */
    private function createAcquisitionChannelsChart(AnalysisReport $report, $analyticsSnapshots, int $order): ReportComponent
    {
        // 期間内の最新スナップショットを使用
        $latestSnapshot = $analyticsSnapshots->sortByDesc('snapshot_date')->first();

        if (!$latestSnapshot) {
            return null;
        }

        $sources = $latestSnapshot->data_json['dimensions']['sources'] ?? [];

        $labels = [];
        $data = [];
        $backgroundColor = [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 205, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
        ];

        foreach ($sources as $source) {
            $labels[] = ucfirst($source['source']);
            $data[] = $source['users'];
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'chart',
            'title' => 'トラフィック獲得チャネル',
            'data_json' => [
                'type' => 'bar',
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'ユーザー数',
                        'data' => $data,
                        'backgroundColor' => $backgroundColor,
                        'borderWidth' => 1,
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => [
                            'position' => 'top',
                        ],
                        'title' => [
                            'display' => true,
                            'text' => 'トラフィック獲得チャネル'
                        ]
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'title' => [
                                'display' => true,
                                'text' => 'ユーザー数'
                            ]
                        ]
                    ]
                ]
            ],
            'order' => $order
        ]);
    }

    /**
     * 人気ページテーブルを作成
     */
    private function createPopularPagesTable(AnalysisReport $report, $analyticsSnapshots, int $order): ReportComponent
    {
        // 期間内の最新スナップショットを使用
        $latestSnapshot = $analyticsSnapshots->sortByDesc('snapshot_date')->first();

        if (!$latestSnapshot) {
            return null;
        }

        $pages = $latestSnapshot->data_json['dimensions']['pages'] ?? [];

        $headers = ['ページURL', 'ページビュー数'];
        $rows = [];

        foreach ($pages as $page) {
            $rows[] = [
                $page['page'],
                number_format($page['pageviews'])
            ];
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'table',
            'title' => '人気ページ',
            'data_json' => [
                'headers' => $headers,
                'rows' => $rows
            ],
            'order' => $order
        ]);
    }

    /**
     * 人気キーワードテーブルを作成
     */
    private function createTopKeywordsTable(AnalysisReport $report, $searchConsoleSnapshots, int $order): ReportComponent
    {
        // 期間内の最新スナップショットを使用
        $latestSnapshot = $searchConsoleSnapshots->sortByDesc('snapshot_date')->first();

        if (!$latestSnapshot) {
            return null;
        }

        $queries = $latestSnapshot->data_json['queries'] ?? [];

        $headers = ['キーワード', 'クリック数', 'インプレッション数', 'CTR', '平均掲載順位'];
        $rows = [];

        foreach ($queries as $query) {
            $rows[] = [
                $query['query'],
                number_format($query['clicks']),
                number_format($query['impressions']),
                $query['ctr'] . '%',
                $query['position']
            ];
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'table',
            'title' => '検索キーワード',
            'data_json' => [
                'headers' => $headers,
                'rows' => $rows
            ],
            'order' => $order
        ]);
    }

    /**
     * ページビューヒートマップを作成
     */
    private function createPageviewHeatmap(AnalysisReport $report, $analyticsSnapshots, int $order): ReportComponent
    {
        // ダミーのヒートマップデータを生成
        $data = [];

        // 1週間分の時間別データを生成
        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $data[] = [
                    'day' => $day,
                    'hour' => $hour,
                    'value' => rand(1, 100)
                ];
            }
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'heatmap',
            'title' => 'ページビュー時間帯ヒートマップ',
            'data_json' => [
                'data' => $data,
                'days' => ['日', '月', '火', '水', '木', '金', '土'],
                'hours' => array_map(function ($hour) {
                    return sprintf('%02d:00', $hour);
                }, range(0, 23))
            ],
            'order' => $order
        ]);
    }

    /**
     * モバイルユーザビリティコンポーネントを作成
     */
    private function createMobileUsabilityComponent(AnalysisReport $report, $analyticsSnapshots, int $order): ReportComponent
    {
        // 期間内のデータからモバイルユーザー比率を計算
        $totalUsers = 0;
        $mobileUsers = 0;

        foreach ($analyticsSnapshots as $snapshot) {
            $devices = $snapshot->data_json['dimensions']['devices'] ?? [];

            foreach ($devices as $device) {
                if ($device['device'] === 'mobile') {
                    $mobileUsers += $device['users'];
                }
                $totalUsers += $device['users'];
            }
        }

        $mobileRatio = $totalUsers > 0 ? round(($mobileUsers / $totalUsers) * 100, 1) : 0;

        // モバイルユーザビリティスコアを生成（実際はPageSpeed Insightsなどから取得）
        $mobileScore = rand(60, 95);

        $contentHtml = <<<HTML
<div class="mb-4">
    <p>モバイルユーザー比率: <strong>{$mobileRatio}%</strong></p>
    <div class="bg-gray-200 h-4 rounded-full mt-2">
        <div class="h-4 rounded-full bg-blue-600" style="width: {$mobileRatio}%"></div>
    </div>
</div>

<div class="mb-4">
    <p>モバイルユーザビリティスコア: <strong>{$mobileScore}/100</strong></p>
    <div class="bg-gray-200 h-4 rounded-full mt-2">
        <div class="h-4 rounded-full
            <?= $mobileScore >= 90 ? 'bg-green-600' : ($mobileScore >= 70 ? 'bg-yellow-500' : 'bg-red-600') ?>"
            style="width: <?= $mobileScore ?>%">
        </div>
    </div>
</div>

<h4 class="font-medium mb-2">改善点:</h4>
<ul class="list-disc pl-5 space-y-1">
    <li>タップターゲットの適切なサイズ調整</li>
    <li>レスポンシブデザインの最適化</li>
    <li>画像サイズの最適化</li>
    <li>フォントサイズの調整</li>
</ul>
HTML;

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'text',
            'title' => 'モバイルユーザビリティ',
            'data_json' => [
                'content' => $contentHtml
            ],
            'order' => $order
        ]);
    }

    /**
     * SEO技術分析コンポーネントを作成
     */
    private function createSeoTechnicalAnalysisComponent(AnalysisReport $report, $searchConsoleSnapshots, int $order): ReportComponent
    {
        $contentHtml = <<<HTML
<div class="space-y-4">
    <div>
        <h4 class="font-medium mb-2">インデックス状況</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li>インデックスされたページ: <strong>238</strong></li>
            <li>インデックスエラー: <strong>12</strong></li>
            <li>除外されたページ: <strong>5</strong></li>
        </ul>
    </div>

    <div>
    <h4 class="font-medium mb-2">クロール状況</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li>クロール頻度: <strong>中程度</strong></li>
            <li>クロールエラー: <strong>2</strong></li>
            <li>最終クロール日: <strong>3日前</strong></li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">構造化データ</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li>検出された構造化データ: <strong>24</strong></li>
            <li>エラーのある構造化データ: <strong>3</strong></li>
            <li>推奨される追加構造化データ: <strong>5</strong></li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">改善推奨事項</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li>サイトマップの更新</li>
            <li>破損しているリンクの修正</li>
            <li>重複コンテンツの解決</li>
            <li>robots.txtの最適化</li>
            <li>ページタイトルとメタディスクリプションの改善</li>
        </ul>
    </div>
</div>
HTML;

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'text',
            'title' => 'SEO技術分析',
            'data_json' => [
                'content' => $contentHtml
            ],
            'order' => $order
        ]);
    }


    /**
     * コンテンツパフォーマンステーブルを作成
     */
    private function createContentPerformanceTable(AnalysisReport $report, $analyticsSnapshots, int $order): ReportComponent
    {
        // 期間内の最新スナップショットを使用
        $latestSnapshot = $analyticsSnapshots->sortByDesc('snapshot_date')->first();

        if (!$latestSnapshot) {
            return null;
        }

        $pages = $latestSnapshot->data_json['dimensions']['pages'] ?? [];

        // コンテンツパフォーマンス指標を追加（ダミーデータ）
        $headers = ['ページURL', 'ページビュー数', '平均滞在時間', '直帰率', 'コンバージョン率'];
        $rows = [];

        foreach ($pages as $page) {
            $rows[] = [
                $page['page'],
                number_format($page['pageviews']),
                rand(1, 5) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT),
                rand(20, 80) . '%',
                rand(1, 10) . '.' . rand(0, 9) . '%'
            ];
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'table',
            'title' => 'コンテンツパフォーマンス',
            'data_json' => [
                'headers' => $headers,
                'rows' => $rows
            ],
            'order' => $order
        ]);
    }

    /**
     * キーワードパフォーマンステーブルを作成
     */
    private function createKeywordPerformanceTable(AnalysisReport $report, $searchConsoleSnapshots, int $order): ReportComponent
    {
        // 期間内の最新スナップショットを使用
        $latestSnapshot = $searchConsoleSnapshots->sortByDesc('snapshot_date')->first();

        if (!$latestSnapshot) {
            return null;
        }

        $queries = $latestSnapshot->data_json['queries'] ?? [];

        $headers = ['キーワード', 'クリック数', 'インプレッション数', 'CTR', '平均掲載順位', 'コンテンツ関連性'];
        $rows = [];

        $relevancy = ['高', '中', '高', '低', '中高'];

        foreach ($queries as $index => $query) {
            $rows[] = [
                $query['query'],
                number_format($query['clicks']),
                number_format($query['impressions']),
                $query['ctr'] . '%',
                $query['position'],
                $relevancy[$index % count($relevancy)]
            ];
        }

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'table',
            'title' => 'キーワードパフォーマンス',
            'data_json' => [
                'headers' => $headers,
                'rows' => $rows
            ],
            'order' => $order
        ]);
    }

    /**
     * エンゲージメント分析チャートを作成
     */
    private function createEngagementAnalysisChart(AnalysisReport $report, $analyticsSnapshots, int $order): ReportComponent
    {
        // ダミーのエンゲージメントデータを生成
        $labels = ['ホーム', 'ブログ', '製品', 'サービス', 'お問い合わせ'];
        $avgTimeOnPage = [rand(100, 300), rand(200, 500), rand(150, 400), rand(100, 250), rand(50, 150)];
        $bounceRates = [rand(30, 70), rand(20, 60), rand(30, 50), rand(40, 80), rand(40, 60)];

        return ReportComponent::create([
            'report_id' => $report->id,
            'component_type' => 'chart',
            'title' => 'ページエンゲージメント分析',
            'data_json' => [
                'type' => 'bar',
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => '平均滞在時間（秒）',
                        'data' => $avgTimeOnPage,
                        'backgroundColor' => 'rgba(75, 192, 192, 0.7)',
                        'borderColor' => 'rgb(75, 192, 192)',
                        'borderWidth' => 1,
                        'yAxisID' => 'y'
                    ],
                    [
                        'label' => '直帰率（%）',
                        'data' => $bounceRates,
                        'backgroundColor' => 'rgba(255, 99, 132, 0.7)',
                        'borderColor' => 'rgb(255, 99, 132)',
                        'borderWidth' => 1,
                        'yAxisID' => 'y1'
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => [
                            'position' => 'top',
                        ],
                        'title' => [
                            'display' => true,
                            'text' => 'ページごとのエンゲージメント'
                        ]
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'position' => 'left',
                            'title' => [
                                'display' => true,
                                'text' => '平均滞在時間（秒）'
                            ]
                        ],
                        'y1' => [
                            'beginAtZero' => true,
                            'position' => 'right',
                            'grid' => [
                                'drawOnChartArea' => false,
                            ],
                            'title' => [
                                'display' => true,
                                'text' => '直帰率（%）'
                            ]
                        ]
                    ]
                ]
            ],
            'order' => $order
        ]);
    }

    /**
     * 概要テキストを生成
     */
    private function generateSummaryText(AnalysisReport $report, $analyticsSnapshots, $searchConsoleSnapshots): string
    {
        $website = $report->website;
        $startDate = $report->date_range_start->format('Y年m月d日');
        $endDate = $report->date_range_end->format('Y年m月d日');

        // Analytics データの集計
        $totalUsers = 0;
        $totalSessions = 0;
        $totalPageviews = 0;

        foreach ($analyticsSnapshots as $snapshot) {
            $totalUsers += $snapshot->data_json['metrics']['users'] ?? 0;
            $totalSessions += $snapshot->data_json['metrics']['sessions'] ?? 0;
            $totalPageviews += $snapshot->data_json['metrics']['pageviews'] ?? 0;
        }

        // Search Console データの集計
        $totalClicks = 0;
        $totalImpressions = 0;

        foreach ($searchConsoleSnapshots as $snapshot) {
            $totalClicks += $snapshot->data_json['metrics']['clicks'] ?? 0;
            $totalImpressions += $snapshot->data_json['metrics']['impressions'] ?? 0;
        }

        $averageCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

        $text = <<<TEXT
<p>このレポートは、{$startDate}から{$endDate}までの期間における「{$website->name}」のパフォーマンスを分析したものです。</p>

<h4 class="font-medium mt-4 mb-2">主要指標の概要</h4>

<ul class="list-disc pl-5 space-y-1">
  <li><strong>ユーザー数:</strong> {$totalUsers}人</li>
  <li><strong>セッション数:</strong> {$totalSessions}回</li>
  <li><strong>ページビュー数:</strong> {$totalPageviews}回</li>
  <li><strong>検索クリック数:</strong> {$totalClicks}回</li>
  <li><strong>検索インプレッション数:</strong> {$totalImpressions}回</li>
  <li><strong>平均クリック率(CTR):</strong> {$averageCtr}%</li>
</ul>

<p class="mt-4">期間中のウェブサイトパフォーマンスは全体的に良好で、特にユーザーエンゲージメントと検索トラフィックに好ましい傾向が見られます。詳細な分析結果と改善提案については、以下のレポートコンポーネントをご参照ください。</p>
TEXT;

        return $text;
    }

    /**
     * ビジネス指標分析テキストを生成
     */
    private function generateBusinessMetricsText(AnalysisReport $report, $analyticsSnapshots, $searchConsoleSnapshots): string
    {
        // ダミーの分析テキスト
        $text = <<<TEXT
<h4 class="font-medium mb-2">全体パフォーマンス評価</h4>

<p>期間中のビジネス指標は安定した成長傾向を示しています。オーガニックトラフィックが前月比15%増加し、コンバージョン率も2.3%向上しました。これは最近のコンテンツ戦略とSEO施策の効果が表れていると考えられます。</p>

<h4 class="font-medium mt-4 mb-2">ROI分析</h4>

<p>デジタルマーケティング投資に対するROIは、期間中に8.5%改善しました。特に検索広告とSEO対策の組み合わせが効果的で、獲得コストあたりの顧客価値が向上しています。</p>

<h4 class="font-medium mt-4 mb-2">ビジネス機会</h4>

<ul class="list-disc pl-5 space-y-1">
  <li><strong>モバイルユーザー向け最適化:</strong> モバイルユーザーが全体の65%を占めていますが、コンバージョン率はデスクトップの75%に留まっています。モバイルユーザー体験を改善することで、売上向上が期待できます。</li>
  <li><strong>コンテンツマーケティング強化:</strong> ブログ記事からの流入が前月比30%増加しており、さらなるコンテンツ投資が効果的と考えられます。</li>
  <li><strong>リターゲティング施策:</strong> 直帰率の高いページからの訪問者に対するリターゲティング広告の実施で、コンバージョン率の改善が見込めます。</li>
</ul>

<h4 class="font-medium mt-4 mb-2">リスク要因</h4>

<ul class="list-disc pl-5 space-y-1">
  <li><strong>検索エンジンアルゴリズム変更:</strong> 最近のGoogle更新により、一部キーワードのランキングが変動しています。コンテンツの質と関連性の向上が必要です。</li>
  <li><strong>競合他社の動向:</strong> 主要競合2社が同様の製品カテゴリでコンテンツ投資を増やしており、差別化戦略が重要になっています。</li>
</ul>

<p class="mt-4">総じて、デジタルマーケティング施策は着実に成果を上げており、特にコンテンツマーケティングとSEO対策の継続的な強化が推奨されます。次四半期はモバイルユーザー体験の最適化に注力することで、さらなるビジネス成長が期待できます。</p>
TEXT;

        return $text;
    }

    /**
     * 技術的概要テキストを生成
     */
    private function generateTechnicalSummaryText(AnalysisReport $report, $analyticsSnapshots, $searchConsoleSnapshots): string
    {
        // ダミーの技術的概要テキスト
        $text = <<<TEXT
<p>このレポートでは、サイトの技術的パフォーマンスと改善機会について分析しています。</p>

<h4 class="font-medium mt-4 mb-2">主要な技術指標</h4>

<ul class="list-disc pl-5 space-y-1">
  <li><strong>平均ページ読み込み時間:</strong> 3.2秒（業界平均より0.8秒遅い）</li>
  <li><strong>モバイル対応スコア:</strong> 82/100</li>
  <li><strong>HTML検証エラー:</strong> 24件</li>
  <li><strong>JavaScript例外:</strong> 5件</li>
  <li><strong>HTTPS実装状況:</strong> 完全対応</li>
  <li><strong>Core Web Vitals:</strong> 2/3項目でPass</li>
</ul>

<p class="mt-4">全体的な技術パフォーマンスは許容範囲内ですが、いくつかの改善点が見つかりました。特にページ読み込み速度の最適化とモバイル対応の強化が推奨されます。</p>
TEXT;

        return $text;
    }

    /**
     * ページ速度分析テキストを生成
     */
    private function generatePageSpeedText(AnalysisReport $report): string
    {
        // ダミーのページ速度分析テキスト
        $text = <<<TEXT
<div class="space-y-4">
    <div>
        <h4 class="font-medium mb-2">Core Web Vitals</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Largest Contentful Paint (LCP):</strong> 2.8秒 <span class="text-yellow-600">(改善の余地あり)</span></li>
            <li><strong>First Input Delay (FID):</strong> 65ms <span class="text-green-600">(良好)</span></li>
            <li><strong>Cumulative Layout Shift (CLS):</strong> 0.12 <span class="text-yellow-600">(改善の余地あり)</span></li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">デバイス別読み込み時間</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>デスクトップ:</strong> 2.1秒</li>
            <li><strong>モバイル:</strong> 3.7秒</li>
            <li><strong>タブレット:</strong> 2.9秒</li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">主な遅延要因</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li>未圧縮の画像ファイル（合計1.8MB）</li>
            <li>レンダリングをブロックするJavaScript（3ファイル）</li>
            <li>キャッシュポリシーの未最適化</li>
            <li>複数の未使用CSSルール</li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">改善提案</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li>WebP形式での画像提供と適切なサイズ最適化</li>
            <li>クリティカルCSSの実装と非クリティカルCSSの遅延読み込み</li>
            <li>JavaScriptの非同期読み込みとコード分割</li>
            <li>ブラウザキャッシュの適切な設定</li>
            <li>不要なサードパーティスクリプトの削除または遅延読み込み</li>
        </ul>
    </div>
</div>
TEXT;

        return $text;
    }

    /**
     * ページエラー分析テキストを生成
     */
    private function generatePageErrorsText(AnalysisReport $report): string
    {
        // ダミーのページエラー分析テキスト
        $text = <<<TEXT
<div class="space-y-4">
    <div>
        <h4 class="font-medium mb-2">HTTP エラー</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>404エラー:</strong> 8件</li>
            <li><strong>500エラー:</strong> 1件</li>
            <li><strong>301リダイレクト:</strong> 15件</li>
            <li><strong>リダイレクトチェーン:</strong> 3件</li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">主なエラーページ</h4>
        <table class="min-w-full divide-y divide-gray-200 mt-2">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">エラータイプ</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">発生回数</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">/products/old-item</td>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">404</td>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">24</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">/blog/2022/03/</td>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">404</td>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">18</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">/api/products</td>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">500</td>
                    <td class="px-3 py-2 whitespace-nowrap text-sm">6</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div>
        <h4 class="font-medium mb-2">JavaScript 例外</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li>Uncaught TypeError: main.js:156</li>
            <li>ReferenceError: sliderComponent.js:28</li>
            <li>Failed to load resource: shopping-cart.js</li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">改善推奨事項</h4>
        <ul class="list-disc pl-5 space-y-1">
            <li>404エラーの多いページに適切なリダイレクトを設定</li>
            <li>サーバーエラーの原因となっているAPIエンドポイントを修正</li>
            <li>JavaScriptエラーを解消し、エラーハンドリングを強化</li>
            <li>リダイレクトチェーンを排除し、直接的なリダイレクトを設定</li>
            <li>カスタム404ページを改善し、ユーザー離脱を防止</li>
        </ul>
    </div>
</div>
TEXT;

        return $text;
    }

    /**
     * コンテンツ概要テキストを生成
     */
    private function generateContentSummaryText(AnalysisReport $report, $analyticsSnapshots, $searchConsoleSnapshots): string
    {
        // ダミーのコンテンツ概要テキスト
        $text = <<<TEXT
<p>このレポートでは、コンテンツの効果とユーザーエンゲージメントを分析し、改善のための具体的な提案を提供します。</p>

<h4 class="font-medium mt-4 mb-2">コンテンツパフォーマンス概要</h4>

<ul class="list-disc pl-5 space-y-1">
  <li><strong>エンゲージメント率:</strong> 63.5%</li>
  <li><strong>平均滞在時間:</strong> 2分45秒</li>
  <li><strong>ソーシャルシェア:</strong> 152件</li>
  <li><strong>コメント数:</strong> 38件</li>
  <li><strong>最も人気の高いコンテンツカテゴリ:</strong> ハウツーガイド、事例紹介</li>
</ul>

<p class="mt-4">全体的にコンテンツパフォーマンスは良好です。特にブログセクションの訪問者エンゲージメントが高く、滞在時間が平均より40%長くなっています。一方で、製品ページのコンバージョン率には改善の余地があります。</p>
TEXT;

        return $text;
    }

    /**
     * コンテンツ改善提案テキストを生成
     */
    private function generateContentRecommendationsText(AnalysisReport $report, $analyticsSnapshots, $searchConsoleSnapshots): string
    {
        // ダミーのコンテンツ改善提案テキスト
        $text = <<<TEXT
<div class="space-y-4">
    <div>
        <h4 class="font-medium mb-2">コンテンツギャップの分析</h4>
        <p>競合サイトと比較した際に、以下のトピックでのコンテンツが不足しています：</p>
        <ul class="list-disc pl-5 space-y-1 mt-2">
            <li>製品の詳細な使用方法ガイド</li>
            <li>業界トレンドに関する分析記事</li>
            <li>ユーザー事例（特にB2B領域）</li>
            <li>初心者向けのチュートリアル</li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">SEOコンテンツ機会</h4>
        <p>以下のキーワードは検索ボリュームが高く、競合が少ない機会です：</p>
        <ul class="list-disc pl-5 space-y-1 mt-2">
            <li>"効率的なデータ分析手法" (月間検索ボリューム: 1,200)</li>
            <li>"初心者向け統計分析ツール" (月間検索ボリューム: 850)</li>
            <li>"無料データ可視化プラットフォーム" (月間検索ボリューム: 720)</li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">既存コンテンツの最適化</h4>
        <p>以下のページは高いトラフィックを獲得していますが、コンバージョン率が低いため最適化が必要です：</p>
        <ul class="list-disc pl-5 space-y-1 mt-2">
            <li>/blog/data-analysis-beginners-guide</li>
            <li>/resources/templates</li>
            <li>/services/consulting</li>
        </ul>
        <p class="mt-2">これらのページには、明確なCTA（行動喚起）の追加、関連製品へのリンク強化、社会的証明の要素を導入することが推奨されます。</p>
    </div>

    <div>
        <h4 class="font-medium mb-2">コンテンツフォーマットの多様化</h4>
        <p>ユーザーエンゲージメントを高めるために、以下のコンテンツフォーマットの導入を検討してください：</p>
        <ul class="list-disc pl-5 space-y-1 mt-2">
            <li>インフォグラフィック（特にデータ統計に関する内容）</li>
            <li>ハウツービデオ（製品の使用方法）</li>
            <li>インタラクティブなデモ（製品機能の紹介）</li>
            <li>ポッドキャスト（業界のトレンド分析）</li>
        </ul>
    </div>

    <div>
        <h4 class="font-medium mb-2">コンテンツカレンダーの提案</h4>
        <p>次の四半期に向けて、以下のコンテンツの作成を計画することをお勧めします：</p>
        <ol class="list-decimal pl-5 space-y-1 mt-2">
            <li>「データ分析の基礎からマスターまで」シリーズ記事（初心者向け）</li>
            <li>「業界別データ分析事例」シリーズ（意思決定者向け）</li>
            <li>「5分でわかる統計分析」ビデオシリーズ</li>
            <li>「データ可視化のベストプラクティス」インフォグラフィック</li>
            <li>ユーザー事例：ROI向上に成功した3社のケーススタディ</li>
        </ol>
    </div>
</div>
TEXT;

        return $text;
    }
}
