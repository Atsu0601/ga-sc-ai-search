<?php

namespace App\Http\Controllers;

use App\Models\Heatmap;
use App\Models\Website;
use App\Models\DataSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        // データスナップショットからページURLの一覧を取得
        $pageUrls = [];
        $snapshots = DataSnapshot::where('website_id', $website->id)
            ->where('snapshot_type', 'analytics')
            ->get();

        foreach ($snapshots as $snapshot) {
            $data = $snapshot->data_json;

            // data_json内のページ情報の抽出（構造はGA4データによって異なる可能性あり）
            if (isset($data['pages'])) {
                foreach (array_keys($data['pages']) as $pageUrl) {
                    $pageUrls[$pageUrl] = $pageUrl;
                }
            }

            // 別の形式のデータの場合
            if (isset($data['events'])) {
                foreach ($data['events'] as $event) {
                    if (isset($event['page'])) {
                        $pageUrls[$event['page']] = $event['page'];
                    }
                }
            }
        }

        // ページURLがない場合はサンプルURLを追加
        if (empty($pageUrls)) {
            $pageUrls[$website->url] = $website->url;
            $pageUrls[$website->url . '/contact'] = $website->url . '/contact';
            $pageUrls[$website->url . '/about'] = $website->url . '/about';
        }

        return view('heatmaps.create', compact('website', 'types', 'pageUrls'));
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
            'date_range_start' => 'required|date',
            'date_range_end' => 'required|date|after_or_equal:date_range_start',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // スナップショットからヒートマップを生成
        $heatmap = Heatmap::createFromSnapshots(
            $website->id,
            $request->page_url,
            $request->type,
            Carbon::parse($request->date_range_start),
            Carbon::parse($request->date_range_end)
        );

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

        // デバッグ情報の追加
        if (config('app.debug')) {
            Log::debug('Heatmap details', [
                'heatmap_id' => $heatmap->id,
                'screenshot_path' => $heatmap->screenshot_path,
                'exists' => $heatmap->screenshot_path ? Storage::exists($heatmap->screenshot_path) : false,
                'public_url' => $heatmap->screenshot_path ? Storage::url($heatmap->screenshot_path) : null
            ]);
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

        // データスナップショットからページURLの一覧を取得
        $pageUrls = [];
        $snapshots = DataSnapshot::where('website_id', $website->id)
            ->where('snapshot_type', 'analytics')
            ->get();

        foreach ($snapshots as $snapshot) {
            $data = $snapshot->data_json;

            // data_json内のページ情報の抽出
            if (isset($data['pages'])) {
                foreach (array_keys($data['pages']) as $pageUrl) {
                    $pageUrls[$pageUrl] = $pageUrl;
                }
            }

            // 別の形式のデータの場合
            if (isset($data['events'])) {
                foreach ($data['events'] as $event) {
                    if (isset($event['page'])) {
                        $pageUrls[$event['page']] = $event['page'];
                    }
                }
            }
        }

        // 現在のページURLが一覧にない場合は追加
        if (!isset($pageUrls[$heatmap->page_url])) {
            $pageUrls[$heatmap->page_url] = $heatmap->page_url;
        }

        return view('heatmaps.edit', compact('website', 'heatmap', 'types', 'pageUrls'));
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
            'date_range_start' => 'required|date',
            'date_range_end' => 'required|date|after_or_equal:date_range_start',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // パラメータが変更された場合は新しいヒートマップデータを生成
        if (
            $request->page_url !== $heatmap->page_url ||
            $request->type !== $heatmap->type ||
            Carbon::parse($request->date_range_start)->format('Y-m-d') !== $heatmap->date_range_start->format('Y-m-d') ||
            Carbon::parse($request->date_range_end)->format('Y-m-d') !== $heatmap->date_range_end->format('Y-m-d')
        ) {

            $newHeatmapData = [];
            if ($request->regenerate_data === 'yes') {
                // データスナップショットから新しいデータを生成
                $tempHeatmap = Heatmap::createFromSnapshots(
                    $website->id,
                    $request->page_url,
                    $request->type,
                    Carbon::parse($request->date_range_start),
                    Carbon::parse($request->date_range_end)
                );

                $newHeatmapData = $tempHeatmap->data_json;
                $tempHeatmap->delete(); // 一時的に作成したヒートマップを削除
            }

            $heatmap->page_url = $request->page_url;
            $heatmap->type = $request->type;
            $heatmap->date_range_start = Carbon::parse($request->date_range_start);
            $heatmap->date_range_end = Carbon::parse($request->date_range_end);

            if (!empty($newHeatmapData)) {
                $heatmap->data_json = $newHeatmapData;
            }

            $heatmap->save();
        }

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
     * GA4からヒートマップデータを再取得
     *
     * @param  \App\Models\Website  $website
     * @param  \App\Models\Heatmap  $heatmap
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshData(Website $website, Heatmap $heatmap)
    {
        $this->authorize('update', $website);

        if ($heatmap->website_id !== $website->id) {
            abort(404);
        }

        // スナップショットからヒートマップデータを再生成
        $newHeatmap = Heatmap::createFromSnapshots(
            $website->id,
            $heatmap->page_url,
            $heatmap->type,
            $heatmap->date_range_start,
            $heatmap->date_range_end
        );

        // 新しいデータで更新
        $heatmap->data_json = $newHeatmap->data_json;
        $heatmap->save();

        // 一時的に作成したヒートマップを削除
        $newHeatmap->delete();

        return redirect()->route('websites.heatmaps.show', [$website->id, $heatmap->id])
            ->with('success', 'ヒートマップデータを最新の状態に更新しました');
    }
}
