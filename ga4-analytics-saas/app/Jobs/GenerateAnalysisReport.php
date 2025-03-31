<?php

namespace App\Jobs;

use App\Models\AnalysisReport;
use App\Models\ReportComponent;
use App\Models\AiRecommendation;
use App\Services\GoogleAnalyticsService;
use App\Services\SearchConsoleService;
use App\Services\DataSnapshotService;
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
    public function handle(
        GoogleAnalyticsService $gaService,
        SearchConsoleService $scService,
        OpenAiService $openAiService,
        DataSnapshotService $dataSnapshotService
    ): void
    {
        try {
            Log::info('レポート生成開始', ['report_id' => $this->report->id]);

            // ウェブサイト情報を取得
            $website = $this->report->website;

            // 既存のデータスナップショットを使用してレポートを生成
            // またはデータスナップショットがない場合は取得を試みる
            $startDate = $this->report->date_range_start;
            $endDate = $this->report->date_range_end;

            // 期間内のAnalyticsスナップショットを確認
            $analyticsSnapshots = $dataSnapshotService->getSnapshotsByDateRange(
                $website, 'analytics', $startDate, $endDate
            );

            // 期間内のSearch Consoleスナップショットを確認
            $searchConsoleSnapshots = $dataSnapshotService->getSnapshotsByDateRange(
                $website, 'search_console', $startDate, $endDate
            );

            // スナップショットが不足している場合、可能であれば新しく作成
            if ($analyticsSnapshots->isEmpty() && $website->analyticsAccount) {
                // 新規スナップショットを作成（開発用なのでサンプルデータ）
                $currentDate = clone $startDate;
                while ($currentDate <= $endDate) {
                    $dataSnapshotService->createAnalyticsSnapshot($website, $currentDate);
                    $currentDate->addDay();
                }

                // 再度スナップショットを取得
                $analyticsSnapshots = $dataSnapshotService->getSnapshotsByDateRange(
                    $website, 'analytics', $startDate, $endDate
                );
            }

            if ($searchConsoleSnapshots->isEmpty() && $website->searchConsoleAccount) {
                // 新規スナップショットを作成（開発用なのでサンプルデータ）
                $currentDate = clone $startDate;
                while ($currentDate <= $endDate) {
                    $dataSnapshotService->createSearchConsoleSnapshot($website, $currentDate);
                    $currentDate->addDay();
                }

                // 再度スナップショットを取得
                $searchConsoleSnapshots = $dataSnapshotService->getSnapshotsByDateRange(
                    $website, 'search_console', $startDate, $endDate
                );
            }

            // レポートコンポーネント生成ジョブをディスパッチ
            UpdateReportComponentsJob::dispatch($this->report->id);

            // OpenAIを使用してレコメンデーションを生成
            $this->generateAiRecommendations($openAiService, $analyticsSnapshots, $searchConsoleSnapshots);

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
     * AIレコメンデーションを生成
     */
    private function generateAiRecommendations($openAiService, $analyticsSnapshots, $searchConsoleSnapshots): void
    {
        // OpenAI APIを使用してデータ分析とレコメンデーション生成
        $recommendations = $openAiService->generateRecommendations(
            $this->report->website,
            $this->report->report_type,
            $analyticsSnapshots,
            $searchConsoleSnapshots
        );

        // 既存のレコメンデーションを削除
        $this->report->recommendations()->delete();

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
