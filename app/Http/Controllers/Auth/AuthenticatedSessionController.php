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
        $sessionCodeId = session('session_code_id');
        
        // webガード（一般ユーザー）で認証
        $request->authenticate();

        $request->session()->regenerate();

        // ★ セッションコード情報を復元
        if ($verifiedSessionCode) {
            session(['verified_session_code' => $verifiedSessionCode]);
        }
        if ($sessionCodeId) {
            session(['session_code_id' => $sessionCodeId]);
        }

        // test-startページへリダイレクト
        return redirect()->intended(route('test.start'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
