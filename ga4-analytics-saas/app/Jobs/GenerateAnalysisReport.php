<?php

namespace App\Jobs;

// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Queue\Queueable;

use App\Models\AnalysisReport;
use App\Models\ReportComponent;
use App\Models\AiRecommendation;
use App\Services\GoogleAnalyticsService;
use App\Services\SearchConsoleService;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateAnalysisReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 再試行回数
     */
    public $tries = 3;

    /**
     * 失敗までのタイムアウト時間（秒）
     */
    public $timeout = 600;

    /**
     * レポートモデル
     */
    protected $report;

    /**
     * Create a new job instance.
     */
    public function __construct(AnalysisReport $report)
    {
        $this->report = $report;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleAnalyticsService $gaService, SearchConsoleService $scService, OpenAiService $openAiService): void
    {
        try {
            Log::info('レポート生成開始', ['report_id' => $this->report->id]);

            // ウェブサイト情報を取得
            $website = $this->report->website;

            // GA4のデータを取得
            if ($website->analyticsAccount) {
                $gaData = $gaService->fetchData(
                    $website->analyticsAccount,
                    $this->report->date_range_start,
                    $this->report->date_range_end
                );
            } else {
                throw new Exception('Google Analyticsアカウントが接続されていません。');
            }

            // Search Consoleのデータを取得
            if ($website->searchConsoleAccount) {
                $scData = $scService->fetchData(
                    $website->searchConsoleAccount,
                    $this->report->date_range_start,
                    $this->report->date_range_end
                );
            } else {
                throw new Exception('Search Consoleアカウントが接続されていません。');
            }

            // レポートタイプに基づいて適切なコンポーネントを生成
            $this->generateReportComponents($gaData, $scData);

            // OpenAIを使用してレコメンデーションを生成
            $this->generateAiRecommendations($openAiService, $gaData, $scData);

            // PDFを生成
            $filePath = $this->generatePdf();

            // レポート状態を更新
            $this->report->status = 'completed';
            $this->report->file_path = $filePath;
            $this->report->save();

            Log::info('レポート生成完了', ['report_id' => $this->report->id]);
        } catch (Exception $e) {
            Log::error('レポート生成エラー', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // レポート状態を更新
            $this->report->status = 'failed';
            $this->report->save();

            throw $e;
        }
    }

    /**
     * レポートコンポーネントを生成
     */
    private function generateReportComponents($gaData, $scData): void
    {
        // データに基づいて適切なコンポーネントを生成
        // （実際の実装はデータ構造に依存）

        // 例：トラフィック概要チャート
        $this->createComponent('chart', 'サイトトラフィック概要', [
            'type' => 'line',
            'data' => $gaData['traffic_overview'] ?? [],
            'options' => [
                'x_axis' => '日付',
                'y_axis' => 'ユーザー数'
            ]
        ], 1);

        // 例：検索キーワードテーブル
        $this->createComponent('table', '上位検索キーワード', [
            'headers' => ['キーワード', 'クリック数', '表示回数', 'CTR', '平均掲載順位'],
            'rows' => $scData['top_keywords'] ?? []
        ], 2);

        // 例：ページビューヒートマップ
        $this->createComponent('heatmap', 'ページビュー分布', [
            'data' => $gaData['pageview_heatmap'] ?? []
        ], 3);

        // その他、レポートタイプに応じて適切なコンポーネントを追加
    }

    /**
     * レポートコンポーネントを作成
     */
    private function createComponent($type, $title, $data, $order): void
    {
        $component = new ReportComponent();
        $component->report_id = $this->report->id;
        $component->component_type = $type;
        $component->title = $title;
        $component->data_json = json_encode($data);
        $component->order = $order;
        $component->save();
    }

    /**
     * AIレコメンデーションを生成
     */
    private function generateAiRecommendations($openAiService, $gaData, $scData): void
    {
        // OpenAI APIを使用してデータ分析とレコメンデーション生成
        $recommendations = $openAiService->generateRecommendations(
            $this->report->website,
            $this->report->report_type,
            $gaData,
            $scData
        );

        // 各レコメンデーションを保存
        foreach ($recommendations as $recommendation) {
            $rec = new AiRecommendation();
            $rec->report_id = $this->report->id;
            $rec->category = $recommendation['category'];
            $rec->severity = $recommendation['severity'];
            $rec->content = $recommendation['content'];
            $rec->save();
        }
    }

    /**
     * PDFレポートを生成
     */
    private function generatePdf(): string
    {
        // PDFファイルの生成（実際の実装はPDFライブラリに依存）
        $fileName = 'reports/' . $this->report->id . '_' . time() . '.pdf';

        // TODO: 実際のPDF生成コード

        return $fileName;
    }
}
