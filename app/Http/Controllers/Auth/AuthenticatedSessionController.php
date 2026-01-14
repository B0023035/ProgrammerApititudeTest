<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // ★ ログイン前にセッションコード情報を保存
        $verifiedSessionCode = session('verified_session_code');
        $examSessionCode = session('exam_session_code');
        $sessionCodeId = session('session_code_id');
        $currentEventId = session('current_event_id');
        $currentEventName = session('current_event_name');
        
        \Log::info('Login - Before authentication', [
            'verified_session_code' => $verifiedSessionCode,
            'exam_session_code' => $examSessionCode,
            'current_event_id' => $currentEventId,
        ]);
        
        // webガード（一般ユーザー）で認証
        $request->authenticate();

        $request->session()->regenerate();

        // ★ セッションコード情報を復元（すべて）
        if ($verifiedSessionCode) {
            session(['verified_session_code' => $verifiedSessionCode]);
            session(['exam_session_code' => $verifiedSessionCode]);
        }
        if ($examSessionCode && !$verifiedSessionCode) {
            session(['verified_session_code' => $examSessionCode]);
            session(['exam_session_code' => $examSessionCode]);
        }
        if ($sessionCodeId) {
            session(['session_code_id' => $sessionCodeId]);
        }
        if ($currentEventId) {
            session(['current_event_id' => $currentEventId]);
        }
        if ($currentEventName) {
            session(['current_event_name' => $currentEventName]);
        }
        
        // セッションを明示的に保存
        session()->save();
        
        \Log::info('Login - After authentication and session restore', [
            'verified_session_code' => session('verified_session_code'),
            'exam_session_code' => session('exam_session_code'),
            'current_event_id' => session('current_event_id'),
        ]);

        // test-startページへリダイレクト
        return redirect()->intended(route('test.start'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        // ★ ログアウト前にセッションコード情報を保存
        $verifiedSessionCode = session('verified_session_code');
        $examSessionCode = session('exam_session_code');
        $currentEventId = session('current_event_id');
        $currentEventName = session('current_event_name');
        
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        
        // ★★★ ログアウト後もセッションコード情報を復元（ウェルカムページに戻る）★★★
        if ($verifiedSessionCode || $examSessionCode) {
            $code = $verifiedSessionCode ?: $examSessionCode;
            session(['verified_session_code' => $code]);
            session(['exam_session_code' => $code]);
        }
        if ($currentEventId) {
            session(['current_event_id' => $currentEventId]);
        }
        if ($currentEventName) {
            session(['current_event_name' => $currentEventName]);
        }
        
        // セッションを明示的に保存
        session()->save();

        // セッションコードが有効な場合はウェルカムページへ、そうでなければセッションコード入力画面へ
        $redirectUrl = session('verified_session_code') ? route('welcome') : '/';
        
        // Inertia::locationでフルページリロードを行い、新しいCSRFトークンを取得
        return Inertia::location($redirectUrl);
    }
}
