<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Notifications\ScheduleTaskFailed;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Analytics データ取得タスク
Schedule::command('analytics:fetch')->dailyAt('00:00')
    ->name('analytics_fetch')
    ->appendOutputTo(storage_path('logs/analytics-fetch.log'))
    ->onFailure(function ($event) {
        Log::error('Analytics fetch failed', [
            'task' => 'analytics:fetch',
            'output' => $event->output()
        ]);

        // 管理者へメール通知
        Notification::route('mail', [
            config('mail.admin_email') => '管理者',
            config('mail.tech_lead_email') => '技術リーダー'
        ])->notify(new ScheduleTaskFailed('Analytics データ取得', $event->output()));
    });

// Search Console データ取得タスク
Schedule::command('search-console:fetch')->dailyAt('02:00')
    ->name('search_console_fetch')
    ->appendOutputTo(storage_path('logs/search-console-fetch.log'))
    ->onFailure(function ($event) {
        Log::error('Search Console fetch failed', [
            'task' => 'search-console:fetch',
            'output' => $event->output()
        ]);

        Notification::route('mail', [
            config('mail.admin_email') => '管理者',
            config('mail.tech_lead_email') => '技術リーダー'
        ])->notify(new ScheduleTaskFailed('Search Console データ取得', $event->output()));
    });

// レポート生成タスク
Schedule::command('reports:generate')->dailyAt('01:00')
    ->name('reports_generate')
    ->appendOutputTo(storage_path('logs/reports-generate.log'))
    ->onFailure(function ($event) {
        Log::error('Reports generation failed', [
            'task' => 'reports:generate',
            'output' => $event->output()
        ]);

        Notification::route('mail', [
            config('mail.admin_email') => '管理者',
            config('mail.tech_lead_email') => '技術リーダー'
        ])->notify(new ScheduleTaskFailed('レポート生成', $event->output()));
    });

// データスナップショットのクリーンアップ
Schedule::command('snapshots:cleanup')->weekly()
    ->name('cleanup_old_snapshots')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/snapshots-cleanup.log'));

// キューワーカーの監視
Schedule::command('queue:monitor default')
    ->everyFiveMinutes()
    ->name('monitor_queue_workers')
    ->withoutOverlapping()
    ->runInBackground();

// 失敗したジョブのクリーンアップ
Schedule::command('queue:prune-failed')
    ->daily()
    ->name('prune_failed_jobs')
    ->withoutOverlapping();

// キャッシュのクリーンアップ
Schedule::command('cache:prune-stale-tags')
    ->daily()
    ->name('prune_stale_cache_tags')
    ->withoutOverlapping();
