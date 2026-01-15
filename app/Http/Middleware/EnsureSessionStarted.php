<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSessionStarted
{
    public function handle(Request $request, Closure $next)
    {
        // ★ セッションが既に開始されていれば何もしない
        if ($request->hasSession() && $request->session()->isStarted()) {
            return $next($request);
        }
        
        // セッションが開始されていなければ開始
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session.store'));
        }
        
        if (!$request->session()->isStarted()) {
            $request->session()->start();
        }
        
        // CSRFトークンを確実にセッションに保存（存在しない場合のみ）
        if (!$request->session()->has('_token')) {
            $request->session()->regenerateToken();
        }
        
        return $next($request);
    }
}