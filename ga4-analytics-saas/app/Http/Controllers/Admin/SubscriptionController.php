<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubscriptionController extends Controller
{
    use AuthorizesRequests;

    /**
     * サブスクリプション管理ページを表示
     */
    public function index()
    {
        // サブスクリプション統計
        $stats = [
            'total_subscriptions' => DB::table('subscriptions')->where('stripe_status', 'active')->count(),
            'trial_users' => User::where('subscription_status', '=', 'trial')->count(),
            // 売上情報はStripeのAPIから取得するか、別途計算
        ];

        // プラン別ユーザー数
        $planStats = DB::table('users')
            ->select('plan_name', DB::raw('count(*) as total'))
            ->groupBy('plan_name')
            ->get();

        // 最近のサブスクリプション
        $recentSubscriptions = DB::table('subscriptions')
            ->join('users', 'subscriptions.user_id', '=', 'users.id')
            ->select('subscriptions.*', 'users.name', 'users.email')
            ->orderByDesc('subscriptions.created_at')
            ->limit(10)
            ->get();

        // プラン一覧
        $plans = Plan::orderBy('price')->get();

        return view('admin.subscriptions.index', compact('stats', 'planStats', 'recentSubscriptions', 'plans'));
    }

    /**
     * プラン管理ページを表示
     */
    public function plans()
    {
        $plans = Plan::orderBy('price')->get();
        return view('admin.subscriptions.plans', compact('plans'));
    }

    /**
     * プラン作成フォームを表示
     */
    public function createPlan()
    {
        return view('admin.subscriptions.create_plan');
    }

    /**
     * 決済完了後のリダイレクト
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
                'session_data' => $session,
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
                $user->stripe_subscription_id = $session->subscription;

                // Cashierサブスクリプションテーブルにもデータを保存
                try {
                    $subscription = \Stripe\Subscription::retrieve([
                        'id' => $session->subscription,
                        'expand' => ['default_payment_method']
                    ]);

                    $dbSubscription = $user->subscriptions()->updateOrCreate(
                        ['stripe_id' => $session->subscription],
                        [
                            'type' => 'default',
                            'stripe_status' => $subscription->status,
                            'stripe_price' => $plan->stripe_plan_id,
                            'quantity' => 1,
                            'trial_ends_at' => null,
                            'ends_at' => null,
                        ]
                    );

                    // サブスクリプション項目も保存
                    if (isset($subscription->items) && isset($subscription->items->data)) {
                        foreach ($subscription->items->data as $item) {
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
                    if (isset($subscription->default_payment_method) &&
                        isset($subscription->default_payment_method->card)) {
                        $user->pm_type = $subscription->default_payment_method->card->brand;
                        $user->pm_last_four = $subscription->default_payment_method->card->last4;
                    }
                } catch (\Exception $e) {
                    Log::error('Error saving subscription data: ' . $e->getMessage(), [
                        'subscription_id' => $session->subscription,
                        'user_id' => $user->id,
                        'plan_id' => $plan->id
                    ]);
                    // エラーがあってもユーザー更新は続行
                }
            }

            $user->save();

            return redirect()->route('subscriptions.index')
                            ->with('success', "{$plan->display_name}への登録が完了しました。");
        } catch (\Exception $e) {
            Log::error('Stripe success callback error: ' . $e->getMessage(), [
                'session_id' => $request->session_id ?? null,
                'plan' => $request->plan ?? null
            ]);

            return redirect()->route('subscriptions.index')
                            ->with('error', 'サブスクリプション処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * プランを保存
     */
    public function storePlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:plans',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly',
            'stripe_plan_id' => 'nullable|string|max:255',
            'website_limit' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        $plan = Plan::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'price' => $request->price,
            'billing_period' => $request->billing_period,
            'stripe_plan_id' => $request->stripe_plan_id,
            'website_limit' => $request->website_limit,
            'is_active' => $request->has('is_active'),
            'is_featured' => $request->has('is_featured'),
        ]);

        return redirect()->route('admin.subscriptions.plans')->with('success', 'プランが作成されました。');
    }

    /**
     * プラン編集フォームを表示
     */
    public function editPlan(Plan $plan)
    {
        return view('admin.subscriptions.edit_plan', compact('plan'));
    }

    /**
     * プランを更新
     */
    public function updatePlan(Request $request, Plan $plan)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly',
            'stripe_plan_id' => 'nullable|string|max:255',
            'website_limit' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        $plan->update([
            'display_name' => $request->display_name,
            'description' => $request->description,
            'price' => $request->price,
            'billing_period' => $request->billing_period,
            'stripe_plan_id' => $request->stripe_plan_id,
            'website_limit' => $request->website_limit,
            'is_active' => $request->has('is_active'),
            'is_featured' => $request->has('is_featured'),
        ]);

        return redirect()->route('admin.subscriptions.plans')->with('success', 'プランが更新されました。');
    }

    /**
     * プランを削除
     */
    public function destroyPlan(Plan $plan)
    {
        // プランを使用中のユーザーがいるかチェック
        $usersCount = User::where('plan_name', $plan->name)->count();

        if ($usersCount > 0) {
            return redirect()->route('admin.subscriptions.plans')->with('error', 'このプランは現在利用中のユーザーがいるため削除できません。');
        }

        $plan->delete();

        return redirect()->route('admin.subscriptions.plans')->with('success', 'プランが削除されました。');
    }

    /**
     * 支払い履歴を表示
     */
    public function payments()
    {
        $payments = Payment::with('user')
            ->orderByDesc('payment_date')
            ->paginate(20);

        return view('admin.subscriptions.payments', compact('payments'));
    }

    /**
     * カード情報を更新
     */
    public function updateCard(Request $request)
    {
        $user = Auth::user();

        try {
            // Stripeで支払い方法を更新
            $user->updateDefaultPaymentMethod($request->payment_method);

            // 支払い方法情報を取得
            $paymentMethod = $user->defaultPaymentMethod();

            // ユーザー情報を更新
            $user->pm_type = $paymentMethod->card->brand;
            $user->pm_last_four = $paymentMethod->card->last4;
            $user->save();

            // サブスクリプション情報を更新（SQLで直接typeフィールドを使用）
            if ($subscription = $user->subscriptions()->where('stripe_id', $user->stripe_subscription_id)->first()) {
                $subscription->update([
                    'type' => 'default', // nameではなくtypeを使用
                ]);
            }

            return redirect()->route('subscriptions.index')
                            ->with('success', '支払い方法が更新されました。');
        } catch (\Exception $e) {
            return back()->with('error', '支払い方法の更新中にエラーが発生しました: ' . $e->getMessage());
        }
    }
}
