<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = [
        'report_id',
        'category',
        'severity',
        'content',
    ];

    /**
     * レポートとのリレーション
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(AnalysisReport::class, 'report_id');
    }

    /**
     * カテゴリを日本語で取得
     */
    public function getCategoryJapaneseAttribute(): string
    {
        return [
            'seo' => 'SEO',
            'performance' => 'パフォーマンス',
            'content' => 'コンテンツ',
            'user_experience' => 'ユーザー体験',
        ][$this->category] ?? 'その他';
    }

    /**
     * 重要度を日本語で取得
     */
    public function getSeverityJapaneseAttribute(): string
    {
        return [
            'info' => '情報',
            'warning' => '警告',
            'critical' => '重要',
        ][$this->severity] ?? '不明';
    }

    /**
     * 重要度に応じたCSSクラスを取得
     */
    public function getSeverityCssClassAttribute(): string
    {
        return [
            'info' => 'bg-blue-100 text-blue-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'critical' => 'bg-red-100 text-red-800',
        ][$this->severity] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * カテゴリに応じたアイコンクラスを取得
     */
    public function getCategoryIconClassAttribute(): string
    {
        return [
            'seo' => 'fa-search',
            'performance' => 'fa-tachometer-alt',
            'content' => 'fa-file-alt',
            'user_experience' => 'fa-users',
        ][$this->category] ?? 'fa-info-circle';
    }

    /**
     * 推奨アクションの短縮版（最初の文）を取得
     */
    public function getShortContentAttribute(): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $this->content, 2);
        return $sentences[0] ?? $this->content;
    }
}
