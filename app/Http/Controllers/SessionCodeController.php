<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class SessionCodeController extends Controller
{
    /**
     * セッションコード入力画面を表示
     */
    public function entry()
    {
        // すでにセッションコードが検証済みの場合はウェルカムページへ
        if (Session::has('verified_session_code')) {
            return redirect()->route('welcome');
        }

        return Inertia::render('SessionCodeEntry');
    }

    /**
     * セッションコードを検証
     */
    public function verify(Request $request)
    {
        $request->validate([
            'session_code' => 'required|string',
        ], [
            'session_code.required' => 'セッションコードを入力してください。',
        ]);

        $sessionCode = $request->input('session_code');

        // イベントテーブルからpassphraseを検索
        $event = Event::where('passphrase', $sessionCode)->first();

        if (! $event) {
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

        // セッションに保存
        Session::put('verified_session_code', $sessionCode);
        Session::put('current_event_id', $event->id);
        Session::put('current_event_name', $event->name);

        // ウェルカムページへリダイレクト
        return redirect()->route('welcome');
    }

    /**
     * セッションをクリア
     */
    public function clearSession()
    {
        Session::forget('verified_session_code');
        Session::forget('current_event_id');
        Session::forget('current_event_name');

        return redirect()->route('session.entry');
    }
}
