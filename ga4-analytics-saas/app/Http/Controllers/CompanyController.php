<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CompanyController extends Controller
{
    public function create()
    {
        // 既に会社情報がある場合はダッシュボードへリダイレクト
        if (Auth::user()->company) {
            return redirect()->route('dashboard');
        }

        return view('company.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'billing_email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();

        // 会社情報を作成
        $company = new Company($request->all());
        $user->company()->save($company);

        // トライアル期間を設定（14日間）
        $user->trial_ends_at = Carbon::now()->addDays(14);
        $user->save();

        return redirect()->route('dashboard')->with('success', '会社情報が登録されました。14日間の無料トライアルが開始されました。');
    }

    public function edit()
    {
        $company = Auth::user()->company;

        if (!$company) {
            return redirect()->route('company.create');
        }

        return view('company.edit', compact('company'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'billing_email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        $company = Auth::user()->company;
        $company->update($request->all());

        return redirect()->route('dashboard')->with('success', '会社情報が更新されました。');
    }
}
