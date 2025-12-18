<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSessionStarted
{
    public function handle(Request $request, Closure $next)
    {
        // セッションが開始されていなければ開始
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session.store'));
        }
        
        if (!$request->session()->isStarted()) {
            $request->session()->start();
        }
        
        // CSRFトークンを確実にセッションに保存
        if (!$request->session()->has('_token')) {
            $request->session()->put('_token', $request->session()->token());
        }
        
        \Log::info('Session ensured', [
            'session_id' => $request->session()->getId(),
            'has_token' => $request->session()->has('_token'),
            'token' => $request->session()->token(),
        ]);
        
        return $next($request);
    }
}