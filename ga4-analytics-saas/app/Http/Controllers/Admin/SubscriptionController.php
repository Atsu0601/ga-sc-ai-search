<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
}
