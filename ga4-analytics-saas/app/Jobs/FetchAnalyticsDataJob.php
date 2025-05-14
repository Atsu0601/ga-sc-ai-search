<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Website;
use App\Services\DataSnapshotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FetchAnalyticsDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;

    public function handle(DataSnapshotService $dataSnapshotService)
    {
        Log::info('Analytics データ取得ジョブを開始');

        try {
            // 昨日の日付
            $targetDate = Carbon::yesterday();

            // アクティブなウェブサイトを取得
            $websites = Website::where('status', 'active')
                ->has('analyticsAccount')
                ->get();

            Log::info("処理対象のウェブサイト: {$websites->count()}件");

            // 各ウェブサイトのスナップショットを作成
            foreach ($websites as $website) {
                try {
                    Log::info("Analytics スナップショット作成開始", [
                        'website_id' => $website->id,
                        'website_name' => $website->name,
                        'date' => $targetDate->format('Y-m-d')
                    ]);

                    // Analyticsスナップショットを作成
                    $snapshot = $dataSnapshotService->createAnalyticsSnapshot($website, $targetDate);

                    if ($snapshot) {
                        Log::info("スナップショット作成完了", [
                            'website_id' => $website->id,
                            'snapshot_id' => $snapshot->id
                        ]);
                    } else {
                        Log::warning("スナップショット作成をスキップ", [
                            'website_id' => $website->id
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Analytics スナップショット作成エラー", [
                        'website_id' => $website->id,
                        'website_name' => $website->name,
                        'date' => $targetDate->format('Y-m-d'),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Analytics データ取得ジョブ完了');
        } catch (\Exception $e) {
            Log::error("Analytics データ取得ジョブエラー", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Analytics data fetch job failed', [
            'error' => $exception->getMessage()
        ]);
    }
}
