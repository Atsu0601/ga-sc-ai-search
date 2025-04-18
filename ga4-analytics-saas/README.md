npm install


Dcoker起動
./vendor/bin/sail up -d

シンボリックリンク
docker-compose exec laravel.ga-analytics-saas php artisan storage:link

Tailwind CSS のインストール
docker-compose exec laravel.ga-analytics-saas npm install -D tailwindcss postcss autoprefixer
docker-compose exec laravel.ga-analytics-saas npm tailwindcss init -p

マイグレーションファイル作成
docker-compose exec laravel.ga-analytics-saas php artisan make:migration create_テーブル名_table
docker-compose exec laravel.ga-analytics-saas php artisan migrate

最後に実行されたバッチのすべてのマイグレーションをロールバック
php artisan migrate:rollback

直前のマイグレーションをロールバック
php artisan migrate:rollback --step=1

すべてのマイグレーションをロールバックし、DBを空にする
php artisan migrate:reset

データベースをリセットし、すべてのマイグレーションを再実行
php artisan migrate:refresh

データベースを空にし、すべてのマイグレーションを再実行
php artisan migrate:fresh

モデル作成
docker-compose exec laravel.ga-analytics-saas php artisan make:model モデル名
docker-compose exec laravel.ga-analytics-saas php artisan make:model Transaction

コントローラー作成
docker-compose exec laravel.ga-analytics-saas php artisan make:controller HomeController

インストール
docker-compose exec laravel.ga-analytics-saas composer require laravel-lang/common --dev
docker-compose exec laravel.ga-analytics-saas composer require laravel/breeze --dev
docker-compose exec laravel.ga-analytics-saas php artisan breeze:install blade


# GA4・Search Console分析AIシステム

## バッチ処理の設定（Docker環境）

### 1. 環境構築

```bash
# プロジェクトディレクトリに移動
cd ga4-analytics-saas

# コンテナのビルドと起動
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
```

### 2. データベースのセットアップ

```bash
# マイグレーションの実行
./vendor/bin/sail artisan migrate

# キューテーブルの作成（必要な場合）
./vendor/bin/sail artisan queue:table
./vendor/bin/sail artisan migrate
```

### 3. スケジュールタスクの確認と実行

```bash
# 登録されているスケジュールタスクの一覧表示
./vendor/bin/sail artisan schedule:list

# スケジュールタスクのテスト実行
./vendor/bin/sail artisan schedule:test

# 手動でのデータ取得実行（テスト用）
./vendor/bin/sail artisan analytics:fetch-data
```

### 4. ログの確認

```bash
# アナリティクスデータ取得のログ
./vendor/bin/sail exec laravel.test tail -f storage/logs/analytics-fetch.log

# Laravelのログ
./vendor/bin/sail exec laravel.test tail -f storage/logs/laravel.log
```

### 5. トラブルシューティング

```bash
# スケジューラーのステータス確認
./vendor/bin/sail exec scheduler ps aux | grep cron

# キャッシュのクリア
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear

# ログの権限設定
./vendor/bin/sail root chmod -R 777 storage/logs
```

### 注意事項

- デフォルトで毎日午前3時にデータ取得を実行
- ログは`storage/logs`ディレクトリに保存
- コンテナ再起動後もcronは自動的に起動
- 実行時間の変更は`app/Console/Kernel.php`の`schedule`メソッドで設定可能

### 開発時のTips

1. スケジュール実行時間の変更：
```php
// app/Console/Kernel.php
$schedule->command('analytics:fetch-data')
    ->dailyAt('03:00') // 時間を変更
```

2. 即時実行（デバッグ用）：
```bash
./vendor/bin/sail artisan analytics:fetch-data
```

3. スケジュール一覧の確認：
```bash
./vendor/bin/sail artisan schedule:list
```
```

これにより、Docker環境でcronを使用したスケジュールタスクの実行が可能になります。supervisorと比べてシンプルな構成で、開発環境での運用が容易になります。