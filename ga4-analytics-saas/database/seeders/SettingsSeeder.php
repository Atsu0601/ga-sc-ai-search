<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // API設定
        Setting::set('google_client_id', '', 'api', 'Google AnalyticsとSearch Console連携用のClient ID');
        Setting::set('google_client_secret', '', 'api', 'Google AnalyticsとSearch Console連携用のClient Secret');
        Setting::set('openai_api_key', '', 'api', 'AIレポート生成用のOpenAI API Key');
        Setting::set('stripe_key', '', 'api', '決済処理用のStripe公開キー');
        Setting::set('stripe_secret', '', 'api', '決済処理用のStripeシークレットキー');

        // システム設定
        Setting::set('trial_days', 14, 'system', '無料トライアル期間（日数）');
        Setting::set('maintenance_mode', 0, 'system', 'メンテナンスモード');
        Setting::set('debug_mode', 0, 'system', 'デバッグモード');
    }
}
