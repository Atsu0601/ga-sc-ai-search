npm install


Dcoker起動
./vendor/bin/sail up -d

シンボリックリンク
docker-compose exec laravel.ga-analytics-saas  php artisan storage:link

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
