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
            $requestToken = $request->input('_token') || $request->header('X-CSRF-TOKEN') || 'NONE';
            $sessionToken = $request->session()->token();

            Log::error("🚨 CSRF Token Mismatch", [
                'method' => $method,
                'path' => $path,
                'request_token' => substr($requestToken, 0, 20) . '...',
                'session_token' => substr($sessionToken, 0, 20) . '...',
                'headers' => [
                    'X-CSRF-TOKEN' => $request->header('X-CSRF-TOKEN') ? 'SET' : 'NOT_SET',
                    'X-Requested-With' => $request->header('X-Requested-With') || 'NOT_SET',
                ],
                'body_has_token' => $request->has('_token') ? 'YES' : 'NO',
                'session_id' => $request->session()->getId(),
            ]);

            throw $e;
        }
    }
}
