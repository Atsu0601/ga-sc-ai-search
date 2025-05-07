<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReportsGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Reports generation started');
        // ジョブをディスパッチ
        \App\Jobs\GenerateAnalysisReport::dispatch();
        $this->info('Reports generation completed');
    }
}
