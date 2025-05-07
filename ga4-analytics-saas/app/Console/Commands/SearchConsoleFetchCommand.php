<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SearchConsoleFetchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search-console:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data from Search Console';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Search Console data fetch started');
        // ジョブをディスパッチ
        \App\Jobs\SearchConsoleFetchJob::dispatch();
        $this->info('Search Console data fetch completed');
    }
}
