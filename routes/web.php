<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Admin\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Admin\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\Auth\VerifyEmailController;
use App\Http\Controllers\Admin\QuestionManagementController;
use App\Http\Controllers\ResultsManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController as UserAuthController;
use App\Http\Controllers\Auth\ConfirmablePasswordController as UserConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController as UserEmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController as UserEmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController as UserNewPasswordController;
use App\Http\Controllers\Auth\PasswordController as UserPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController as UserPasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController as UserRegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController as UserVerifyEmailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\PracticeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// ========================================
// 管理者ルート（最優先で定義）
// ========================================

// 管理者ゲストルート（未ログイン時）- 最初に定義
Route::middleware(['web', 'guest:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

// 管理者認証ルート（メール確認など）
Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('verify-email', [EmailVerificationPromptController::class, 'show'])
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

// 管理者認証ルート（ダッシュボードなど）
Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 成績管理
    Route::get('/results', [ResultsManagementController::class, 'index'])
        ->name('results.index');
    
    // Comlink成績管理
    Route::get('/results-comlink', [ResultsManagementController::class, 'comlink'])
        ->name('results.comlink');
    
    // セッション詳細ページ
    Route::get('/results/session/{sessionId}', [ResultsManagementController::class, 'sessionDetail'])
        ->name('results.session-detail');
    
    Route::get('/results/user/{userId}', [ResultsManagementController::class, 'userDetail'])
        ->name('results.user-detail');
    Route::get('/results/statistics', [ResultsManagementController::class, 'statistics'])
        ->name('results.statistics');
    Route::get('/results/grades', [ResultsManagementController::class, 'gradeList'])
        ->name('results.grades');

    // 問題管理
    Route::get('/questions', [QuestionManagementController::class, 'index'])
        ->name('questions.index');
    Route::get('/questions/create', [QuestionManagementController::class, 'create'])
        ->name('questions.create');
    Route::post('/questions', [QuestionManagementController::class, 'store'])
        ->name('questions.store');
    Route::get('/questions/{question}/edit', [QuestionManagementController::class, 'edit'])
        ->name('questions.edit');
    Route::put('/questions/{question}', [QuestionManagementController::class, 'update'])
        ->name('questions.update');
    Route::delete('/questions/{question}', [QuestionManagementController::class, 'destroy'])
        ->name('questions.destroy');

    // ユーザー管理
    Route::get('/users', [UserManagementController::class, 'index'])
        ->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])
        ->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->name('users.store');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])
        ->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])
        ->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
        ->name('users.destroy');
});

// ========================================
// TOPページ
// ========================================
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
// 認証ユーザー専用ルート
// ========================================

// 練習問題説明画面
Route::get('/practice/instructions', function () {
    return Inertia::render('ExamInstructions', [
        'isGuest' => false
    ]);
})->name('practice.instructions')->middleware('auth');

// 練習問題
Route::prefix('practice')->name('practice.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [PracticeController::class, 'index'])->name('index');
    Route::get('/{section}', [PracticeController::class, 'show'])
        ->name('show')
        ->where('section', '[1-3]');
    Route::post('/complete', [PracticeController::class, 'complete'])->name('complete');
});

// 本番試験
Route::prefix('exam')->name('exam.')->middleware(['web', 'auth'])->group(function () {
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

// ========================================
// 一般ユーザー用ルート
// ========================================

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['web', 'auth', 'verified'])->name('dashboard');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 一般ユーザーゲストルート（管理者の後に定義）
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('register', [UserRegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [UserRegisteredUserController::class, 'store']);

    Route::get('login', [UserAuthController::class, 'create'])
        ->name('login');

    Route::post('login', [UserAuthController::class, 'store']);

    Route::get('forgot-password', [UserPasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [UserPasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [UserNewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [UserNewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('verify-email', [UserEmailVerificationPromptController::class, 'show'])
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [UserVerifyEmailController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [UserConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [UserConfirmablePasswordController::class, 'store']);

    Route::put('password', [UserPasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [UserAuthController::class, 'destroy'])
        ->name('logout');
});

// テスト関連のルート
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // 認証ユーザー用テスト開始画面
    Route::get('/test-start', function () {
        return Inertia::render('TestStart');
    })->name('test.start');
    
    Route::get('/test', [TestController::class, 'index'])->name('test.index');
    Route::post('/test/start-session', [TestController::class, 'start'])->name('test.start-session');
    Route::post('/test/submit', [TestController::class, 'submit'])->name('test.submit');
    Route::get('/test/result', [TestController::class, 'result'])->name('test.result');
});

require __DIR__ . '/auth.php';