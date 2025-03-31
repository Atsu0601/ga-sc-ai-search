<?php

namespace App\Jobs;

use App\Models\AnalysisReport;
use App\Services\ReportComponentGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateReportComponentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 再試行回数
     */
    public $tries = 3;

    /**
     * 失敗までのタイムアウト時間（秒）
     */
    public $timeout = 300;

    /**
     * レポートID
     */
    protected $reportId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $reportId)
    {
        $this->reportId = $reportId;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportComponentGenerator $componentGenerator): void
    {
        try {
            Log::info('レポートコンポーネント更新ジョブ開始', ['report_id' => $this->reportId]);

            // レポートを取得
            $report = AnalysisReport::findOrFail($this->reportId);

            // コンポーネントを生成
            $componentGenerator->generateAllComponents($report);

            Log::info('レポートコンポーネント更新ジョブ完了', ['report_id' => $this->reportId]);
        } catch (\Exception $e) {
            Log::error('レポートコンポーネント更新ジョブエラー', [
                'report_id' => $this->reportId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
