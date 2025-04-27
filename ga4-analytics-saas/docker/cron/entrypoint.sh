#!/bin/bash

# cronデーモンを起動
cron

# ログを表示し続ける（コンテナを実行し続けるため）
tail -f /var/log/cron/laravel.log
