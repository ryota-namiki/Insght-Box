<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * 登録画面表示
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * ユーザー登録
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // プロフィール作成
        $user->profile()->create([]);

        // 自動ログイン
        Auth::login($user);

        return redirect('/cards')->with('success', '登録が完了しました');
    }

    /**
     * ログイン画面表示
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * ログイン処理
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended('/cards')->with('success', 'ログインしました');
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません',
        ])->withInput($request->only('email'));
    }

    /**
     * ログアウト
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'ログアウトしました');
    }

    /**
     * プロフィール表示
     */
    public function showProfile()
    {
        $user = Auth::user();
        return view('auth.profile', compact('user'));
    }

    /**
     * プロフィール更新
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'interests' => 'nullable|string',
        ]);

        $user->update([
            'name' => $request->name,
        ]);

        // プロフィール更新
        if (!$user->profile) {
            $user->profile()->create([]);
        }

        $interests = $request->interests 
            ? array_map('trim', explode(',', $request->interests))
            : null;

        $user->profile->update([
            'department' => $request->department,
            'interests' => $interests,
        ]);

        return back()->with('success', 'プロフィールを更新しました');
    }
}
