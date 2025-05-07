<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureCompanyInfoExists;
use App\Http\Middleware\CheckAdminRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\AnalyticsFetchCommand::class,
        \App\Console\Commands\SearchConsoleFetchCommand::class,
        \App\Console\Commands\ReportsGenerateCommand::class,
        \App\Console\Commands\SnapshotsCleanupCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // 必要なカスタムミドルウェアのエイリアスのみ追加
        $middleware->alias([
            'company.exists' => EnsureCompanyInfoExists::class,
            'admin' => CheckAdminRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
