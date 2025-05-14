<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Services\DataSnapshotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AnalyticsFetchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch analytics data from GA4';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(DataSnapshotService $dataSnapshotService)
    {
        $this->info('Analytics データ取得を開始します...');
        Log::info('Analytics データ取得コマンドを開始');

        try {
            // 昨日の日付
            $targetDate = Carbon::yesterday();

            // アクティブなウェブサイトを取得
            $websites = Website::where('status', 'active')
                ->has('analyticsAccount')
                ->get();

            $this->info("処理対象のウェブサイト: {$websites->count()}件");

            $successCount = 0;
            $errorCount = 0;

            // 各ウェブサイトのスナップショットを作成
            foreach ($websites as $website) {
                try {
                    $this->info("ウェブサイト「{$website->name}」の処理を開始...");
                    Log::info("Analytics スナップショット作成開始", [
                        'website_id' => $website->id,
                        'website_name' => $website->name,
                        'date' => $targetDate->format('Y-m-d')
                    ]);

                    // Analyticsスナップショットを作成
                    $snapshot = $dataSnapshotService->createAnalyticsSnapshot($website, $targetDate);

                    if ($snapshot) {
                        $this->info("→ スナップショット作成完了 (ID: {$snapshot->id})");
                        $successCount++;
                    } else {
                        $this->warn("→ スナップショット作成をスキップしました");
                    }
                } catch (\Exception $e) {
                    $this->error("→ エラー: {$e->getMessage()}");
                    Log::error("Analytics スナップショット作成エラー", [
                        'website_id' => $website->id,
                        'website_name' => $website->name,
                        'date' => $targetDate->format('Y-m-d'),
                        'error' => $e->getMessage()
                    ]);
                    $errorCount++;
                }
            }

            $this->info("処理完了: 成功={$successCount}, エラー={$errorCount}");
            return 0;
        } catch (\Exception $e) {
            $this->error("コマンド実行中にエラーが発生しました: {$e->getMessage()}");
            Log::error("Analytics データ取得コマンドエラー", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
