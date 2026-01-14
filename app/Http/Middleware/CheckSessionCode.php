<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionCode
{
    /**
     * セッションコードが必要なルートをチェック
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('CheckSessionCode - Middleware called', [
            'url' => $request->url(),
            'session_id' => $request->session()->getId(),
            'has_verified_code' => $request->session()->has('verified_session_code'),
            'verified_code' => $request->session()->get('verified_session_code'),
        ]);

        // セッションコードが検証済みかチェック
        if (!$request->session()->has('verified_session_code')) {
            Log::warning('CheckSessionCode - セッションコード未検証、リダイレクト', [
                'url' => $request->url(),
                'session_id' => $request->session()->getId(),
                'all_session_data' => $request->session()->all(),
            ]);
            
            return redirect()->route('session.entry');
        }
        
        Log::info('CheckSessionCode - Session code verified, proceeding', [
            'verified_code' => $request->session()->get('verified_session_code'),
        ]);
        
        return $next($request);
    }
}