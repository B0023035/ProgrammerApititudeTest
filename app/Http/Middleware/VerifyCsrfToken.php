<?php
// ========================================
// 1. app/Http/Middleware/VerifyCsrfToken.php
// これを完全に置き換えて419の原因を特定
// ========================================

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Closure;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // デバッグ情報をログに出力
        \Log::info('CSRF Debug', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'session_token' => $request->session()->token(),
            'request_token' => $request->input('_token'),
            'header_token' => $request->header('X-CSRF-TOKEN'),
            'session_id' => $request->session()->getId(),
            'has_session' => $request->hasSession(),
            'cookies' => $request->cookies->all(),
        ]);

        return parent::handle($request, $next);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = $request->session()->token();

        $match = is_string($sessionToken) &&
                 is_string($token) &&
                 hash_equals($sessionToken, $token);

        // マッチしない場合の詳細をログ
        if (!$match) {
            \Log::error('CSRF Token Mismatch', [
                'session_token' => $sessionToken,
                'request_token' => $token,
                'session_token_length' => strlen($sessionToken ?? ''),
                'request_token_length' => strlen($token ?? ''),
                'url' => $request->fullUrl(),
            ]);
        }

        return $match;
    }
}