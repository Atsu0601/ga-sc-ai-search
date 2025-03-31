<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GoogleApiController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DataSnapshotController;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

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
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::post('/subscriptions/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Google Analytics
    Route::get('/google/analytics/redirect/{website}', [GoogleApiController::class, 'redirectToGoogleAnalytics'])
        ->name('google.analytics.redirect');
    Route::get('/google/analytics/callback', [GoogleApiController::class, 'handleGoogleAnalyticsCallback'])
        ->name('google.analytics.callback');

    // Search Console
    Route::get('/google/searchconsole/redirect/{website}', [GoogleApiController::class, 'redirectToSearchConsole'])
        ->name('google.searchconsole.redirect');
    Route::get('/google/searchconsole/callback', [GoogleApiController::class, 'handleSearchConsoleCallback'])
        ->name('google.searchconsole.callback');

    // レポート一覧・詳細
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');

    // レポート作成
    Route::get('/websites/{website}/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/websites/{website}/reports', [ReportController::class, 'store'])->name('reports.store');

    // データスナップショット管理
    Route::get('/websites/{website}/snapshots', [DataSnapshotController::class, 'index'])
        ->name('snapshots.index');
    Route::get('/websites/{website}/snapshots/{id}', [DataSnapshotController::class, 'show'])
        ->name('snapshots.show');
    Route::post('/websites/{website}/snapshots', [DataSnapshotController::class, 'create'])
        ->name('snapshots.create');
    Route::get('/websites/{website}/snapshots/data', [DataSnapshotController::class, 'getData'])
        ->name('snapshots.data');
});

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
    Route::get('/subscriptions', function () {
        return view('admin.subscriptions.index');
    })->name('subscriptions.index');

    // システム設定
    Route::get('/settings', function () {
        return view('admin.settings.index');
    })->name('settings.index');
});

require __DIR__.'/auth.php';
