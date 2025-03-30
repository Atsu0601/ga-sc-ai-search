<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\AnalysisReport;
use App\Jobs\GenerateAnalysisReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * レポート作成フォーム表示
     */
    public function create(Website $website)
    {
        // 所有者確認
        $this->authorize('view', $website);

        // サイトがアクティブでない場合はリダイレクト
        if ($website->status !== 'active') {
            return redirect()->route('websites.show', $website->id)
                             ->with('error', 'レポート作成にはGA4とSearch Consoleの接続が必要です。');
        }

        return view('reports.create', compact('website'));
    }

    /**
     * レポート生成処理
     */
    public function store(Request $request, Website $website)
    {
        // 所有者確認
        $this->authorize('view', $website);

        // サイトがアクティブでない場合はリダイレクト
        if ($website->status !== 'active') {
            return redirect()->route('websites.show', $website->id)
                             ->with('error', 'レポート作成にはGA4とSearch Consoleの接続が必要です。');
        }

        $request->validate([
            'report_type' => 'required|in:executive,technical,content',
            'date_range' => 'required|string',
        ]);

        // 日付範囲を解析
        $dateRange = explode(' - ', $request->date_range);
        $startDate = Carbon::createFromFormat('Y/m/d', $dateRange[0]);
        $endDate = Carbon::createFromFormat('Y/m/d', $dateRange[1]);

        // レポート作成
        $report = new AnalysisReport();
        $report->website_id = $website->id;
        $report->report_type = $request->report_type;
        $report->date_range_start = $startDate;
        $report->date_range_end = $endDate;
        $report->status = 'processing';
        $report->save();

        // バックグラウンドジョブをディスパッチ
        GenerateAnalysisReport::dispatch($report);

        return redirect()->route('reports.show', $report->id)
                         ->with('success', 'レポート生成を開始しました。処理が完了するまでお待ちください。');
    }

    /**
     * レポート詳細表示
     */
    public function show(AnalysisReport $report)
    {
        // 所有者確認
        $this->authorize('view', $report->website);

        // レポートコンポーネントを取得
        $components = $report->components()->orderBy('order')->get();

        // AIレコメンデーションを取得
        $recommendations = $report->recommendations()->orderBy('severity', 'desc')->get();

        return view('reports.show', compact('report', 'components', 'recommendations'));
    }

    /**
     * すべてのレポート一覧表示
     */
    public function index()
    {
        // ユーザーが所有するウェブサイトのIDを取得
        $websiteIds = Auth::user()->websites()->pluck('id');

        // レポートを取得
        $reports = AnalysisReport::whereIn('website_id', $websiteIds)
                                ->orderByDesc('created_at')
                                ->paginate(10);

        return view('reports.index', compact('reports'));
    }

    /**
     * PDFダウンロード
     */
    public function download(AnalysisReport $report)
    {
        // 所有者確認
        $this->authorize('view', $report->website);

        // レポートがまだ処理中または失敗した場合はリダイレクト
        if ($report->status !== 'completed') {
            return redirect()->route('reports.show', $report->id)
                             ->with('error', 'レポートの処理が完了していないためダウンロードできません。');
        }

        // PDFが生成済みの場合
        if ($report->file_path && file_exists(storage_path('app/' . $report->file_path))) {
            return response()->download(storage_path('app/' . $report->file_path));
        }

        return redirect()->route('reports.show', $report->id)
                         ->with('error', 'PDFファイルが見つかりません。');
    }
}
