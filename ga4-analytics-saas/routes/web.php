<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GoogleApiController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DataSnapshotController;
use App\Http\Controllers\StripeWebhookController;
use Laravel\Cashier\Http\Controllers\WebhookController;
use App\Http\Controllers\HeatmapController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportPowerPointController;
use App\Http\Controllers\ReportGoogleSlideController;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });


// 追加分
// 認証と会社情報が必要なルート
Route::middleware(['auth', 'company.exists'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // ウェブサイト管理ルート
    Route::resource('websites', WebsiteController::class);

    // プロフィール関連のルート
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // サブスクリプション管理
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/checkout', [SubscriptionController::class, 'checkout'])->name('subscriptions.checkout');
    Route::get('/subscriptions/success', [SubscriptionController::class, 'success'])->name('subscriptions.success');
    Route::get('/subscriptions/invoices/{invoice}', [SubscriptionController::class, 'downloadInvoice'])->name('subscriptions.invoice');
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::get('/subscriptions/change/success', [SubscriptionController::class, 'changeSuccess'])->name('subscriptions.change.success');
    Route::post('/subscriptions/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Google Analytics
    Route::get('/google/analytics/redirect/{website}', [GoogleApiController::class, 'redirectToGoogleAnalytics'])
        ->name('google.analytics.redirect');
    Route::get('/google/analytics/callback', [GoogleApiController::class, 'handleGoogleAnalyticsCallback'])
        ->name('google.analytics.callback');
    Route::post('/google/analytics/select-property/{website}', [GoogleApiController::class, 'handlePropertySelection'])
        ->name('google.analytics.select-property');
    Route::delete('/google/analytics/disconnect/{website}', [GoogleApiController::class, 'disconnectAnalytics'])
        ->name('google.analytics.disconnect');

    // Search Console
    Route::get('/google/searchconsole/redirect/{website}', [GoogleApiController::class, 'redirectToSearchConsole'])
        ->name('google.searchconsole.redirect');
    Route::get('/google/searchconsole/callback', [GoogleApiController::class, 'handleSearchConsoleCallback'])
        ->name('google.searchconsole.callback');
    Route::delete('/google/searchconsole/disconnect/{website}', [GoogleApiController::class, 'disconnectSearchConsole'])
        ->name('google.searchconsole.disconnect');
    Route::post('/google/searchconsole/select-property/{website}', [GoogleApiController::class, 'handleSearchConsolePropertySelection'])
        ->name('google.searchconsole.select-property');

    // レポート一覧・詳細
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');

    // レポート作成
    Route::get('/websites/{website}/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/websites/{website}/reports', [ReportController::class, 'store'])->name('reports.store');

    // データスナップショット管理
    Route::get('/websites/{website}/snapshots', [DataSnapshotController::class, 'index'])->name('snapshots.index');
    Route::get('/websites/{website}/snapshots/{id}', [DataSnapshotController::class, 'show'])->name('snapshots.show');
    Route::post('/websites/{website}/snapshots', [DataSnapshotController::class, 'create'])->name('snapshots.create');
    Route::get('/websites/{website}/snapshots/data', [DataSnapshotController::class, 'getData'])->name('snapshots.data');

    // // PowerPoint出力
    // Route::get('/reports/{id}/powerpoint', [ReportPowerPointController::class, 'export'])->name('reports.powerpoint');

    // Google Slide出力
    Route::get('/reports/{id}/export', [ReportGoogleSlideController::class, 'export'])->name('reports.export');
    Route::get('/report/slides/{id}', [ReportGoogleSlideController::class, 'export'])->name('report.slides');
});

// Stripe Webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// 会社情報登録・編集用ルート
Route::middleware(['auth'])->group(function () {
    Route::get('/company/create', [CompanyController::class, 'create'])->name('company.create');
    Route::post('/company', [CompanyController::class, 'store'])->name('company.store');
    Route::get('/company/edit', [CompanyController::class, 'edit'])->name('company.edit');
    Route::put('/company', [CompanyController::class, 'update'])->name('company.update');
});

// 管理者専用ルート
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // 管理者ダッシュボード
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // ユーザー管理
    Route::resource('users', Admin\UserController::class);

    // サブスクリプション管理
    Route::get('/subscriptions', [Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/plans', [Admin\SubscriptionController::class, 'plans'])->name('subscriptions.plans');
    Route::get('/subscriptions/plans/create', [Admin\SubscriptionController::class, 'createPlan'])->name('subscriptions.plans.create');
    Route::post('/subscriptions/plans', [Admin\SubscriptionController::class, 'storePlan'])->name('subscriptions.plans.store');
    Route::get('/subscriptions/plans/{plan}/edit', [Admin\SubscriptionController::class, 'editPlan'])->name('subscriptions.plans.edit');
    Route::put('/subscriptions/plans/{plan}', [Admin\SubscriptionController::class, 'updatePlan'])->name('subscriptions.plans.update');
    Route::delete('/subscriptions/plans/{plan}', [Admin\SubscriptionController::class, 'destroyPlan'])->name('subscriptions.plans.destroy');
    Route::get('/subscriptions/payments', [Admin\SubscriptionController::class, 'payments'])->name('subscriptions.payments');

    // システム設定
    Route::get('/settings', [Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/api', [Admin\SettingController::class, 'updateApi'])->name('settings.update.api');
    Route::post('/settings/system', [Admin\SettingController::class, 'updateSystem'])->name('settings.update.system');
    Route::post('/settings/cache/clear', [Admin\SettingController::class, 'clearCache'])->name('settings.cache.clear');
    Route::post('/settings/cache/config', [Admin\SettingController::class, 'cacheConfig'])->name('settings.cache.config');
    Route::post('/settings/cache/routes', [Admin\SettingController::class, 'cacheRoutes'])->name('settings.cache.routes');
});

require __DIR__ . '/auth.php';
