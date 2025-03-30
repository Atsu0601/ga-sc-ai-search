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
        // アナリティクスアカウント（ANALYTICS_ACCOUNTS）テーブル
        Schema::create('analytics_related_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('property_id');
            $table->string('view_id')->nullable();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Search Consoleアカウント（SEARCH_CONSOLE_ACCOUNTS）テーブル
        Schema::create('search_console_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('site_url');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 分析レポート（ANALYSIS_REPORTS）テーブル
        Schema::create('analysis_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('report_type');
            $table->date('date_range_start');
            $table->date('date_range_end');
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // レポートコンポーネント（REPORT_COMPONENTS）テーブル
        Schema::create('report_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('analysis_reports')->onDelete('cascade');
            $table->string('component_type');
            $table->string('title');
            $table->json('data_json');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // AI推奨（AI_RECOMMENDATIONS）テーブル
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('analysis_reports')->onDelete('cascade');
            $table->string('category');
            $table->string('severity');
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();
        });

        // ヒートマップ（HEATMAPS）テーブル
        Schema::create('heatmaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('page_url');
            $table->string('type');
            $table->json('data_json');
            $table->date('date_range_start');
            $table->date('date_range_end');
            $table->timestamps();
            $table->softDeletes();
        });

        // データスナップショット（DATA_SNAPSHOTS）テーブル
        Schema::create('data_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('snapshot_type');
            $table->json('data_json');
            $table->date('snapshot_date');
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_related_tables');
        Schema::dropIfExists('search_console_accounts');
        Schema::dropIfExists('analysis_reports');
        Schema::dropIfExists('report_components');
        Schema::dropIfExists('ai_recommendations');
        Schema::dropIfExists('heatmaps');
        Schema::dropIfExists('data_snapshots');
    }
};
