<?php

use App\Http\Controllers\ProfileController;
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

// ★ ゲスト専用ルート
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
    
    // ★ 修正: ゲスト用結果表示ルート
    Route::get('/result', [ExamController::class, 'guestShowResult'])->name('result');

    // ★ 追加: ゲスト用クリーンアップルート
    Route::post('/cleanup', [ExamController::class, 'guestCleanup'])->name('cleanup');
});

// テスト開始画面(共通)
Route::get('/test-start', function () {
    $isGuest = !Auth::check();
    return Inertia::render($isGuest ? 'GuestInfo' : 'TestStart');
})->name('test.start');

// 練習問題説明画面(認証ユーザー用)
Route::get('/practice/instructions', function () {
    return Inertia::render('ExamInstructions', [
        'isGuest' => false
    ]);
})->name('practice.instructions')->middleware('auth');

// 練習問題(認証ユーザー用)
Route::prefix('practice')->name('practice.')->middleware('auth')->group(function () {
    Route::get('/', [PracticeController::class, 'index'])->name('index');
    Route::get('/{section}', [PracticeController::class, 'show'])
        ->name('show')
        ->where('section', '[1-3]');
    Route::post('/complete', [PracticeController::class, 'complete'])->name('complete');
});

// 本番試験(認証ユーザー専用)
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

// 認証ユーザー専用
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 管理者ログイン
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', function () {
        return Inertia::render('Auth/AdministratorLogin');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.home'));
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが違います。',
        ])->withInput($request->only('email', 'remember'));
    })->name('login.post');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/home', function () {
            return Inertia::render('AdministratorHome');
        })->name('home');

        Route::post('/logout', function (Request $request) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login');
        })->name('logout');
    });
});

require __DIR__.'/auth.php';