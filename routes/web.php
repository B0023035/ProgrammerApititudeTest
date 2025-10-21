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
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// 管理者認証ルート
Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {
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

Route::middleware('auth:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('guest:admin')->prefix('admin')->name('admin.')->group(function () {
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

Route::middleware('auth:admin')->prefix('admin')->name('admin.')->group(function () {
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

// 一般ユーザー用ルート
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('guest')->group(function () {
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

Route::middleware('auth')->group(function () {
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
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/test', [TestController::class, 'index'])->name('test.index');
    Route::post('/test/start', [TestController::class, 'start'])->name('test.start');
    Route::post('/test/submit', [TestController::class, 'submit'])->name('test.submit');
    Route::get('/test/result', [TestController::class, 'result'])->name('test.result');
});

require __DIR__ . '/auth.php';