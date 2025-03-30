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
    ];

    /**
     * 日付として扱う属性
     *
     * @var array
     */
    protected $casts = [
        'date_range_start' => 'date',
        'date_range_end' => 'date',
    ];

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
     * レポートタイプを日本語で取得
     */
    public function getReportTypeJapaneseAttribute(): string
    {
        return [
            'executive' => '経営者向け',
            'technical' => '技術者向け',
            'content' => 'コンテンツ向け',
        ][$this->report_type] ?? 'その他';
    }

    /**
     * ステータスを日本語で取得
     */
    public function getStatusJapaneseAttribute(): string
    {
        return [
            'processing' => '処理中',
            'completed' => '完了',
            'failed' => '失敗',
        ][$this->status] ?? '不明';
    }

    /**
     * 処理中かどうかを確認
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * 完了しているかどうかを確認
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 失敗したかどうかを確認
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * レポートの進捗状況を計算（パーセント）
     */
    public function getProgressPercentage(): int
    {
        if ($this->isCompleted()) {
            return 100;
        }

        if ($this->isFailed()) {
            return 0;
        }

        // 処理中の場合は作成時間から推定
        $createdAt = $this->created_at;
        $now = now();
        $diff = $createdAt->diffInMinutes($now);

        // レポート生成は通常5分程度と仮定
        $estimatedCompletionTime = 5;
        $progress = min(95, round(($diff / $estimatedCompletionTime) * 100));

        return $progress;
    }
}
