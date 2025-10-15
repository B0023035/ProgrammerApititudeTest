<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use App\Models\ExamSession;
use App\Models\ExamViolation;

class AdminAuthController extends Controller
{
    /**
     * ログイン画面表示
     */
    public function showLogin()
    {
        return Inertia::render('Admin/Login');
    }

    /**
     * ログイン処理
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ])->onlyInput('email');
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

    /**
     * ダッシュボード
     */
    public function dashboard()
    {
        $admin = Auth::guard('admin')->user();
        
        // 統計情報を取得
        $stats = [
            'total_users' => User::count(),
            'total_sessions' => ExamSession::whereNotNull('finished_at')->count(),
            'active_sessions' => ExamSession::whereNull('finished_at')->whereNull('disqualified_at')->count(),
            'recent_violations' => ExamViolation::with(['user', 'examSession'])
                ->latest('detected_at')
                ->limit(10)
                ->get(),
        ];

        // 最近の受験セッション
        $recent_sessions = ExamSession::with('user')
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'admin' => $admin,
            'stats' => $stats,
            'recent_sessions' => $recent_sessions,
        ]);
    }
}