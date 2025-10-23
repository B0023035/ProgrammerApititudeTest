<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Inertiaミドルウェアを追加
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // ミドルウェアエイリアスを設定
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'check.session.code' => \App\Http\Middleware\CheckSessionCode::class,
        ]);

        // ゲストミドルウェアのリダイレクト先をカスタマイズ
        $middleware->redirectGuestsTo(function ($request) {
            // デバッグ用: 現在のパスを確認
            \Log::info('redirectGuestsTo called', [
                'path' => $request->path(),
                'url' => $request->url(),
                'admin_check' => auth()->guard('admin')->check(),
                'web_check' => auth()->guard('web')->check(),
            ]);
            
            // 管理者エリア（admin/*）の場合のみ管理者ログインへ
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            
            // それ以外（一般ユーザーエリア）は通常のログイン画面へ
            return route('login');
        });

        // 認証済みユーザーのリダイレクト先をカスタマイズ
        $middleware->redirectUsersTo(function ($request) {
            // 管理者として認証されている場合
            if (auth()->guard('admin')->check()) {
                return route('admin.dashboard');
            }
            // 一般ユーザーとして認証されている場合
            return route('test.start');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();