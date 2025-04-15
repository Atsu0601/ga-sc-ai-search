<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportComponent extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = [
        'report_id',
        'component_type',
        'title',
        'data_json',
        'order',
    ];

    /**
     * JSON型にキャストする属性
     *
     * @var array
     */
    protected $casts = [
        'data_json' => 'array',
    ];

    /**
     * レポートとのリレーション
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(AnalysisReport::class, 'report_id');
    }

    /**
     * コンポーネントタイプを日本語で取得
     */
    public function getComponentTypeJapaneseAttribute(): string
    {
        return [
            'chart' => 'グラフ',
            'table' => '表',
            'text' => 'テキスト',
            'heatmap' => 'ヒートマップ',
            'metrics' => '基本指標',
            'devices' => 'デバイス分析',
            'sources' => 'トラフィックソース',
            'pages' => 'ページ分析'
        ][$this->component_type] ?? 'その他';
    }

    /**
     * チャートの場合のタイプを取得
     */
    public function getChartTypeAttribute(): ?string
    {
        if ($this->component_type !== 'chart') {
            return null;
        }

        return $this->data_json['type'] ?? 'line';
    }

    /**
     * データの整形済み配列を取得
     */
    public function getFormattedDataAttribute(): array
    {
        switch ($this->component_type) {
            case 'chart':
                return $this->formatChartData();

            case 'table':
                return $this->formatTableData();

            case 'heatmap':
                return $this->formatHeatmapData();

            default:
                return $this->data_json ?? [];
        }
    }

    /**
     * チャートデータの整形
     */
    private function formatChartData(): array
    {
        $data = $this->data_json;

        // Chart.jsに適した形式に変換
        $formatted = [
            'type' => $data['type'] ?? 'line',
            'data' => [
                'labels' => [],
                'datasets' => [],
            ],
            'options' => $data['options'] ?? [],
        ];

        // データセットの変換ロジック
        // （実際のデータ構造に応じて実装）

        return $formatted;
    }

    /**
     * テーブルデータの整形
     */
    private function formatTableData(): array
    {
        return $this->data_json ?? [];
    }

    /**
     * ヒートマップデータの整形
     */
    private function formatHeatmapData(): array
    {
        return $this->data_json ?? [];
    }

    public function getFormattedValueAttribute($key)
    {
        if (isset($this->data_json[$key])) {
            $value = $this->data_json[$key];
            if (is_numeric($value)) {
                return number_format($value);
            }
            return $value;
        }
        return null;
    }
}
