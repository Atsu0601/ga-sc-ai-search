<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // スタータープラン
        Plan::create([
            'name' => 'starter',
            'display_name' => 'スタータープラン',
            'description' => 'Webサイト登録 最大3サイト、月間レポート数 10件、メールサポート',
            'price' => 5000,
            'billing_period' => 'monthly',
            'website_limit' => 3,
            'is_active' => true,
            'is_featured' => false,
        ]);

        // プロプラン
        Plan::create([
            'name' => 'pro',
            'display_name' => 'プロプラン',
            'description' => 'Webサイト登録 最大10サイト、月間レポート数 無制限、優先サポート（電話・メール）、カスタムレポート機能',
            'price' => 12000,
            'billing_period' => 'monthly',
            'website_limit' => 10,
            'is_active' => true,
            'is_featured' => true,
        ]);

        // エージェンシープラン
        Plan::create([
            'name' => 'agency',
            'display_name' => 'エージェンシープラン',
            'description' => 'Webサイト登録 最大30サイト、月間レポート数 無制限、専任サポート担当者、ホワイトラベル対応',
            'price' => 30000,
            'billing_period' => 'monthly',
            'website_limit' => 30,
            'is_active' => true,
            'is_featured' => false,
        ]);
    }
}
