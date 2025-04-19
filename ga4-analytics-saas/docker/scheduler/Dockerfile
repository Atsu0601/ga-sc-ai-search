FROM php:8.2-cli

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    cron \
    && rm -rf /var/lib/apt/lists/*

# PHPの拡張機能インストール
RUN docker-php-ext-install pdo pdo_mysql

# cronの設定ファイルを作成
RUN echo "* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler

# cronジョブを登録
RUN crontab /etc/cron.d/laravel-scheduler

# 作業ディレクトリの設定
WORKDIR /var/www/html

# cronをフォアグラウンドで実行
CMD ["cron", "-f"]
