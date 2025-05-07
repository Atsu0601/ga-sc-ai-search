<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Website;
use App\Services\DataSnapshotService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SnapshotsCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 再試行回数
     */
    public $tries = 3;

    /**
     * 失敗までのタイムアウト時間（秒）
     */
    public $timeout = 3600;

    /**
     * 保持するスナップショットの期間（日数）
     */
    protected $retentionDays = 90;

    /**
     * Execute the job.
     */
    public function handle(DataSnapshotService $dataSnapshotService): void
    {
        try {
            Log::info('Snapshots cleanup started');

            // 削除対象の日付を計算
            $cutoffDate = Carbon::now()->subDays($this->retentionDays);

            // すべてのウェブサイトを取得
            $websites = Website::all();

            foreach ($websites as $website) {
                try {
                    // 古いAnalyticsスナップショットを削除
                    $deletedAnalytics = $dataSnapshotService->deleteOldSnapshots(
                        $website,
                        'analytics',
                        $cutoffDate
                    );

                    // 古いSearch Consoleスナップショットを削除
                    $deletedSearchConsole = $dataSnapshotService->deleteOldSnapshots(
                        $website,
                        'search_console',
                        $cutoffDate
                    );

                    Log::info('Snapshots cleaned up for website', [
                        'website_id' => $website->id,
                        'website_name' => $website->name,
                        'deleted_analytics' => $deletedAnalytics,
                        'deleted_search_console' => $deletedSearchConsole
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to clean up snapshots for website', [
                        'website_id' => $website->id,
                        'website_name' => $website->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Snapshots cleanup completed');
        } catch (\Exception $e) {
            Log::error('Snapshots cleanup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * ジョブが失敗した場合の処理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Snapshots cleanup job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
