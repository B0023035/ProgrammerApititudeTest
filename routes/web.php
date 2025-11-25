<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\EventManagementController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionManagementController;
use App\Http\Controllers\ResultsManagementController;
use App\Http\Controllers\Admin\ResultsComlinkController;
use App\Http\Controllers\SessionCodeController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ========================================
// セッションコード入力(最初にアクセスするページ)
// ※ これはセッションコード認証不要
// ========================================
Route::get('/', [SessionCodeController::class, 'entry'])->name('session.entry');
Route::post('/session/verify', [SessionCodeController::class, 'verify'])->name('session.verify');

// ========================================
// 管理者ルート(セッションコード不要)
// ========================================
Route::prefix('admin')->name('admin.')->group(function () {

    // ゲストのみアクセス可能(未ログイン管理者)
    Route::middleware('guest:admin')->group(function () {
        // 管理者ログインページ(認証不要)
        Route::get('/login', function () {
            return Inertia::render('Admin/Login');
        })->name('login');

        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    });

    // 認証済み管理者のみアクセス可能
    Route::middleware('auth:admin')->group(function () {
        // ダッシュボード
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'total_users' => \App\Models\User::count(),
                    'total_sessions' => \DB::table('exam_sessions')
                        ->whereNotNull('finished_at')
                        ->count(),
                    'active_sessions' => \DB::table('exam_sessions')
                        ->whereNull('finished_at')
                        ->count(),
                ],
                'recentSessions' => \DB::table('exam_sessions')
                    ->whereNotNull('finished_at')
                    ->orderBy('finished_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'finished_at' => $session->finished_at,
                            'user' => \App\Models\User::find($session->user_id),
                        ];
                    }),
                'recentUsers' => \App\Models\User::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
            ]);
        })->name('dashboard');

        // ログアウト
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // イベント管理
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [EventManagementController::class, 'index'])->name('index');
            Route::get('/create', [EventManagementController::class, 'create'])->name('create');
            Route::post('/', [EventManagementController::class, 'store'])->name('store');
            Route::get('/{event}/edit', [EventManagementController::class, 'edit'])->name('edit');
            Route::put('/{event}', [EventManagementController::class, 'update'])->name('update');
            Route::delete('/{event}', [EventManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{event}/terminate', [EventManagementController::class, 'terminate'])->name('terminate');
            Route::get('/generate-passphrase', [EventManagementController::class, 'generateRandomPassphrase'])->name('generate-passphrase');
        });

        // 成績管理
        Route::prefix('results')->name('results.')->group(function () {
            Route::get('/', [ResultsManagementController::class, 'index'])->name('index');
            Route::get('/comlink', [ResultsComlinkController::class, 'index'])->name('comlink');
            Route::get('/statistics', [ResultsManagementController::class, 'statistics'])->name('statistics');
            Route::get('/grade-list', [ResultsManagementController::class, 'gradeList'])->name('grade-list');
            Route::get('/session/{sessionId}', [ResultsManagementController::class, 'sessionDetail'])->name('session-detail');
            Route::get('/user/{userId}', [ResultsManagementController::class, 'userDetail'])->name('user-detail');
        });

        // 問題管理
        Route::resource('questions', QuestionManagementController::class);

        // ユーザー管理
        Route::resource('users', UserManagementController::class);

        // プロフィール管理
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

// ========================================
// セッションコード検証が必要なルート(一般ユーザー用)
// ========================================
Route::middleware(['check.session.code'])->group(function () {

    // Welcomeページ(セッションコード認証後、ログイン前)
    Route::get('/welcome', function () {
        \Log::info('Welcome page accessed', [
            'session_code' => session('verified_session_code'),
            'is_authenticated' => auth()->check(),
            'is_admin' => auth()->guard('admin')->check(),
        ]);

        return Inertia::render('Welcome');
    })->name('welcome');

    // テスト開始画面(ログイン後)
    Route::get('/test-start', function () {
        // 未認証の場合はログインページへ
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return Inertia::render('TestStart');
    })->name('test.start');

    // 練習問題説明画面
    Route::get('/practice/instructions', function () {
        return Inertia::render('ExamInstructions', [
            'isGuest' => false,
        ]);
    })->name('practice.instructions')->middleware('auth');

    // ゲスト専用ルート
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

            // 練習問題完了処理
            Route::match(['post', 'get'], '/complete', [PracticeController::class, 'guestComplete'])->name('complete');

            // ゲスト用解説ページ表示用のGETルート(念のため残す)
            Route::get('/explanation/{part}', [PracticeController::class, 'showGuestExplanation'])
                ->name('explanation')
                ->where('part', '[1-3]');
        });

        // ゲスト用本番試験
        Route::prefix('exam')->name('exam.')->group(function () {
            // ★ 追加: 本番試験説明ページ(練習問題完了後に表示)
            Route::get('/explanation/{part}', [ExamController::class, 'guestExplanation'])
                ->where('part', '[1-3]')
                ->name('explanation');
            
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

    // 認証ユーザー専用ルート
    Route::middleware('auth')->group(function () {
        // 練習問題
        Route::prefix('practice')->name('practice.')->group(function () {
            Route::get('/', [PracticeController::class, 'index'])->name('index');
            Route::get('/{section}', [PracticeController::class, 'show'])
                ->name('show')
                ->where('section', '[1-3]');

            // 練習問題完了処理
            Route::match(['post', 'get'], '/complete', [PracticeController::class, 'complete'])->name('complete');

            // 解説ページ表示用のGETルート(念のため残す)
            Route::get('/explanation/{part}', [PracticeController::class, 'showExplanation'])
                ->name('explanation')
                ->where('part', '[1-3]');
        });

        // 本番試験
        Route::prefix('exam')->name('exam.')->group(function () {
            // ★ 追加: 本番試験説明ページ(練習問題完了後に表示)
            Route::get('/explanation/{part}', [ExamController::class, 'explanation'])
                ->where('part', '[1-3]')
                ->name('explanation');
            
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
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // 認証ルート(ログイン・登録など)
    require __DIR__.'/auth.php';
});