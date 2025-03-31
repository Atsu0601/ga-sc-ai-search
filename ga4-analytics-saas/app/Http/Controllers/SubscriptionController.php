<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * サブスクリプション管理ページを表示
     */
    public function index()
    {
        return view('subscriptions.index');
    }

    /**
     * プラン選択処理
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:starter,pro,agency',
        ]);

        $user = Auth::user();
        $plan = $request->plan;

        // ここにStripe処理が入る
        // 実際の実装ではLaravel Cashierを使用してStripe APIと連携

        // 仮実装：プラン情報を更新
        switch ($plan) {
            case 'starter':
                $user->plan_name = 'starter';
                $user->website_limit = 3;
                break;
            case 'pro':
                $user->plan_name = 'pro';
                $user->website_limit = 10;
                break;
            case 'agency':
                $user->plan_name = 'agency';
                $user->website_limit = 30;
                break;
        }

        $user->subscription_status = 'active';
        $user->save();

        return redirect()->route('subscriptions.index')
                         ->with('success', "{$plan}プランへの登録が完了しました。");
    }

    /**
     * プラン変更処理
     */
    public function changePlan(Request $request)
    {
        // プラン変更処理
        return redirect()->route('subscriptions.index')
                         ->with('success', "プランの変更リクエストを受け付けました。次回の請求サイクルから適用されます。");
    }

    /**
     * サブスクリプションキャンセル処理
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();

        // ここにStripeでのサブスクリプション解約処理が入る

        // 仮実装：次回の請求日でサブスクリプション終了
        $user->subscription_status = 'cancelled';
        $user->save();

        return redirect()->route('subscriptions.index')
                         ->with('success', "サブスクリプションは次回の請求日にキャンセルされます。それまではサービスを引き続きご利用いただけます。");
    }
}
