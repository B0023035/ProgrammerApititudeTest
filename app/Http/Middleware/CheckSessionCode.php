<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionCode
{
    /**
     * セッションコードが必要なルートをチェック
     */
    public function handle(Request $request, Closure $next): Response
    {
        // セッションコードが検証済みかチェック
        if (!session()->has('verified_session_code')) {
            \Log::warning('セッションコード未検証 - リダイレクト', [
                'url' => $request->url(),
                'session_id' => session()->getId(),
                'all_session' => session()->all(),
            ]);
            
            return redirect()->route('session.entry');
        }
        
        return $next($request);
    }
}