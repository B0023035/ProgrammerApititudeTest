<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;

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
     * Handle the request, catching CSRF token mismatches.
     */
    public function handle(Request $request, \Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // ★ 詳細ログ出力
            $method = $request->method();
            $path = $request->path();
            
            // リクエストからトークンを取得（複数のソースを確認）
            $bodyToken = $request->input('_token');
            $headerToken = $request->header('X-CSRF-TOKEN');
            $xsrfToken = $request->cookie('XSRF-TOKEN');
            $requestToken = $bodyToken ?: $headerToken ?: 'NONE';
            
            // セッショントークンを取得
            $sessionToken = $request->session()->token();
            $sessionId = $request->session()->getId();
            
            // セッションCookieが存在するか確認
            $sessionCookie = $request->cookie(config('session.cookie'));

            Log::error("🚨 CSRF Token Mismatch - DETAILED", [
                'method' => $method,
                'path' => $path,
                'tokens' => [
                    'body_token' => $bodyToken ? substr($bodyToken, 0, 20) . '...' : 'NOT_SET',
                    'header_token' => $headerToken ? substr($headerToken, 0, 20) . '...' : 'NOT_SET',
                    'xsrf_cookie' => $xsrfToken ? substr($xsrfToken, 0, 20) . '...' : 'NOT_SET',
                    'session_token' => substr($sessionToken, 0, 20) . '...',
                ],
                'session' => [
                    'id' => $sessionId,
                    'cookie_present' => $sessionCookie ? 'YES' : 'NO',
                    'cookie_name' => config('session.cookie'),
                ],
                'match_check' => [
                    'body_matches' => $bodyToken === $sessionToken ? 'YES' : 'NO',
                    'header_matches' => $headerToken === $sessionToken ? 'YES' : 'NO',
                ],
            ]);

            throw $e;
        }
    }
}
