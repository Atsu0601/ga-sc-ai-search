FROM php:8.2-cli

# タイムゾーンの設定
ENV TZ=Asia/Tokyo

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    cron \
    vim \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip

# PHP拡張のインストール
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# cronジョブの設定ファイルをコピー
COPY crontab /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron

# cronジョブのログディレクトリを作成
RUN mkdir -p /var/log/cron
RUN touch /var/log/cron/laravel.log
RUN chmod 0777 /var/log/cron/laravel.log

# cronサービスを起動するスクリプトをコピー
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]