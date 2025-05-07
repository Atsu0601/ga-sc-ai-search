<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SnapshotsCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snapshots:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old snapshots';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Snapshots cleanup started');
        // ジョブをディスパッチ
        \App\Jobs\SnapshotsCleanupJob::dispatch();
        $this->info('Snapshots cleanup completed');
    }
}
