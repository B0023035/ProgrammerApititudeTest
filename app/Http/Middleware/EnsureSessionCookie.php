<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionCookie
{
    /**
     * セッションクッキーが確実に設定されることを保証する
     * （419エラー完全解決）
     */
    public function handle(Request $request, Closure $next): Response
    {
        // セッションを明示的に開始・初期化
        if (!Session::isStarted()) {
            Session::start();
        }

        // セッション ID を強制的に生成（存在しない場合）
        if (!Session::getId()) {
            Session::setId(\Illuminate\Support\Str::random(40));
        }

        // セッションを保存
        Session::save();

        $response = $next($request);

        // ★ CRITICAL: Set-Cookie ヘッダを確実に追加
        $sessionName = config('session.cookie');
        $sessionId = Session::getId();
        $sessionPath = config('session.path', '/');
        $sessionDomain = config('session.domain', '');
        $lifetime = config('session.lifetime', 120);
        $secure = config('session.secure', false);
        $httpOnly = config('session.http_only', true);
        $sameSite = config('session.same_site', 'lax');

        // Set-Cookie ヘッダを手動で設定（フォールバック）
        $cookieValue = urlencode($sessionId);
        $cookieHeader = "{$sessionName}={$cookieValue}";
        $cookieHeader .= "; Path={$sessionPath}";
        if ($sessionDomain) {
            $cookieHeader .= "; Domain={$sessionDomain}";
        }
        if ($secure) {
            $cookieHeader .= "; Secure";
        }
        if ($httpOnly) {
            $cookieHeader .= "; HttpOnly";
        }
        if ($sameSite) {
            $cookieHeader .= "; SameSite={$sameSite}";
        }
        $cookieHeader .= "; Max-Age=" . ($lifetime * 60);

        \Log::info("🍪 セッションクッキー確保", [
            'session_name' => $sessionName,
            'session_id' => substr($sessionId, 0, 20) . '...',
            'cookie_header' => substr($cookieHeader, 0, 100) . '...',
            'response_headers' => $response->headers->all(),
        ]);

        // レスポンスにセッションクッキーを追加
        $response->header('Set-Cookie', $cookieHeader, false);

        return $response;
    }
}
