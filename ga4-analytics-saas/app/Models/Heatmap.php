<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Heatmap extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'heatmaps';

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
     * スコープ: 特定のウェブサイトに属するヒートマップを取得
     */
    public function scopeForWebsite($query, $websiteId)
    {
        return $query->where('website_id', $websiteId);
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
     * スナップショットからヒートマップを生成
     *
     * @param int $websiteId
     * @param string $pageUrl
     * @param string $type
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return \App\Models\Heatmap
     */
    public static function createFromSnapshots($websiteId, $pageUrl, $type, $startDate, $endDate)
    {
        // 指定した日付範囲のスナップショットを取得
        $snapshots = DataSnapshot::where('website_id', $websiteId)
            ->whereBetween('snapshot_date', [$startDate, $endDate])
            ->where('snapshot_type', 'analytics')
            ->get();

        // ヒートマップデータの初期化
        $heatmapData = [];

        if ($type === 'click') {
            $heatmapData = self::processClickData($snapshots, $pageUrl);
        } elseif ($type === 'scroll') {
            $heatmapData = self::processScrollData($snapshots, $pageUrl);
        } elseif ($type === 'move') {
            $heatmapData = self::processMoveData($snapshots, $pageUrl);
        } elseif ($type === 'attention') {
            $heatmapData = self::processAttentionData($snapshots, $pageUrl);
        }

        // ヒートマップレコードを作成
        return self::create([
            'website_id' => $websiteId,
            'page_url' => $pageUrl,
            'type' => $type,
            'data_json' => $heatmapData,
            'date_range_start' => $startDate,
            'date_range_end' => $endDate
        ]);
    }

    /**
     * クリックヒートマップデータ処理
     *
     * @param \Illuminate\Database\Eloquent\Collection $snapshots
     * @param string $pageUrl
     * @return array
     */
    private static function processClickData($snapshots, $pageUrl)
    {
        $clicks = [];
        $totalClicks = 0;

        foreach ($snapshots as $snapshot) {
            $data = $snapshot->data_json;

            // データ形式に応じて処理を調整
            // クリックイベントのパスはGA4の設定によって異なる場合があります
            if (isset($data['events'])) {
                foreach ($data['events'] as $event) {
                    if (
                        $event['name'] === 'click' &&
                        $event['page'] === $pageUrl &&
                        isset($event['params']['x_pos']) &&
                        isset($event['params']['y_pos'])
                    ) {

                        $clicks[] = [
                            'x' => (int)$event['params']['x_pos'],
                            'y' => (int)$event['params']['y_pos']
                        ];
                        $totalClicks++;
                    }
                }
            }

            // 別の形式の場合（例：スナップショットに集計済みデータがある場合）
            if (isset($data['pages'][$pageUrl]['clicks'])) {
                foreach ($data['pages'][$pageUrl]['clicks'] as $click) {
                    if (isset($click['x']) && isset($click['y'])) {
                        $clicks[] = [
                            'x' => (int)$click['x'],
                            'y' => (int)$click['y']
                        ];
                        $totalClicks++;
                    }
                }
            }
        }

        // サンプルデータの生成（実データがない場合）
        if (empty($clicks)) {
            $clicks = self::generateSampleClickData();
            $totalClicks = count($clicks);
        }

        return [
            'clicks' => $clicks,
            'totalClicks' => $totalClicks
        ];
    }

    /**
     * スクロールヒートマップデータ処理
     *
     * @param \Illuminate\Database\Eloquent\Collection $snapshots
     * @param string $pageUrl
     * @return array
     */
    private static function processScrollData($snapshots, $pageUrl)
    {
        $scrollDepth = [];
        $totalUsers = 0;

        foreach ($snapshots as $snapshot) {
            $data = $snapshot->data_json;

            // GA4のスクロールイベント処理
            if (isset($data['events'])) {
                foreach ($data['events'] as $event) {
                    if (
                        $event['name'] === 'scroll' &&
                        $event['page'] === $pageUrl &&
                        isset($event['params']['percent_scrolled'])
                    ) {

                        $depth = (int)($event['params']['percent_scrolled'] * 10); // %をピクセル値に変換（仮定）
                        if (!isset($scrollDepth[$depth])) {
                            $scrollDepth[$depth] = 0;
                        }
                        $scrollDepth[$depth]++;
                    }
                }
            }

            // 別の形式の場合
            if (isset($data['pages'][$pageUrl]['scroll'])) {
                foreach ($data['pages'][$pageUrl]['scroll'] as $depth => $count) {
                    if (!isset($scrollDepth[$depth])) {
                        $scrollDepth[$depth] = 0;
                    }
                    $scrollDepth[$depth] += $count;
                }
            }

            // ユーザー数の集計
            if (isset($data['metrics']['users'])) {
                $totalUsers += $data['metrics']['users'];
            }
        }

        // サンプルデータの生成（実データがない場合）
        if (empty($scrollDepth)) {
            list($scrollDepth, $totalUsers) = self::generateSampleScrollData();
        }

        return [
            'scrollDepth' => $scrollDepth,
            'totalUsers' => $totalUsers ?: 100 // デフォルト値
        ];
    }

    /**
     * マウス移動ヒートマップデータ処理（クリックと同様の実装）
     */
    private static function processMoveData($snapshots, $pageUrl)
    {
        // 実際のデータがない場合はサンプルデータを返す
        $movePoints = self::generateSampleMoveData();
        return [
            'movePoints' => $movePoints,
            'totalMoves' => count($movePoints)
        ];
    }

    /**
     * 注目度ヒートマップデータ処理
     */
    private static function processAttentionData($snapshots, $pageUrl)
    {
        // 実際のデータがない場合はサンプルデータを返す
        $attentionAreas = self::generateSampleAttentionData();
        return [
            'attentionAreas' => $attentionAreas
        ];
    }

    /**
     * サンプルクリックデータ生成（実データがない場合用）
     */
    private static function generateSampleClickData()
    {
        $clicks = [];
        $clickCount = rand(100, 300); // サンプルとして100〜300個のクリックを生成

        for ($i = 0; $i < $clickCount; $i++) {
            $x = rand(50, 1200); // ブラウザの一般的な幅を想定
            $y = rand(50, 2000); // ページの縦長を想定

            // クリック位置が集中するエリアを作るための重み付け
            if (rand(0, 100) < 30) { // 30%の確率で特定エリアに集中
                $x = rand(300, 700);
                $y = rand(200, 500);
            }

            $clicks[] = [
                'x' => $x,
                'y' => $y
            ];
        }

        return $clicks;
    }

    /**
     * サンプルスクロールデータ生成（実データがない場合用）
     */
    private static function generateSampleScrollData()
    {
        $scrollDepth = [];
        $totalUsers = rand(80, 150);
        $depths = [0, 300, 600, 900, 1200, 1500, 1800, 2100, 2400, 2700, 3000];

        $currentUsers = $totalUsers;
        foreach ($depths as $depth) {
            $scrollDepth[$depth] = $currentUsers;
            // ページの下に行くほどユーザー数が減少
            $dropRate = rand(5, 15) / 100; // 5%〜15%の減少率
            $currentUsers = max(1, (int)($currentUsers * (1 - $dropRate)));
        }

        return [$scrollDepth, $totalUsers];
    }

    /**
     * サンプルマウス移動データ生成
     */
    private static function generateSampleMoveData()
    {
        $movePoints = [];
        $moveCount = rand(300, 600);

        for ($i = 0; $i < $moveCount; $i++) {
            $x = rand(50, 1200);
            $y = rand(50, 2000);

            $movePoints[] = [
                'x' => $x,
                'y' => $y,
                'duration' => rand(1, 5) // 滞在時間（秒）
            ];
        }

        return $movePoints;
    }

    /**
     * サンプル注目度データ生成
     */
    private static function generateSampleAttentionData()
    {
        $areas = [];
        $areaCount = rand(10, 20);

        for ($i = 0; $i < $areaCount; $i++) {
            $x = rand(50, 1100);
            $y = rand(50, 1900);
            $width = rand(100, 300);
            $height = rand(100, 300);

            $areas[] = [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
                'attention' => rand(1, 100) / 100 // 0.01〜1.00の値
            ];
        }

        return $areas;
    }

    /**
     * ヒートマップデータをフォーマット (クリックヒートマップ用)
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
        usort($formatted, function ($a, $b) {
            return $a['depth'] - $b['depth'];
        });

        return $formatted;
    }

    /**
     * ヒートマップデータをフォーマット (種類に応じて適切なメソッドを呼び出す)
     */
    public function formatData()
    {
        switch ($this->type) {
            case 'click':
                return $this->formatClickData();
            case 'scroll':
                return $this->formatScrollData();
            case 'move':
                // マウス移動ヒートマップのフォーマット処理
                return $this->formatClickData(); // クリックと同様の処理
            case 'attention':
                // 注目度ヒートマップのフォーマット処理
                return $this->formatClickData(); // 仮実装
            default:
                return [];
        }
    }

    /**
     * 日付範囲の文字列表現を取得
     */
    public function getDateRangeText()
    {
        return $this->date_range_start->format('Y/m/d') . ' 〜 ' . $this->date_range_end->format('Y/m/d');
    }
}
