<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisReport extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = [
        'website_id',
        'report_type',
        'date_range_start',
        'date_range_end',
        'status',
        'file_path',
        'data_json'
    ];

    /**
     * 日付として扱う属性
     *
     * @var array
     */
    protected $casts = [
        'date_range_start' => 'datetime',
        'date_range_end' => 'datetime',
        'data_json' => 'array'
    ];

    // ステータス定数
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * ウェブサイトとのリレーション
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * レポートコンポーネントとのリレーション
     */
    public function components(): HasMany
    {
        return $this->hasMany(ReportComponent::class, 'report_id');
    }

    /**
     * AIレコメンデーションとのリレーション
     */
    public function recommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class, 'report_id');
    }

    /**
     * レポートタイプの日本語表示を取得
     */
    public function getReportTypeJapaneseAttribute(): string
    {
        return [
            'business' => '経営者向け',
            'technical' => '技術者向け',
            'content' => 'コンテンツ向け'
        ][$this->report_type] ?? $this->report_type;
    }

    /**
     * ステータスの日本語表示を取得
     */
    public function getStatusJapaneseAttribute(): string
    {
        return [
            self::STATUS_PROCESSING => '生成中',
            self::STATUS_COMPLETED => '完了',
            self::STATUS_FAILED => '失敗'
        ][$this->status] ?? $this->status;
    }

    /**
     * 進捗率を取得（0-100）
     */
    public function getProgressPercentage(): int
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 100;
        }

        if ($this->status === self::STATUS_FAILED) {
            return 0;
        }

        // コンポーネントの生成状況から進捗を計算
        $totalComponents = $this->components()->count();
        if ($totalComponents === 0) {
            return 10; // 初期状態
        }

        $completedComponents = $this->components()
            ->whereNotNull('data_json')
            ->count();

        return min(90, 10 + (int)(($completedComponents / $totalComponents) * 80));
    }
}
