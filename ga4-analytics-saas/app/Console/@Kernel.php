<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * コンソールアプリケーションのコマンドを登録
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
