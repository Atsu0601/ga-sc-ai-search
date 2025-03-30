<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * ユーザー一覧を表示
     */
    public function index()
    {
        $users = User::with('company')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    /**
     * ユーザー詳細を表示
     */
    public function show(User $user)
    {
        $user->load(['company', 'websites']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * ユーザー編集フォームを表示
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * ユーザー情報を更新
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:user,admin',
            'plan_name' => 'required|string|max:255',
            'website_limit' => 'required|integer|min:1',
        ]);

        $user->update($request->all());

        return redirect()->route('admin.users.show', $user->id)
                         ->with('success', 'ユーザー情報が更新されました。');
    }
}
