<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventManagementController extends Controller
{
    /**
     * イベント一覧
     */
    public function index()
    {
        $events = Event::orderBy('begin', 'desc')->get()->map(function ($event) {
            $now = Carbon::now();

            // ステータス判定
            if ($now->lt($event->begin)) {
                $status = '開始前';
                $statusColor = 'blue';
            } elseif ($now->between($event->begin, $event->end)) {
                $status = '実施中';
                $statusColor = 'green';
            } else {
                $status = '終了';
                $statusColor = 'gray';
            }

            return [
                'id' => $event->id,
                'name' => $event->name,
                'passphrase' => $event->passphrase,
                'begin' => $event->begin->toIso8601String(),
                'end' => $event->end->toIso8601String(),
                'exam_type' => $event->exam_type,
                'status' => $status,
                'status_color' => $statusColor,
                'created_at' => $event->created_at->toIso8601String(),
            ];
        });

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
        ]);
    }

    /**
     * イベント作成フォーム
     */
    public function create()
    {
        // ランダムなパスフレーズを生成
        $randomPassphrase = $this->generatePassphrase();

        return Inertia::render('Admin/Events/Create', [
            'randomPassphrase' => $randomPassphrase,
        ]);
    }

    /**
     * イベント作成
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'passphrase' => 'required|string|max:255|unique:events,passphrase',
            'begin' => 'required|date',
            'end' => 'required|date|after:begin',
            'exam_type' => 'required|in:30min,45min,full',
        ], [
            'name.required' => 'イベント名を入力してください',
            'passphrase.required' => 'パスフレーズを入力してください',
            'passphrase.unique' => 'このパスフレーズは既に使用されています',
            'begin.required' => '開始日時を入力してください',
            'end.required' => '終了日時を入力してください',
            'end.after' => '終了日時は開始日時より後に設定してください',
            'exam_type.required' => '出題形式を選択してください',
        ]);

        Event::create($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'イベントを作成しました');
    }

    /**
     * イベント編集フォーム
     */
    public function edit($id)
    {
        $event = Event::findOrFail($id);

        return Inertia::render('Admin/Events/Edit', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'passphrase' => $event->passphrase,
                'begin' => $event->begin->format('Y-m-d\TH:i'),
                'end' => $event->end->format('Y-m-d\TH:i'),
                'exam_type' => $event->exam_type,
            ],
        ]);
    }

    /**
     * イベント更新
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'passphrase' => 'required|string|max:255|unique:events,passphrase,'.$id,
            'begin' => 'required|date',
            'end' => 'required|date|after:begin',
            'exam_type' => 'required|in:30min,45min,full',
        ], [
            'name.required' => 'イベント名を入力してください',
            'passphrase.required' => 'パスフレーズを入力してください',
            'passphrase.unique' => 'このパスフレーズは既に使用されています',
            'begin.required' => '開始日時を入力してください',
            'end.required' => '終了日時を入力してください',
            'end.after' => '終了日時は開始日時より後に設定してください',
            'exam_type.required' => '出題形式を選択してください',
        ]);

        $event->update($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'イベントを更新しました');
    }

    /**
     * イベント削除
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'イベントを削除しました');
    }

    /**
     * イベント早期終了
     */
    public function terminate($id)
    {
        $event = Event::findOrFail($id);

        // 現在時刻に終了日時を設定
        $event->end = Carbon::now();
        $event->save();

        return redirect()->route('admin.events.index')
            ->with('success', 'イベントを早期終了しました');
    }

    /**
     * ランダムなパスフレーズを生成（API）
     */
    public function generateRandomPassphrase()
    {
        return response()->json([
            'passphrase' => $this->generatePassphrase(),
        ]);
    }

    /**
     * パスフレーズ生成ロジック
     */
    private function generatePassphrase()
    {
        // 形式: XXXX-XXXX-XXXX (英数字)
        $part1 = strtoupper(Str::random(4));
        $part2 = strtoupper(Str::random(4));
        $part3 = strtoupper(Str::random(4));

        return "{$part1}-{$part2}-{$part3}";
    }
}
