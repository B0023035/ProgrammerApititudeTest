<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminAuthController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\PracticeController;

// TOPページ
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// ========================================
// ゲスト専用ルート
// ========================================
Route::prefix('guest')->name('guest.')->group(function () {
    // ゲスト用テスト開始画面
    Route::get('/test-start', function () {
        return Inertia::render('GuestInfo');
    })->name('test.start');
    
    // ゲスト情報入力
    Route::get('/info', function () {
        return Inertia::render('GuestInfo');
    })->name('info');
    Route::post('/info', [ExamController::class, 'storeGuestInfo'])->name('info.store');
    
    // ゲスト用練習問題
    Route::prefix('practice')->name('practice.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('guest.practice.show', ['section' => 1]);
        })->name('index');
        
        Route::get('/{section}', [PracticeController::class, 'guestShow'])
            ->name('show')
            ->where('section', '[1-3]');
        Route::post('/complete', [PracticeController::class, 'guestComplete'])->name('complete');
    });
    
    // ゲスト用本番試験
    Route::prefix('exam')->name('exam.')->group(function () {
        Route::post('/start', [ExamController::class, 'guestStart'])->name('start');
        Route::get('/part/{part}', [ExamController::class, 'guestPart'])
            ->where('part', '[1-3]')
            ->name('part');
        Route::post('/complete-part', [ExamController::class, 'guestCompletePart'])->name('complete-part');
        Route::post('/report-violation', [ExamController::class, 'guestReportViolation'])->name('report-violation');
        Route::get('/disqualified', [ExamController::class, 'guestDisqualified'])->name('disqualified');
    });
    
    // ゲスト用結果表示
    Route::get('/result', [ExamController::class, 'guestShowResult'])->name('result');
    
    // ゲスト用クリーンアップ
    Route::post('/cleanup', [ExamController::class, 'guestCleanup'])->name('cleanup');
});

// ========================================
// 共通ルート（ゲスト・認証ユーザー）
// ========================================

// テスト開始画面
Route::get('/test-start', function () {
    $isGuest = !Auth::check();
    return Inertia::render($isGuest ? 'GuestInfo' : 'TestStart');
})->name('test.start');

// ========================================
// 認証ユーザー専用ルート
// ========================================

// 練習問題説明画面
Route::get('/practice/instructions', function () {
    return Inertia::render('ExamInstructions', [
        'isGuest' => false
    ]);
})->name('practice.instructions')->middleware('auth');

// 練習問題
Route::prefix('practice')->name('practice.')->middleware('auth')->group(function () {
    Route::get('/', [PracticeController::class, 'index'])->name('index');
    Route::get('/{section}', [PracticeController::class, 'show'])
        ->name('show')
        ->where('section', '[1-3]');
    Route::post('/complete', [PracticeController::class, 'complete'])->name('complete');
});

// 本番試験
Route::prefix('exam')->name('exam.')->middleware('auth')->group(function () {
    Route::post('/start', [ExamController::class, 'start'])->name('start');
    Route::get('/part/{part}', [ExamController::class, 'part'])
        ->where('part', '[1-3]')
        ->name('part');
    Route::post('/complete-part', [ExamController::class, 'completePart'])->name('complete-part');
    Route::post('/save-answer', [ExamController::class, 'saveAnswer'])->name('save-answer');
    Route::post('/report-violation', [ExamController::class, 'reportViolation'])->name('report-violation');
    Route::get('/disqualified', [ExamController::class, 'disqualified'])->name('disqualified');
    Route::get('/result/{sessionUuid}', [ExamController::class, 'showResult'])->name('result');
});

// プロフィール管理
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ========================================
// 管理者ルート
// ========================================
Route::prefix('admin')->name('admin.')->group(function () {
    
    // ゲストのみアクセス可能（未ログイン管理者）
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    });

    // 認証済み管理者のみアクセス可能
    Route::middleware('auth:admin')->group(function () {
        
        // ダッシュボード
        Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('/home', [AdminAuthController::class, 'dashboard'])->name('home'); // 互換性のため
        
        // ログアウト
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        
        // Comlink成績管理システム
        Route::get('/results-comlink', function () {
            return Inertia::render('Admin/ResultsComlink', [
                'sessions' => [], // 実際のデータは後で実装
                'users' => [],
            ]);
        })->name('results.comlink');
        
        // 成績管理システム（通常版）
        Route::prefix('results')->name('results.')->group(function () {
            // メイン一覧
            Route::get('/', function () {
                $sessions = \App\Models\ExamSession::with('user')
                    ->whereNotNull('finished_at')
                    ->latest('finished_at')
                    ->paginate(50);
                
                return Inertia::render('Admin/Results/Index', [
                    'sessions' => $sessions
                ]);
            })->name('index');
            
            // ユーザー詳細
            Route::get('/user/{userId}', function ($userId) {
                $user = \App\Models\User::findOrFail($userId);
                $sessions = \App\Models\ExamSession::where('user_id', $userId)
                    ->whereNotNull('finished_at')
                    ->latest('finished_at')
                    ->get();
                
                return Inertia::render('Admin/Results/UserDetail', [
                    'user' => $user,
                    'sessions' => $sessions
                ]);
            })->name('user-detail');
            
            // セッション詳細
            Route::get('/session/{sessionId}', function ($sessionId) {
                $session = \App\Models\ExamSession::with(['user', 'answers.question'])
                    ->findOrFail($sessionId);
                
                return Inertia::render('Admin/Results/SessionDetail', [
                    'session' => $session
                ]);
            })->name('session-detail');
            
            // 学年別一覧
            Route::get('/grade', function () {
                $users = \App\Models\User::with(['examSessions' => function ($query) {
                    $query->whereNotNull('finished_at')->latest('finished_at');
                }])->get()->groupBy('grade');
                
                return Inertia::render('Admin/Results/GradeList', [
                    'usersByGrade' => $users
                ]);
            })->name('grade-list');
            
            // 統計・グラフ
            Route::get('/statistics', function () {
                $totalSessions = \App\Models\ExamSession::whereNotNull('finished_at')->count();
                $totalUsers = \App\Models\User::count();
                $averageScore = \App\Models\ExamSession::whereNotNull('finished_at')->avg('total_score');
                
                return Inertia::render('Admin/Results/Statistics', [
                    'stats' => [
                        'total_sessions' => $totalSessions,
                        'total_users' => $totalUsers,
                        'average_score' => round($averageScore, 2),
                    ]
                ]);
            })->name('statistics');
        });
        
        // ユーザー管理
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', function () {
                $users = \App\Models\User::withCount('examSessions')
                    ->latest()
                    ->paginate(20);
                
                return Inertia::render('Admin/Users/Index', [
                    'users' => $users
                ]);
            })->name('index');
        });
    });
});

// 認証ルート
require __DIR__.'/auth.php';