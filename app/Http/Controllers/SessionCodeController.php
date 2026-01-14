<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class SessionCodeController extends Controller
{
    /**
     * セッションコード入力画面を表示
     */
    public function entry()
    {
        Log::info('SessionCodeEntry - entry() called', [
            'session_id' => Session::getId(),
            'has_verified_code' => Session::has('verified_session_code'),
            'verified_code' => Session::get('verified_session_code'),
        ]);

        // すでにセッションコードが検証済みの場合はウェルカムページへ
        if (Session::has('verified_session_code')) {
            Log::info('SessionCodeEntry - Already verified, redirecting to welcome');
            return redirect()->route('welcome');
        }

        return Inertia::render('SessionCodeEntry');
    }

    /**
     * セッションコードを検証
     */
    public function verify(Request $request)
    {
        Log::info('SessionCodeVerify - verify() called', [
            'session_id_before' => Session::getId(),
            'input_code' => $request->input('session_code'),
        ]);

        $request->validate([
            'session_code' => 'required|string',
        ], [
            'session_code.required' => 'セッションコードを入力してください。',
        ]);

        $sessionCode = $request->input('session_code');

        // イベントテーブルからpassphraseを検索
        $event = Event::where('passphrase', $sessionCode)->first();

        if (! $event) {
            Log::warning('SessionCodeVerify - Invalid session code', [
                'input_code' => $sessionCode,
            ]);
            return back()->withErrors([
                'session_code' => '無効なセッションコードです。正しいコードを入力してください。',
            ])->withInput();
        }

        // イベント期間のチェック
        $now = now();
        if ($event->isUpcoming()) {
            return back()->withErrors([
                'session_code' => 'このイベントはまだ開始されていません。',
            ])->withInput();
        }

        if ($event->isExpired()) {
            return back()->withErrors([
                'session_code' => 'このイベントは終了しました。',
            ])->withInput();
        }

        // ★★★ セッションに保存（明示的に保存を実行）★★★
        $request->session()->put('verified_session_code', $sessionCode);
        $request->session()->put('current_event_id', $event->id);
        $request->session()->put('current_event_name', $event->name);
        
        // ★★★ exam_session_code にも保存（互換性のため）★★★
        $request->session()->put('exam_session_code', $sessionCode);
        
        // セッションを明示的に保存
        $request->session()->save();

        Log::info('SessionCodeVerify - Session saved successfully', [
            'session_id_after' => $request->session()->getId(),
            'verified_code' => $request->session()->get('verified_session_code'),
            'event_id' => $request->session()->get('current_event_id'),
            'event_name' => $request->session()->get('current_event_name'),
            'all_session_data' => $request->session()->all(),
        ]);

        // ★★★ ウェルカムページへInertia::locationでフルリダイレクト ★★★
        // Inertia::locationを使用すると、ブラウザがフルページリロードを行い、
        // 新しいセッションCookieが確実に送信される
        return \Inertia\Inertia::location(route('welcome'));
    }

    /**
     * セッションをクリア
     */
    public function clearSession()
    {
        Log::info('SessionCodeClear - Clearing session', [
            'session_id' => Session::getId(),
            'verified_code_before' => Session::get('verified_session_code'),
        ]);

        Session::forget('verified_session_code');
        Session::forget('current_event_id');
        Session::forget('current_event_name');
        Session::save();

        return redirect()->route('session.entry');
    }
}
