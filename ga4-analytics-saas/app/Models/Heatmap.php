<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

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
        'date_range_start',
        'date_range_end',
        'screenshot_path',
        'data_json'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date_range_start' => 'datetime',
        'date_range_end' => 'datetime',
        'data_json' => 'array'
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
            'scroll' => 'スクロールヒートマップ'
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
        $heatmap = new self();
        $heatmap->website_id = $websiteId;
        $heatmap->page_url = $pageUrl;
        $heatmap->type = $type;
        $heatmap->date_range_start = $startDate;
        $heatmap->date_range_end = $endDate;

        try {
            // スクリーンショットの取得と保存
            $screenshotPath = self::captureScreenshot($pageUrl);
            if ($screenshotPath) {
                $heatmap->screenshot_path = $screenshotPath;
                Log::info('Screenshot captured successfully', [
                    'path' => $screenshotPath,
                    'url' => $pageUrl
                ]);
            }

            // ヒートマップデータの生成
            $heatmap->data_json = self::generateHeatmapData($websiteId, $pageUrl, $type, $startDate, $endDate);

            $heatmap->save();
            return $heatmap;
        } catch (\Exception $e) {
            Log::error('Failed to create heatmap', [
                'error' => $e->getMessage(),
                'url' => $pageUrl,
                'type' => $type
            ]);
            throw $e;
        }
    }

    private static function captureScreenshot($url)
    {
        try {
            // スクリーンショット保存用のディレクトリ作成
            $directory = storage_path('app/public/heatmaps');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // 一意のファイル名を生成
            $filename = 'heatmaps/' . uniqid() . '.png';
            $fullPath = storage_path('app/public/' . $filename);

            // Browsershotを使用してスクリーンショットを取得
            Browsershot::url($url)
                ->setNodeBinary(env('NODE_BINARY', '/usr/local/bin/node'))
                ->setNpmBinary(env('NPM_BINARY', '/usr/local/bin/npm'))
                ->setChromePath(env('CHROME_BINARY', '/usr/bin/google-chrome'))
                ->windowSize(1920, 1080)
                ->fullPage()
                ->waitUntilNetworkIdle()
                ->timeout(30000) // 30秒のタイムアウト
                ->save($fullPath);

            Log::info('Screenshot captured', [
                'url' => $url,
                'path' => $filename
            ]);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Screenshot capture failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
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

    /**
     * スナップショットからヒートマップデータを生成
     *
     * @param int $websiteId
     * @param string $pageUrl
     * @param string $type
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private static function generateHeatmapData($websiteId, $pageUrl, $type, $startDate, $endDate)
    {
        try {
            // データスナップショットから該当期間のデータを取得
            $snapshots = DataSnapshot::where('website_id', $websiteId)
                ->where('snapshot_type', 'analytics')
                ->whereBetween('snapshot_date', [$startDate, $endDate])
                ->get();

            $heatmapData = [];

            if ($type === 'click') {
                $heatmapData = [
                    'clicks' => [],
                    'totalClicks' => 0
                ];

                foreach ($snapshots as $snapshot) {
                    $data = $snapshot->data_json;

                    // クリックイベントのデータを抽出
                    if (isset($data['events']) && is_array($data['events'])) {
                        foreach ($data['events'] as $event) {
                            if (
                                isset($event['page']) &&
                                $event['page'] === $pageUrl &&
                                isset($event['click_x']) &&
                                isset($event['click_y'])
                            ) {
                                $heatmapData['clicks'][] = [
                                    'x' => $event['click_x'],
                                    'y' => $event['click_y'],
                                    'timestamp' => $event['timestamp'] ?? null
                                ];
                                $heatmapData['totalClicks']++;
                            }
                        }
                    }
                }
            } elseif ($type === 'scroll') {
                $heatmapData = [
                    'scrollDepth' => [],
                    'totalUsers' => 0
                ];

                foreach ($snapshots as $snapshot) {
                    $data = $snapshot->data_json;

                    // スクロール深度データを抽出
                    if (isset($data['pages'][$pageUrl]['scroll_depth'])) {
                        foreach ($data['pages'][$pageUrl]['scroll_depth'] as $depth => $count) {
                            if (!isset($heatmapData['scrollDepth'][$depth])) {
                                $heatmapData['scrollDepth'][$depth] = 0;
                            }
                            $heatmapData['scrollDepth'][$depth] += $count;
                        }
                    }

                    // 総ユーザー数を更新
                    if (isset($data['pages'][$pageUrl]['users'])) {
                        $heatmapData['totalUsers'] += $data['pages'][$pageUrl]['users'];
                    }
                }
            }

            Log::info('Heatmap data generated successfully', [
                'website_id' => $websiteId,
                'page_url' => $pageUrl,
                'type' => $type,
                'data_points' => $type === 'click' ?
                    count($heatmapData['clicks']) :
                    count($heatmapData['scrollDepth'])
            ]);

            return $heatmapData;
        } catch (\Exception $e) {
            Log::error('Failed to generate heatmap data', [
                'error' => $e->getMessage(),
                'website_id' => $websiteId,
                'page_url' => $pageUrl,
                'type' => $type
            ]);

            // エラーの場合は空のデータ構造を返す
            return $type === 'click' ?
                ['clicks' => [], 'totalClicks' => 0] :
                ['scrollDepth' => [], 'totalUsers' => 0];
        }
    }
}
