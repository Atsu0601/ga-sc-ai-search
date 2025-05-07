<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Website;
use App\Services\SearchConsoleService;
use Illuminate\Support\Facades\Log;

class SearchConsoleFetchJob implements ShouldQueue
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
     * Execute the job.
     */
    public function handle(SearchConsoleService $searchConsoleService): void
    {
        try {
            Log::info('Search Console data fetch started');

            // Search Consoleアカウントを持つウェブサイトを取得
            $websites = Website::whereNotNull('search_console_account')->get();

            foreach ($websites as $website) {
                try {
                    // 各ウェブサイトのSearch Consoleデータを取得
                    $searchConsoleService->fetchData($website);

                    Log::info('Search Console data fetched successfully', [
                        'website_id' => $website->id,
                        'website_name' => $website->name
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to fetch Search Console data for website', [
                        'website_id' => $website->id,
                        'website_name' => $website->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Search Console data fetch completed');
        } catch (\Exception $e) {
            Log::error('Search Console data fetch job failed', [
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
        Log::error('Search Console data fetch job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
