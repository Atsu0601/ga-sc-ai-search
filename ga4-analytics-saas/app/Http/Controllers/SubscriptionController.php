<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use App\Models\User;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class SubscriptionController extends Controller
{
    /**
     * サブスクリプション管理ページを表示
     */
    public function index(Request $request)
    {
        // 支払い方法更新完了のフラグがある場合
        if ($request->has('payment_updated')) {
            // ユーザーの支払い方法情報を更新
            $user = Auth::user();

            try {
                if ($user->hasStripeId()) {
                    // 最新の支払い方法情報を取得
                    $paymentMethod = $user->defaultPaymentMethod();

                    if ($paymentMethod && isset($paymentMethod->card)) {
                        $user->pm_type = $paymentMethod->card->brand;
                        $user->pm_last_four = $paymentMethod->card->last4;
                        $user->save();
                    }
                }

                // 成功メッセージを表示
                return view('subscriptions.index', [
                    'plans' => Plan::getActive()
                ])->with('success', '支払い方法が更新されました。');
            } catch (\Exception $e) {
                Log::error('Error updating payment method info: ' . $e->getMessage());
            }
        }

        // 通常の表示
        return view('subscriptions.index', [
            'plans' => Plan::getActive()
        ]);
    }

    /**
     * Stripeチェックアウトセッションを作成
     * カード変更処理
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

            // 支払い方法更新モードかどうか
            $isUpdateMode = $request->boolean('update_payment');

            // 絶対URLを使用
            $successUrl = url('/subscriptions') . ($isUpdateMode ? '?payment_updated=1' :
                ('?plan=' . $plan->name . '&session_id={CHECKOUT_SESSION_ID}'));

            $cancelUrl = url('/subscriptions');

            // 支払い方法更新モードの場合
            if ($isUpdateMode && $user->stripe_id) {
                // 既存の顧客の支払い方法を更新するセッション
                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'mode' => 'setup',
                    'customer' => $user->stripe_id,
                    'setup_intent_data' => [
                        'metadata' => [
                            'user_id' => $user->id,
                            'is_update' => true,
                        ],
                    ],
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                ]);
            } else {
                // 通常のサブスクリプション作成セッション
                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'customer_email' => $user->email,
                    'line_items' => [
                        [
                            'price' => $plan->stripe_plan_id,
                            'quantity' => 1,
                        ],
                    ],
                    'mode' => 'subscription',
                    'success_url' => url('/subscriptions/success') .
                        '?plan=' . $plan->name .
                        '&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $cancelUrl,
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'user_id' => $user->id,
                    ],
                ]);
            }

            return redirect($session->url);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            // エラーログ
            Log::error('Stripe checkout error: ' . $e->getMessage(), [
                'plan_id' => $plan->id,
                'stripe_plan_id' => $plan->stripe_plan_id,
                'user_id' => $user->id,
                'is_update' => $request->boolean('update_payment')
            ]);

            return back()->with('error', 'チェックアウトセッションの作成中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * サブスクリプション成功時の処理
     */
    public function success(Request $request)
    {
        try {
            $request->validate([
                'plan' => 'required|exists:plans,name',
                'session_id' => 'required|string',
            ]);

            $plan = Plan::where('name', $request->plan)
                ->where('is_active', true)
                ->firstOrFail();

            $user = Auth::user();

            // Stripeからセッション情報を取得して処理
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = \Stripe\Checkout\Session::retrieve([
                'id' => $request->session_id,
                'expand' => ['subscription', 'subscription.default_payment_method']
            ]);

            // デバッグログ
            Log::info('Stripe checkout success callback', [
                'session_id' => $request->session_id,
                'user_id' => $user->id,
                'plan' => $plan->name
            ]);

            // ユーザー情報を更新
            $user->plan_name = $plan->name;
            $user->subscription_status = 'active';
            $user->website_limit = $plan->website_limit;

            // Stripeの顧客IDとサブスクリプションIDがあれば保存
            if (!empty($session->customer)) {
                $user->stripe_id = $session->customer;
            }

            if (!empty($session->subscription)) {
                $user->stripe_subscription_id = $session->subscription->id;

                // Cashierサブスクリプションテーブルにデータを保存
                $dbSubscription = $user->subscriptions()->updateOrCreate(
                    ['stripe_id' => $session->subscription->id],
                    [
                        'type' => 'default',
                        'stripe_status' => $session->subscription->status,
                        'stripe_price' => $plan->stripe_plan_id,
                        'quantity' => 1,
                        'trial_ends_at' => null,
                        'ends_at' => null,
                    ]
                );

                // サブスクリプション項目を保存
                if (isset($session->subscription->items) && isset($session->subscription->items->data)) {
                    foreach ($session->subscription->items->data as $item) {
                        $dbSubscription->items()->updateOrCreate(
                            ['stripe_id' => $item->id],
                            [
                                'stripe_product' => $item->price->product,
                                'stripe_price' => $item->price->id,
                                'quantity' => $item->quantity,
                            ]
                        );
                    }
                }

                // 支払い方法情報を更新
                if (isset($session->subscription->default_payment_method) &&
                    isset($session->subscription->default_payment_method->card)) {
                    $user->pm_type = $session->subscription->default_payment_method->card->brand;
                    $user->pm_last_four = $session->subscription->default_payment_method->card->last4;
                }
            }

            $user->save();

            return redirect()->route('subscriptions.index')
                            ->with('success', "{$plan->display_name}への登録が完了しました。");
        } catch (\Exception $e) {
            Log::error('Stripe success callback error: ' . $e->getMessage(), [
                'session_id' => $request->session_id ?? null,
                'plan' => $request->plan ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('subscriptions.index')
                            ->with('error', 'サブスクリプション処理中にエラーが発生しました: ' . $e->getMessage());
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
