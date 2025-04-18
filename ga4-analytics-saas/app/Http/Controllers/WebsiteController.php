<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Website;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebsiteController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $websites = Auth::user()->websites;
        return view('websites.index', compact('websites'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // ユーザーのウェブサイト登録上限を確認
        $user = Auth::user();
        $websiteCount = $user->websites()->count();

        if ($websiteCount >= $user->website_limit) {
            return redirect()->route('websites.index')->with('error', 'ウェブサイトの登録上限に達しています。プランをアップグレードしてください。');
        }

        return view('websites.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();
        $websiteCount = $user->websites()->count();

        if ($websiteCount >= $user->website_limit) {
            return redirect()->route('websites.index')
                ->with('error', 'ウェブサイトの登録上限に達しています。プランをアップグレードしてください。');
        }

        $website = new Website($request->all());
        $website->user_id = $user->id;
        $website->status = 'pending';
        $website->save();

        return redirect()->route('websites.show', $website->id)
            ->with('success', 'ウェブサイトが登録されました。Google AnalyticsとSearch Consoleを接続してください。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Website $website)
    {
        // 所有者確認
        $this->authorize('view', $website);

        return view('websites.show', compact('website'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Website $website)
    {
        // 所有者確認
        $this->authorize('update', $website);

        return view('websites.edit', compact('website'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Website $website)
    {
        $this->authorize('update', $website);

        // バリデーション
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'ga4_property_id' => 'nullable|string|max:255',
            'ga4_credentials' => 'nullable|string',
            'search_console_site_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // 既存のデータを保持しながら更新
            $website->name = $request->name;
            $website->url = $request->url;

            // GA4関連のフィールドは入力がある場合のみ更新
            if ($request->filled('ga4_property_id')) {
                $website->ga4_property_id = $request->ga4_property_id;
            }

            // GA4認証情報は慎重に扱う
            if ($request->filled('ga4_credentials')) {
                try {
                    $website->ga4_credentials = $request->ga4_credentials;
                } catch (\Exception $e) {
                    Log::error('Failed to encrypt GA4 credentials', [
                        'website_id' => $website->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            // Search Console URLの更新
            if ($request->filled('search_console_site_url')) {
                $website->search_console_site_url = $request->search_console_site_url;
            }

            $website->save();

            DB::commit();

            Log::info('Website updated successfully', [
                'website_id' => $website->id,
                'user_id' => Auth::id()
            ]);

            return redirect()
                ->route('websites.show', $website->id)
                ->with('success', 'ウェブサイト情報を更新しました');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update website', [
                'website_id' => $website->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'ウェブサイト情報の更新に失敗しました')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Website $website)
    {
        // 所有者確認
        $this->authorize('delete', $website);

        $website->delete();

        return redirect()->route('websites.index')
            ->with('success', 'ウェブサイトが削除されました。');
    }
}
