<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminAuthController extends Controller
{
    /**
     * 管理者ログイン画面を表示
     */
    public function showLogin()
    {
        return Inertia::render('Admin/Login');
    }

    /**
     * 管理者ログイン処理
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 通常のwebガードでログイン試行
        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            // ログイン成功後、管理者権限をチェック
            $user = Auth::user();
            
            if (!$user->is_admin) {
                // 管理者でない場合はログアウト
                Auth::logout();
                return back()->withErrors([
                    'email' => '管理者権限がありません。',
                ])->onlyInput('email');
            }
            
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ])->onlyInput('email');
    }

    /**
     * ダッシュボード表示
     */
    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard');
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }
}