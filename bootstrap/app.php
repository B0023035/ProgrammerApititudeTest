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
        // Cloudflare対応: プロキシを信頼
        $middleware->trustProxies(at: '*');
        
        // ★★★ Cookie暗号化を完全に無効化 ★★★
        $middleware->encryptCookies(except: [
            '*',  // すべてのCookieの暗号化を無効化
        ]);
        
        // Inertiaミドルウェアを追加
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // ★ EnsureSessionStartedを追加
        $middleware->web(prepend: [
            \App\Http\Middleware\EnsureSessionStarted::class,
        ]);

        // ミドルウェアエイリアスを設定
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'check.session.code' => \App\Http\Middleware\CheckSessionCode::class,
        ]);

        // ゲストミドルウェアのリダイレクト先をカスタマイズ
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            return route('login');
        });

        // 認証済みユーザーのリダイレクト先をカスタマイズ
        $middleware->redirectUsersTo(function ($request) {
            if (auth()->guard('admin')->check()) {
                return route('admin.dashboard');
            }
            return route('test.start');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();