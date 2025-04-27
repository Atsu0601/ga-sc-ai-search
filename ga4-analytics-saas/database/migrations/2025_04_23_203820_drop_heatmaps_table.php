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
        //
        Schema::dropIfExists('heatmaps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        $table->id();
        $table->foreignId('website_id')->constrained()->onDelete('cascade');
        $table->string('page_url');
        $table->string('type');
        $table->datetime('date_range_start');
        $table->datetime('date_range_end');
        $table->string('screenshot_path')->nullable();
        $table->json('data_json')->nullable();
        $table->timestamps();
        $table->softDeletes();
    }
};
