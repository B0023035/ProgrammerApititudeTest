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
        ]);

        // ゲストミドルウェアのリダイレクト先をカスタマイズ
        $middleware->redirectGuestsTo(function ($request) {
            // 管理者エリアの場合
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            // 一般ユーザーの場合
            return route('login');
        });

        // 認証済みユーザーのリダイレクト先をカスタマイズ
        $middleware->redirectUsersTo(function ($request) {
            // 管理者として認証されている場合
            if (auth()->guard('admin')->check()) {
                return route('admin.dashboard');
            }
            // 一般ユーザーとして認証されている場合
            return '/test-start';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();