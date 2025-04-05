<?php

namespace App\Http\Controllers;

use App\Models\Heatmap;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class HeatmapController extends Controller
{
    use AuthorizesRequests;

    /**
     * ヒートマップ一覧表示
     *
     * @param \App\Models\Website $website
     * @return \Illuminate\View\View
     */
    public function index(Website $website)
    {
        $this->authorize('view', $website);

        $heatmaps = Heatmap::where('website_id', $website->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('heatmaps.index', compact('website', 'heatmaps'));
    }

    /**
     * 新規ヒートマップ作成フォーム表示
     *
     * @param \App\Models\Website $website
     * @return \Illuminate\View\View
     */
    public function create(Website $website)
    {
        $this->authorize('update', $website);

        $types = Heatmap::getTypes();

        return view('heatmaps.create', compact('website', 'types'));
    }

    /**
     * ヒートマップの保存処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Website  $website
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Website $website)
    {
        $this->authorize('update', $website);

        $validator = Validator::make($request->all(), [
            'page_url' => 'required|url|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(Heatmap::getTypes())),
            'data_json' => 'required|json',
            'date_range_start' => 'required|date',
            'date_range_end' => 'required|date|after_or_equal:date_range_start',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $heatmap = new Heatmap();
        $heatmap->website_id = $website->id;
        $heatmap->page_url = $request->page_url;
        $heatmap->type = $request->type;
        $heatmap->data_json = json_decode($request->data_json, true);
        $heatmap->date_range_start = Carbon::parse($request->date_range_start);
        $heatmap->date_range_end = Carbon::parse($request->date_range_end);
        $heatmap->save();

        return redirect()->route('websites.heatmaps.show', [$website->id, $heatmap->id])
            ->with('success', 'ヒートマップを作成しました');
    }

    /**
     * ヒートマップの詳細表示
     *
     * @param  \App\Models\Website  $website
     * @param  \App\Models\Heatmap  $heatmap
     * @return \Illuminate\View\View
     */
    public function show(Website $website, Heatmap $heatmap)
    {
        $this->authorize('view', $website);

        if ($heatmap->website_id !== $website->id) {
            abort(404);
        }

        $formattedData = $heatmap->formatData();
        $types = Heatmap::getTypes();

        return view('heatmaps.show', compact('website', 'heatmap', 'formattedData', 'types'));
    }

    /**
     * ヒートマップ編集フォーム表示
     *
     * @param  \App\Models\Website  $website
     * @param  \App\Models\Heatmap  $heatmap
     * @return \Illuminate\View\View
     */
    public function edit(Website $website, Heatmap $heatmap)
    {
        $this->authorize('update', $website);

        if ($heatmap->website_id !== $website->id) {
            abort(404);
        }

        $types = Heatmap::getTypes();

        return view('heatmaps.edit', compact('website', 'heatmap', 'types'));
    }

    /**
     * ヒートマップの更新処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Website  $website
     * @param  \App\Models\Heatmap  $heatmap
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Website $website, Heatmap $heatmap)
    {
        $this->authorize('update', $website);

        if ($heatmap->website_id !== $website->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'page_url' => 'required|url|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(Heatmap::getTypes())),
            'data_json' => 'required|json',
            'date_range_start' => 'required|date',
            'date_range_end' => 'required|date|after_or_equal:date_range_start',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $heatmap->page_url = $request->page_url;
        $heatmap->type = $request->type;
        $heatmap->data_json = json_decode($request->data_json, true);
        $heatmap->date_range_start = Carbon::parse($request->date_range_start);
        $heatmap->date_range_end = Carbon::parse($request->date_range_end);
        $heatmap->save();

        return redirect()->route('websites.heatmaps.show', [$website->id, $heatmap->id])
            ->with('success', 'ヒートマップを更新しました');
    }

    /**
     * ヒートマップの削除処理
     *
     * @param  \App\Models\Website  $website
     * @param  \App\Models\Heatmap  $heatmap
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Website $website, Heatmap $heatmap)
    {
        $this->authorize('update', $website);

        if ($heatmap->website_id !== $website->id) {
            abort(404);
        }

        $heatmap->delete();

        return redirect()->route('websites.heatmaps.index', $website->id)
            ->with('success', 'ヒートマップを削除しました');
    }

    /**
     * クリックデータのアップロード処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json,csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = [];

        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'json') {
            // JSONファイルの処理
            $content = file_get_contents($path);
            $jsonData = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return response()->json(['data' => $jsonData]);
            } else {
                return response()->json(['errors' => ['file' => ['JSONの解析に失敗しました。']]], 422);
            }
        } else {
            // CSVファイルの処理
            if (($handle = fopen($path, 'r')) !== false) {
                // ヘッダー行を取得
                $headers = fgetcsv($handle, 1000, ',');

                if ($headers && count($headers) >= 3) {
                    // X座標、Y座標、回数と仮定
                    $xIndex = 0;
                    $yIndex = 1;
                    $countIndex = 2;

                    $clicks = [];
                    $totalClicks = 0;

                    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                        if (isset($row[$xIndex]) && isset($row[$yIndex])) {
                            $count = isset($row[$countIndex]) ? intval($row[$countIndex]) : 1;
                            $totalClicks += $count;

                            // 同じ位置のクリックを指定された回数分追加
                            for ($i = 0; $i < $count; $i++) {
                                $clicks[] = [
                                    'x' => (int)$row[$xIndex],
                                    'y' => (int)$row[$yIndex]
                                ];
                            }
                        }
                    }

                    $data = [
                        'clicks' => $clicks,
                        'totalClicks' => $totalClicks
                    ];
                }
                fclose($handle);
            }

            return response()->json(['data' => $data]);
        }
    }

    /**
     * スクロールデータのアップロード処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadScrollData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json,csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = [];

        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'json') {
            // JSONファイルの処理
            $content = file_get_contents($path);
            $jsonData = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return response()->json(['data' => $jsonData]);
            } else {
                return response()->json(['errors' => ['file' => ['JSONの解析に失敗しました。']]], 422);
            }
        } else {
            // CSVファイルの処理
            if (($handle = fopen($path, 'r')) !== false) {
                // ヘッダー行を取得
                $headers = fgetcsv($handle, 1000, ',');

                if ($headers && count($headers) >= 2) {
                    // スクロール深度とユーザー数を仮定
                    $depthIndex = 0;
                    $countIndex = 1;

                    $scrollDepth = [];
                    $totalUsers = 0;

                    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                        if (isset($row[$depthIndex]) && isset($row[$countIndex])) {
                            $depth = (int)$row[$depthIndex];
                            $count = (int)$row[$countIndex];
                            $scrollDepth[$depth] = $count;

                            if ($depth === 0) { // 初期表示は全ユーザー数とする
                                $totalUsers = $count;
                            }
                        }
                    }

                    $data = [
                        'scrollDepth' => $scrollDepth,
                        'totalUsers' => $totalUsers
                    ];
                }
                fclose($handle);
            }

            return response()->json(['data' => $data]);
        }
    }
}
