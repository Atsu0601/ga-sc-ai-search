<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 既存カラムの後に新しいカラムを追加
            $table->string('role')->default('user')->after('remember_token'); // admin, user
            $table->string('subscription_status')->default('trial')->after('role'); // trial, active, inactive
            // ↓ このカラムは削除 (Cashierが追加するため)
            // $table->string('stripe_customer_id')->nullable()->after('subscription_status');
            $table->string('stripe_subscription_id')->nullable()->after('subscription_status'); // Stripeサブスクリプション
            $table->string('plan_name')->default('trial')->after('stripe_subscription_id'); // trial, starter, pro, agency
            $table->integer('website_limit')->default(3)->after('plan_name'); // Webサイト上限数
            // ↓ このカラムは削除 (Cashierが追加するため)
            // $table->timestamp('trial_ends_at')->nullable()->after('website_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 追加したカラムを削除
            $table->dropColumn([
                'role',
                'subscription_status',
                // 'stripe_customer_id', // 削除
                'stripe_subscription_id',
                'plan_name',
                'website_limit',
                // 'trial_ends_at', // 削除
            ]);
        });
    }
};
