<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Website;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
        // 所有者確認
        $this->authorize('update', $website);

        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'description' => 'nullable|string',
        ]);

        $website->update($request->only(['name', 'url', 'description']));

        return redirect()->route('websites.show', $website->id)
                         ->with('success', 'ウェブサイト情報が更新されました。');
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
