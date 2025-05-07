<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
    public function handle()
    {
        $this->info('Analytics data fetch started');

        // ジョブをディスパッチ
        \App\Jobs\FetchAnalyticsDataJob::dispatch();

        $this->info('Analytics data fetch completed');
    }
}
