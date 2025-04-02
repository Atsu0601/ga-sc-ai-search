<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SettingController extends Controller
{
    use AuthorizesRequests;

    /**
     * システム設定ページを表示
     */
    public function index()
    {
        // APIキー設定の取得
        $apiSettings = Setting::getGroup('api');

        // システム設定の取得
        $systemSettings = Setting::getGroup('system');

        return view('admin.settings.index', compact('apiSettings', 'systemSettings'));
    }

    /**
     * API設定の更新
     */
    public function updateApi(Request $request)
    {
        $request->validate([
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
            'openai_api_key' => 'nullable|string|max:255',
            'stripe_key' => 'nullable|string|max:255',
            'stripe_secret' => 'nullable|string|max:255',
        ]);

        // API設定を保存
        Setting::set('google_client_id', $request->google_client_id, 'api', 'Google AnalyticsとSearch Console連携用のClient ID');
        Setting::set('google_client_secret', $request->google_client_secret, 'api', 'Google AnalyticsとSearch Console連携用のClient Secret');
        Setting::set('openai_api_key', $request->openai_api_key, 'api', 'AIレポート生成用のOpenAI API Key');
        Setting::set('stripe_key', $request->stripe_key, 'api', '決済処理用のStripe公開キー');
        Setting::set('stripe_secret', $request->stripe_secret, 'api', '決済処理用のStripeシークレットキー');

        return redirect()->route('admin.settings.index')->with('success', 'API設定が更新されました。');
    }

    /**
     * システム設定の更新
     */
    public function updateSystem(Request $request)
    {
        $request->validate([
            'trial_days' => 'required|integer|min:1',
            'maintenance_mode' => 'sometimes|boolean',
            'debug_mode' => 'sometimes|boolean',
        ]);

        // システム設定を保存
        Setting::set('trial_days', $request->trial_days, 'system', '無料トライアル期間（日数）');
        Setting::set('maintenance_mode', $request->has('maintenance_mode') ? 1 : 0, 'system', 'メンテナンスモード');
        Setting::set('debug_mode', $request->has('debug_mode') ? 1 : 0, 'system', 'デバッグモード');

        return redirect()->route('admin.settings.index')->with('success', 'システム設定が更新されました。');
    }

    /**
     * キャッシュクリア
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            return redirect()->route('admin.settings.index')->with('success', 'キャッシュがクリアされました。');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')->with('error', 'キャッシュクリア中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 設定キャッシュ
     */
    public function cacheConfig()
    {
        try {
            Artisan::call('config:cache');
            return redirect()->route('admin.settings.index')->with('success', '設定がキャッシュされました。');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')->with('error', '設定キャッシュ中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * ルートキャッシュ
     */
    public function cacheRoutes()
    {
        try {
            Artisan::call('route:cache');
            return redirect()->route('admin.settings.index')->with('success', 'ルートがキャッシュされました。');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')->with('error', 'ルートキャッシュ中にエラーが発生しました: ' . $e->getMessage());
        }
    }
}
