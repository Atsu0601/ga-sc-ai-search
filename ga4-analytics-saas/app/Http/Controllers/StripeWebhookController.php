<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Plan;
use Stripe\Stripe;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Carbon\Carbon;

class StripeWebhookController extends Controller
{
    /**
     * Stripeからのウェブフックを処理します
     */
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $endpoint_secret = config('services.stripe.webhook_secret');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            // ウェブフックの署名を検証
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (SignatureVerificationException $e) {
            // 署名検証に失敗した場合
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Signature verification failed'], 400);
        } catch (\Exception $e) {
            // その他のエラーが発生した場合
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook error'], 400);
        }

        // イベントタイプに基づいた処理
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            default:
                // 処理が定義されていないイベントの場合はログに記録
                Log::info('Unhandled Stripe event: ' . $event->type);
        }

        return response()->json(['success' => true]);
    }

    /**
     * チェックアウトセッション完了イベントを処理
     */
    private function handleCheckoutSessionCompleted($session)
    {
        // セッションのメタデータからユーザーとプランの情報を取得
        $userId = $session->metadata->user_id ?? null;
        $planId = $session->metadata->plan_id ?? null;

        if (!$userId || !$planId) {
            Log::error('Checkout session missing metadata', ['session_id' => $session->id]);
            return;
        }

        $user = User::find($userId);
        $plan = Plan::find($planId);

        if (!$user || !$plan) {
            Log::error('User or plan not found', ['user_id' => $userId, 'plan_id' => $planId]);
            return;
        }

        // プラン変更の場合は既存のサブスクリプションを処理
        $isChange = isset($session->metadata->is_change) && $session->metadata->is_change === 'true';

        // ユーザー情報を更新
        $user->plan_name = $plan->name;
        $user->subscription_status = 'active';
        $user->website_limit = $plan->website_limit;

        // Stripeの顧客IDとサブスクリプションIDを保存
        if (isset($session->customer)) {
            $user->stripe_id = $session->customer;
        }

        if (isset($session->subscription)) {
            $user->stripe_subscription_id = $session->subscription;

            try {
                // Stripeからサブスクリプション情報を取得
                $subscription = \Stripe\Subscription::retrieve([
                    'id' => $session->subscription,
                    'expand' => ['default_payment_method']
                ]);

                // Cashierサブスクリプションテーブルにデータを保存
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

                // サブスクリプション項目を保存
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
            }
        }

        $user->save();

        Log::info('User subscription updated via checkout session', [
            'user_id' => $userId,
            'plan' => $plan->name,
            'is_change' => $isChange
        ]);
    }

    /**
     * 支払い成功イベントを処理
     */
    private function handleInvoicePaymentSucceeded($invoice)
    {
        // 顧客IDからユーザーを検索
        $user = User::where('stripe_id', $invoice->customer)->first();

        if (!$user) {
            Log::error('User not found for invoice', ['customer_id' => $invoice->customer]);
            return;
        }

        // サブスクリプションIDがあれば処理
        if (isset($invoice->subscription)) {
            try {
                // サブスクリプションステータスを更新
                $dbSubscription = $user->subscriptions()
                    ->where('stripe_id', $invoice->subscription)
                    ->first();

                if ($dbSubscription) {
                    $dbSubscription->stripe_status = 'active';
                    $dbSubscription->ends_at = null; // 支払いが成功したらキャンセル日をリセット
                    $dbSubscription->save();
                }
            } catch (\Exception $e) {
                Log::error('Error updating subscription after payment: ' . $e->getMessage(), [
                    'invoice_id' => $invoice->id,
                    'subscription_id' => $invoice->subscription,
                    'user_id' => $user->id
                ]);
            }
        }

        Log::info('Invoice payment succeeded', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_paid
        ]);
    }

    /**
     * 支払い失敗イベントを処理
     */
    private function handleInvoicePaymentFailed($invoice)
    {
        // 顧客IDからユーザーを検索
        $user = User::where('stripe_id', $invoice->customer)->first();

        if (!$user) {
            Log::error('User not found for invoice', ['customer_id' => $invoice->customer]);
            return;
        }

        // サブスクリプションIDがあれば処理
        if (isset($invoice->subscription)) {
            try {
                // サブスクリプションステータスを更新
                $dbSubscription = $user->subscriptions()
                    ->where('stripe_id', $invoice->subscription)
                    ->first();

                if ($dbSubscription) {
                    $dbSubscription->stripe_status = 'past_due';
                    $dbSubscription->save();
                }
            } catch (\Exception $e) {
                Log::error('Error updating subscription after failed payment: ' . $e->getMessage(), [
                    'invoice_id' => $invoice->id,
                    'subscription_id' => $invoice->subscription,
                    'user_id' => $user->id
                ]);
            }
        }

        // 支払い失敗の通知をメールで送信するなどの処理

        Log::info('Invoice payment failed', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'attempt_count' => $invoice->attempt_count
        ]);
    }

    /**
     * サブスクリプション更新イベントを処理
     */
    private function handleSubscriptionUpdated($subscription)
    {
        // サブスクリプションのステータスに応じた処理
        $status = $subscription->status;
        $customer = $subscription->customer;

        $user = User::where('stripe_id', $customer)->first();

        if (!$user) {
            Log::error('User not found for subscription', ['customer_id' => $customer]);
            return;
        }

        // Cashierサブスクリプションテーブルも更新
        try {
            $dbSubscription = $user->subscriptions()
                ->where('stripe_id', $subscription->id)
                ->first();

            if ($dbSubscription) {
                $dbSubscription->stripe_status = $status;

                if ($subscription->cancel_at_period_end) {
                    $dbSubscription->ends_at = Carbon::createFromTimestamp($subscription->current_period_end);
                } else if ($status === 'canceled' && !$dbSubscription->ends_at) {
                    $dbSubscription->ends_at = Carbon::now();
                } else if ($status === 'active' && $dbSubscription->ends_at) {
                    $dbSubscription->ends_at = null;
                }

                $dbSubscription->save();
            }
        } catch (\Exception $e) {
            Log::error('Error updating subscription in database: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'status' => $status
            ]);
        }

        // キャンセル予定の場合
        if ($subscription->cancel_at_period_end) {
            $user->subscription_status = 'cancelled';
            $user->save();

            Log::info('Subscription marked for cancellation', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id
            ]);
            return;
        }

        // ステータスに応じてユーザー情報を更新
        switch ($status) {
            case 'active':
                $user->subscription_status = 'active';
                break;

            case 'past_due':
                // 支払い遅延の場合は警告メールを送信するなどの処理
                $user->subscription_status = 'past_due';
                break;

            case 'unpaid':
                // 未払いの場合の処理
                $user->subscription_status = 'unpaid';
                break;

            case 'canceled':
                // キャンセル完了の場合の処理
                $user->subscription_status = 'cancelled';
                break;
        }

        $user->save();

        Log::info('Subscription status updated', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'status' => $status
        ]);
    }

    /**
     * サブスクリプション削除イベントを処理
     */
    private function handleSubscriptionDeleted($subscription)
    {
        $customer = $subscription->customer;

        $user = User::where('stripe_id', $customer)->first();

        if (!$user) {
            Log::error('User not found for subscription', ['customer_id' => $customer]);
            return;
        }

        // Cashierサブスクリプションテーブルも更新
        try {
            $dbSubscription = $user->subscriptions()
                ->where('stripe_id', $subscription->id)
                ->first();

            if ($dbSubscription && !$dbSubscription->ends_at) {
                $dbSubscription->ends_at = Carbon::now();
                $dbSubscription->stripe_status = 'canceled';
                $dbSubscription->save();
            }
        } catch (\Exception $e) {
            Log::error('Error updating deleted subscription in database: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id
            ]);
        }

        // サブスクリプション終了の処理
        $user->subscription_status = 'cancelled';
        $user->save();

        Log::info('Subscription deleted', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id
        ]);
    }
}
