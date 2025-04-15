<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\AnalysisReport;
use App\Jobs\GenerateAnalysisReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleAnalyticsService;
use App\Services\SearchConsoleService;
use App\Services\DataSnapshotService;
use App\Services\ReportComponentGenerator;
use App\Services\OpenAIService;

class ReportController extends Controller
{
    use AuthorizesRequests;

    /**
     * レポート一覧表示
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

        // GET パラメータからレポートタイプを取得
        $reportType = request()->get('type', 'executive');

        return view('reports.create', compact('website', 'reportType'));
    }

    /**
     * レポート生成処理
     */
    public function store(Request $request, Website $website)
    {
        try {
            // バリデーション
            $validated = $request->validate([
                'report_type' => 'required|in:business,technical,content',
                'date_range' => 'required|string',
            ]);

            Log::info('レポート生成開始', [
                'website_id' => $website->id,
                'report_type' => $request->report_type,
                'date_range' => $request->date_range
            ]);

            // 日付範囲を分割
            [$startDate, $endDate] = explode(' - ', $request->date_range);
            $startDate = Carbon::createFromFormat('Y/m/d', $startDate);
            $endDate = Carbon::createFromFormat('Y/m/d', $endDate);

            // レポートを作成
            $report = new AnalysisReport([
                'website_id' => $website->id,
                'report_type' => $request->report_type,
                'date_range_start' => $startDate,
                'date_range_end' => $endDate,
                'status' => AnalysisReport::STATUS_PROCESSING
            ]);
            $report->save();

            Log::info('レポートレコード作成完了', ['report_id' => $report->id]);

            // データ取得
            $analyticsData = null;
            $searchConsoleData = null;

            // GA4データ取得
            if ($website->analyticsAccount) {
                try {
                    $analyticsService = new GoogleAnalyticsService();
                    $analyticsData = $analyticsService->fetchData(
                        $website->analyticsAccount,
                        $startDate,
                        $endDate
                    );

                    Log::info('GA4データ取得成功', [
                        'report_id' => $report->id,
                        'data' => $analyticsData
                    ]);
                } catch (\Exception $e) {
                    Log::error('GA4データ取得エラー', [
                        'report_id' => $report->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            // Search Consoleデータ取得
            if ($website->searchConsoleAccount) {
                try {
                    $searchConsoleService = new SearchConsoleService();
                    $searchConsoleData = $searchConsoleService->fetchData(
                        $website->searchConsoleAccount,
                        $startDate,
                        $endDate
                    );

                    Log::info('Search Consoleデータ取得成功', [
                        'report_id' => $report->id,
                        'data' => $searchConsoleData
                    ]);
                } catch (\Exception $e) {
                    Log::error('Search Consoleデータ取得エラー', [
                        'report_id' => $report->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            // データをレポートに保存
            $report->data_json = [
                'analytics' => $analyticsData,
                'search_console' => $searchConsoleData
            ];
            $report->save();

            Log::info('データ保存完了', ['report_id' => $report->id]);

            try {
                // 必要なサービスのインスタンスを作成
                $analyticsService = new GoogleAnalyticsService();
                $searchConsoleService = new SearchConsoleService();
                $dataSnapshotService = new DataSnapshotService(
                    $analyticsService,
                    $searchConsoleService
                );

                // ReportComponentGeneratorのインスタンスを作成
                $componentGenerator = new ReportComponentGenerator($dataSnapshotService);

                // レポートコンポーネントの生成
                $components = $componentGenerator->generateComponents($report);

                if ($components) {
                    $report->status = AnalysisReport::STATUS_COMPLETED;
                    Log::info('レポート生成完了', ['report_id' => $report->id]);
                } else {
                    $report->status = AnalysisReport::STATUS_FAILED;
                    Log::error('レポートコンポーネント生成失敗', ['report_id' => $report->id]);
                    throw new \Exception('レポートコンポーネントの生成に失敗しました。');
                }

                $report->save();

                return redirect()->route('reports.show', ['report' => $report->id])
                    ->with('success', 'レポートの生成を開始しました。');
            } catch (\Exception $e) {
                Log::error('コンポーネント生成エラー', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('レポート生成エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($report)) {
                $report->status = AnalysisReport::STATUS_FAILED;
                $report->save();
            }

            return redirect()->back()
                ->with('error', 'レポートの生成中にエラーが発生しました。もう一度お試しいただくか、管理者にお問い合わせください。');
        }
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

    /**
     * レポート削除
     */
    public function destroy(AnalysisReport $report)
    {
        try {
            // レポートに関連するコンポーネントを削除
            $report->components()->delete();

            // レポートを削除
            $report->delete();

            return redirect()->route('reports.index')
                ->with('success', 'レポートを削除しました。');
        } catch (\Exception $e) {
            Log::error('レポート削除エラー', [
                'report_id' => $report->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'レポートの削除中にエラーが発生しました。');
        }
    }

    public function getStatus(AnalysisReport $report)
    {
        $data = [
            'status' => $report->status,
            'status_japanese' => $report->status_japanese,
            'progress' => $report->getProgressPercentage(),
            'data_json' => $report->data_json
        ];

        Log::info('レポートステータス確認', [
            'report_id' => $report->id,
            'status' => $data
        ]);

        return response()->json($data);
    }
}
