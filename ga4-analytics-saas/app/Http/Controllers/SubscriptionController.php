<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use App\Models\User;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class SubscriptionController extends Controller
{
    /**
     * サブスクリプション管理ページを表示
     */
    public function index()
    {
        $plans = Plan::getActive();
        return view('subscriptions.index', compact('plans'));
    }

    /**
     * Stripeチェックアウトセッションを作成
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:plans,name',
        ]);

        $plan = Plan::where('name', $request->plan)
              ->where('is_active', true)
              ->firstOrFail();

        $user = Auth::user();

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // チェックアウトセッションの作成
            $session = Session::create([
                'payment_method_types' => ['card'],
                'customer_email' => $user->email,
                'line_items' => [
                    [
                        'price' => $plan->stripe_plan_id,
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'subscription',
                'success_url' => route('subscriptions.success', ['plan' => $plan->name]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscriptions.index'),
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                ],
            ]);

            return redirect($session->url);

        } catch (ApiErrorException $e) {
            return back()->with('error', 'チェックアウトセッションの作成中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * サブスクリプション成功時の処理
     */
    public function success(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:plans,name',
            'session_id' => 'required|string',
        ]);

        $plan = Plan::where('name', $request->plan)
              ->where('is_active', true)
              ->firstOrFail();

        $user = Auth::user();

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // セッション情報の取得
            $session = Session::retrieve($request->session_id);

            // サブスクリプションの成功を確認
            if ($session->payment_status === 'paid' || $session->status === 'complete') {
                // ユーザー情報を更新
                $user->plan_name = $plan->name;
                $user->subscription_status = 'active';
                $user->website_limit = $plan->website_limit;
                $user->stripe_id = $session->customer;
                $user->stripe_subscription_id = $session->subscription;
                $user->save();

                return redirect()->route('subscriptions.index')
                                 ->with('success', "{$plan->display_name}への登録が完了しました。");
            }

            return redirect()->route('subscriptions.index')
                             ->with('error', 'サブスクリプションの処理中にエラーが発生しました。');

        } catch (ApiErrorException $e) {
            return redirect()->route('subscriptions.index')
                             ->with('error', 'サブスクリプションの確認中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**ｓ
     * プラン変更処理
     */
    public function changePlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:plans,name',
        ]);

        $user = Auth::user();
        $newPlan = Plan::where('name', $request->plan)
                 ->where('is_active', true)
                 ->first();

        if (!$newPlan) {
            return back()->with('error', '指定されたプランは無効です。');
        }

        // 現在のプランと同じ場合はリダイレクト
        if ($user->plan_name === $newPlan->name) {
            return back()->with('info', '既に同じプランをご利用中です。');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // プラン変更用のチェックアウトセッションを作成
            $session = Session::create([
                'payment_method_types' => ['card'],
                'customer_email' => $user->email,
                'line_items' => [
                    [
                        'price' => $newPlan->stripe_plan_id,
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'subscription',
                'success_url' => route('subscriptions.change.success', ['plan' => $newPlan->name]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscriptions.index'),
                'metadata' => [
                    'plan_id' => $newPlan->id,
                    'user_id' => $user->id,
                    'is_change' => true,
                ],
            ]);

            return redirect($session->url);

        } catch (ApiErrorException $e) {
            return back()->with('error', 'プラン変更処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * プラン変更成功時の処理
     */
    public function changeSuccess(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:plans,name',
            'session_id' => 'required|string',
        ]);

        $newPlan = Plan::where('name', $request->plan)
                 ->where('is_active', true)
                 ->firstOrFail();

        $user = Auth::user();

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // セッション情報の取得
            $session = Session::retrieve($request->session_id);

            // 既存のサブスクリプションがある場合はキャンセル
            if ($user->stripe_subscription_id) {
                // Stripeで以前のサブスクリプションをキャンセル
                // 実際の実装では、webhookを使って非同期で処理することが推奨されます
            }

            // ユーザー情報を更新
            $user->plan_name = $newPlan->name;
            $user->website_limit = $newPlan->website_limit;
            $user->stripe_subscription_id = $session->subscription;
            $user->save();

            return redirect()->route('subscriptions.index')
                             ->with('success', "プランを{$newPlan->display_name}に変更しました。");

        } catch (ApiErrorException $e) {
            return redirect()->route('subscriptions.index')
                             ->with('error', 'プラン変更の確認中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * サブスクリプションキャンセル処理
     */
    public function cancel()
    {
        $user = Auth::user();

        if (!$user->stripe_subscription_id) {
            return back()->with('error', 'アクティブなサブスクリプションがありません。');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Stripeでサブスクリプションを取得
            $subscription = \Stripe\Subscription::retrieve($user->stripe_subscription_id);

            // サブスクリプションをキャンセル（期間終了時）
            $subscription->cancel_at_period_end = true;
            $subscription->save();

            // ユーザー情報を更新
            $user->subscription_status = 'cancelled';
            $user->save();

            return redirect()->route('subscriptions.index')
                             ->with('success', 'サブスクリプションは次回の請求日にキャンセルされます。それまではサービスを引き続きご利用いただけます。');
        } catch (ApiErrorException $e) {
            return back()->with('error', 'サブスクリプション解約中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 領収書のダウンロード
     */
    public function downloadInvoice($invoiceId)
    {
        $user = Auth::user();

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Stripeから請求書を取得
            $invoice = \Stripe\Invoice::retrieve($invoiceId);

            // ユーザーの請求書であることを確認
            if ($invoice->customer !== $user->stripe_id) {
                return back()->with('error', 'このインボイスにアクセスする権限がありません。');
            }

            // 請求書のPDFのURLを取得
            $pdf = $invoice->invoice_pdf;

            return redirect($pdf);
        } catch (ApiErrorException $e) {
            return back()->with('error', '領収書のダウンロード中にエラーが発生しました: ' . $e->getMessage());
        }
    }
}
