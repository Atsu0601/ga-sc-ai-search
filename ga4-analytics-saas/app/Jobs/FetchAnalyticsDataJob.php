<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Website;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class FetchAnalyticsDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;

    public function handle()
    {
        Artisan::call('analytics:fetch-data');
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Analytics data fetch job failed', [
            'error' => $exception->getMessage()
        ]);
    }
}
