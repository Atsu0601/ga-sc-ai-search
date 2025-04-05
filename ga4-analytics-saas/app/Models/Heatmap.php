<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Heatmap extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * テーブル名
     *
     * @var string
     */
    // protected $table = 'heatmaps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'website_id',
        'page_url',
        'type',
        'data_json',
        'date_range_start',
        'date_range_end'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data_json' => 'json',
        'date_range_start' => 'date',
        'date_range_end' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * このヒートマップが属するウェブサイト
     */
    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * ヒートマップの種類一覧
     *
     * @return array
     */
    public static function getTypes()
    {
        return [
            'click' => 'クリックヒートマップ',
            'move' => 'マウス移動ヒートマップ',
            'scroll' => 'スクロールヒートマップ',
            'attention' => '注目度ヒートマップ'
        ];
    }

    /**
     * データを取得
     *
     * @return array
     */
    public function getData()
    {
        return $this->data_json ?? [];
    }

    /**
     * ヒートマップデータをフォーマット (クリックヒートマップ用)
     *
     * @return array
     */
    public function formatClickData()
    {
        $data = $this->getData();
        $formatted = [];

        if (empty($data) || !isset($data['clicks'])) {
            return [];
        }

        // クリック位置データをグリッド化
        $gridSize = 10; // ピクセル単位のグリッドサイズ
        $grid = [];

        foreach ($data['clicks'] as $click) {
            if (isset($click['x']) && isset($click['y'])) {
                // グリッドセルにクリックを集約
                $gridX = floor($click['x'] / $gridSize);
                $gridY = floor($click['y'] / $gridSize);
                $key = "{$gridX}_{$gridY}";

                if (!isset($grid[$key])) {
                    $grid[$key] = [
                        'x' => $gridX * $gridSize,
                        'y' => $gridY * $gridSize,
                        'count' => 0
                    ];
                }

                $grid[$key]['count']++;
            }
        }

        // 最大値を特定してヒートマップ強度を正規化
        $maxCount = 1;
        foreach ($grid as $cell) {
            if ($cell['count'] > $maxCount) {
                $maxCount = $cell['count'];
            }
        }

        // 最終フォーマット
        foreach ($grid as $cell) {
            $formatted[] = [
                'x' => $cell['x'],
                'y' => $cell['y'],
                'value' => $cell['count'],
                'intensity' => $cell['count'] / $maxCount
            ];
        }

        return $formatted;
    }

    /**
     * ヒートマップデータをフォーマット (スクロールヒートマップ用)
     *
     * @return array
     */
    public function formatScrollData()
    {
        $data = $this->getData();
        $formatted = [];

        if (empty($data) || !isset($data['scrollDepth'])) {
            return [];
        }

        // スクロール深度データを処理
        $totalUsers = $data['totalUsers'] ?? 1;

        foreach ($data['scrollDepth'] as $depth => $count) {
            $formatted[] = [
                'depth' => intval($depth),
                'count' => $count,
                'percentage' => ($count / $totalUsers) * 100
            ];
        }

        // 深度で並べ替え
        usort($formatted, function($a, $b) {
            return $a['depth'] - $b['depth'];
        });

        return $formatted;
    }

    /**
     * ヒートマップデータをフォーマット (種類に応じて適切なメソッドを呼び出す)
     *
     * @return array
     */
    public function formatData()
    {
        switch ($this->type) {
            case 'click':
                return $this->formatClickData();
            case 'scroll':
                return $this->formatScrollData();
            case 'move':
                // マウス移動ヒートマップのフォーマット処理をここに実装
                return $this->formatClickData(); // 仮実装：クリックと同様の処理
            case 'attention':
                // 注目度ヒートマップのフォーマット処理をここに実装
                return $this->formatClickData(); // 仮実装：クリックと同様の処理
            default:
                return [];
        }
    }

    /**
     * 日付範囲の文字列表現を取得
     *
     * @return string
     */
    public function getDateRangeText()
    {
        return $this->date_range_start->format('Y/m/d') . ' 〜 ' . $this->date_range_end->format('Y/m/d');
    }
}
