<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
        /**
     * ログイン画面を表示
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/Login', [
            'canResetPassword' => Route::has('admin.password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * ログイン処理
     */
    public function login(Request $request): RedirectResponse  // ← store から login に変更
    {
        // バリデーション
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // adminガードで認証を試みる
        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ])->onlyInput('email');
    }

    /**
     * 管理者ダッシュボード
     */
    public function dashboard()
    {
        // 統計データを取得
        $totalUsers = User::count();
        $totalSessions = ExamSession::whereNotNull('finished_at')->count();
        
        // 平均スコアを計算（answersテーブルから）
        $averageScore = DB::table('exam_sessions')
            ->join('answers', 'exam_sessions.id', '=', 'answers.exam_session_id')
            ->whereNotNull('exam_sessions.finished_at')
            ->select(
                'exam_sessions.id',
                DB::raw('SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count')
            )
            ->groupBy('exam_sessions.id')
            ->get()
            ->avg('correct_count');
        
        // 最近のセッション
        $recentSessions = ExamSession::with(['user', 'answers'])
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->take(10)
            ->get()
            ->map(function ($session) {
                $correctCount = $session->answers->where('is_correct', 1)->count();
                $session->score = $correctCount;
                return $session;
            });

        // 管理者情報を取得
        $admin = Auth::guard('admin')->user();

        // Dashboard.vueをレンダリング（管理者用データを渡す）
        return Inertia::render('Admin/Dashboard', [
            'auth' => [
                'user' => auth('admin')->user()
            ],
            'stats' => [
                'total_users' => \App\Models\User::count(),
                'total_sessions' => \App\Models\ExamSession::whereNotNull('finished_at')->count(),
            ],
            'recentSessions' => \App\Models\ExamSession::with('user')
                ->whereNotNull('finished_at')
                ->latest('finished_at')
                ->take(10)
                ->get(),
            'recentUsers' => \App\Models\User::latest()
                ->take(10)
                ->get(),
        ]);
    }

    // もしスコアを計算したい場合は、以下のようなメソッドを追加
    private function calculateAverageScore()
    {
        $sessions = \App\Models\ExamSession::whereNotNull('finished_at')
            ->with('answers')
            ->get();
        
        if ($sessions->isEmpty()) {
            return 0;
        }
        
        $totalScore = $sessions->sum(function ($session) {
            return $session->answers->where('is_correct', 1)->count();
        });
        
        return round($totalScore / $sessions->count(), 2);
    }

    /**
     * 管理者ログアウト処理
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}