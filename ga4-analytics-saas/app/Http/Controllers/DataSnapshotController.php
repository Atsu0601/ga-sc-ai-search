<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\DataSnapshot;
use App\Services\DataSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DataSnapshotController extends Controller
{
    use AuthorizesRequests;

    protected $dataSnapshotService;

    /**
     * コンストラクタ
     */
    public function __construct(DataSnapshotService $dataSnapshotService)
    {
        $this->dataSnapshotService = $dataSnapshotService;
    }

    /**
     * 指定したWebサイトのスナップショット一覧を表示
     */
    public function index(Website $website)
    {
        // 所有者確認
        $this->authorize('view', $website);

        // 過去30日分のスナップショットを取得
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $analyticsSnapshots = $this->dataSnapshotService->getSnapshotsByDateRange(
            $website, 'analytics', $startDate, $endDate
        );

        $searchConsoleSnapshots = $this->dataSnapshotService->getSnapshotsByDateRange(
            $website, 'search_console', $startDate, $endDate
        );

        return view('snapshots.index', compact(
            'website', 'analyticsSnapshots', 'searchConsoleSnapshots'
        ));
    }

    /**
     * スナップショットの詳細を表示
     */
    public function show(Website $website, $id)
    {
        // 所有者確認
        $this->authorize('view', $website);

        $snapshot = DataSnapshot::findOrFail($id);

        // スナップショットが指定のウェブサイトのものか確認
        if ($snapshot->website_id !== $website->id) {
            abort(403, 'このスナップショットへのアクセス権限がありません。');
        }

        return view('snapshots.show', compact('website', 'snapshot'));
    }

    /**
     * 最新のスナップショットを作成
     */
    public function create(Website $website)
    {
        // 所有者確認
        $this->authorize('update', $website);

        // ウェブサイトがアクティブであることを確認
        if ($website->status !== 'active') {
            return redirect()->route('websites.show', $website->id)
                             ->with('error', 'スナップショットを作成するには、Google AnalyticsとSearch Consoleの接続が必要です。');
        }

        // 昨日のデータをスナップショット
        $date = Carbon::yesterday();

        try {
            $result = $this->dataSnapshotService->createAllSnapshots($website, $date);

            if ($result) {
                return redirect()->route('snapshots.index', $website->id)
                                 ->with('success', $date->format('Y年m月d日') . 'のデータスナップショットを作成しました。');
            } else {
                return redirect()->route('snapshots.index', $website->id)
                                 ->with('error', 'データスナップショットの作成中にエラーが発生しました。');
            }
        } catch (\Exception $e) {
            return redirect()->route('snapshots.index', $website->id)
                             ->with('error', 'データスナップショットの作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 指定した日付範囲のスナップショットをJSON形式で取得（API用）
     */
    public function getData(Request $request, Website $website)
    {
        // 所有者確認
        $this->authorize('view', $website);

        $request->validate([
            'type' => 'required|in:analytics,search_console',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $type = $request->type;

        // スナップショットデータを取得
        $snapshots = $this->dataSnapshotService->getSnapshotsByDateRange(
            $website, $type, $startDate, $endDate
        );

        // データを集計するなど、処理が必要な場合はここで行う
        $processedData = $this->processSnapshotData($snapshots, $type);

        return response()->json($processedData);
    }

    /**
     * スナップショットデータの処理
     */
    private function processSnapshotData($snapshots, $type)
    {
        if ($snapshots->isEmpty()) {
            return [];
        }

        $result = [
            'dates' => [],
            'metrics' => [],
        ];

        // 日付の配列を作成
        foreach ($snapshots as $snapshot) {
            $result['dates'][] = $snapshot->snapshot_date->format('Y-m-d');
        }

        // タイプに応じたメトリクスを取得
        if ($type === 'analytics') {
            $metricKeys = ['users', 'sessions', 'pageviews', 'bounceRate', 'avgSessionDuration'];
        } else {
            $metricKeys = ['clicks', 'impressions', 'ctr', 'position'];
        }

        // 各メトリクスの配列を初期化
        foreach ($metricKeys as $key) {
            $result['metrics'][$key] = [];
        }

        // 各スナップショットからメトリクスを取得
        foreach ($snapshots as $snapshot) {
            $data = $snapshot->data_json;

            foreach ($metricKeys as $key) {
                $result['metrics'][$key][] = $data['metrics'][$key] ?? 0;
            }
        }

        return $result;
    }
}
