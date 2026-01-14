<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Question;
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
                'question_selection_mode' => $event->question_selection_mode ?? 'random',
                'part1_questions' => $event->part1_questions,
                'part1_time' => $event->part1_time,
                'part2_questions' => $event->part2_questions,
                'part2_time' => $event->part2_time,
                'part3_questions' => $event->part3_questions,
                'part3_time' => $event->part3_time,
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
        $randomPassphrase = $this->generatePassphrase();

        // 全問題を取得（パートごとにグループ化）
        $questions = Question::with('choices')
            ->orderBy('part')
            ->orderBy('number')
            ->get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'part' => $q->part,
                    'number' => $q->number,
                    'text' => mb_substr($q->text, 0, 50) . (mb_strlen($q->text) > 50 ? '...' : ''),
                    'image' => $q->image,
                ];
            });

        return Inertia::render('Admin/Events/Create', [
            'randomPassphrase' => $randomPassphrase,
            'allQuestions' => $questions,
        ]);
    }

    /**
     * イベント作成
     */
    public function store(Request $request)
    {
        // デバッグ: 受け取ったデータを確認
        \Log::info('Received data:', $request->all());
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'passphrase' => 'required|string|max:255|unique:events,passphrase',
            'begin' => 'required|date',
            'end' => 'required|date|after:begin',
            'exam_type' => 'required|in:30min,45min,full,custom',
            'question_selection_mode' => 'required|in:sequential,random,custom',
            // すべての試験タイプで必須
            'part1_questions' => 'required|integer|min:1|max:40',
            'part1_time' => 'required|integer|min:0',
            'part2_questions' => 'required|integer|min:1|max:30',
            'part2_time' => 'required|integer|min:0',
            'part3_questions' => 'required|integer|min:1|max:25',
            'part3_time' => 'required|integer|min:0',
            // カスタム問題IDの配列（オプション）
            'custom_question_ids' => 'nullable|array',
            'custom_question_ids.*' => 'integer|exists:questions,id',
        ], [
            'name.required' => 'イベント名を入力してください',
            'passphrase.required' => 'パスフレーズを入力してください',
            'passphrase.unique' => 'このパスフレーズは既に使用されています',
            'begin.required' => '開始日時を入力してください',
            'end.required' => '終了日時を入力してください',
            'end.after' => '終了日時は開始日時より後に設定してください',
            'exam_type.required' => '出題形式を選択してください',
            'part1_questions.required' => '第一部の問題数を指定してください',
            'part1_questions.max' => '第一部の問題数は最大40問です',
            'part1_time.required' => '第一部の制限時間を指定してください',
            'part2_questions.required' => '第二部の問題数を指定してください',
            'part2_questions.max' => '第二部の問題数は最大30問です',
            'part2_time.required' => '第二部の制限時間を指定してください',
            'part3_questions.required' => '第三部の問題数を指定してください',
            'part3_questions.max' => '第三部の問題数は最大25問です',
            'part3_time.required' => '第三部の制限時間を指定してください',
        ]);

        // フロントエンドから既に秒単位で送られてくるので、変換不要
        // ただし整数型に変換
        $validated['part1_time'] = (int)$validated['part1_time'];
        $validated['part2_time'] = (int)$validated['part2_time'];
        $validated['part3_time'] = (int)$validated['part3_time'];

        // カスタム問題IDを一旦取り出す
        $customQuestionIds = $validated['custom_question_ids'] ?? [];
        unset($validated['custom_question_ids']);

        $event = Event::create($validated);

        // カスタム問題モードの場合、問題を関連付け
        if ($validated['question_selection_mode'] === 'custom' && !empty($customQuestionIds)) {
            $syncData = [];
            foreach ($customQuestionIds as $order => $questionId) {
                $syncData[$questionId] = ['order' => $order];
            }
            $event->customQuestions()->sync($syncData);
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'イベントを作成しました');
    }

    /**
     * イベント編集フォーム
     */
    public function edit($id)
    {
        $event = Event::findOrFail($id);

        // 全問題を取得
        $questions = Question::with('choices')
            ->orderBy('part')
            ->orderBy('number')
            ->get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'part' => $q->part,
                    'number' => $q->number,
                    'text' => mb_substr($q->text, 0, 50) . (mb_strlen($q->text) > 50 ? '...' : ''),
                    'image' => $q->image,
                ];
            });

        // 選択済みの問題IDを取得
        $selectedQuestionIds = $event->customQuestions()->pluck('questions.id')->toArray();

        return Inertia::render('Admin/Events/Edit', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'passphrase' => $event->passphrase,
                'begin' => $event->begin->format('Y-m-d\TH:i'),
                'end' => $event->end->format('Y-m-d\TH:i'),
                'exam_type' => $event->exam_type,
                'question_selection_mode' => $event->question_selection_mode ?? 'random',
                'part1_questions' => $event->part1_questions,
                'part1_time' => $event->part1_time, // 秒単位のまま渡す（Vueで分に変換）
                'part2_questions' => $event->part2_questions,
                'part2_time' => $event->part2_time,
                'part3_questions' => $event->part3_questions,
                'part3_time' => $event->part3_time,
                'custom_question_ids' => $selectedQuestionIds,
            ],
            'allQuestions' => $questions,
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
            'exam_type' => 'required|in:30min,45min,full,custom',
            'question_selection_mode' => 'required|in:sequential,random,custom',
            'part1_questions' => 'required|integer|min:1|max:40',
            'part1_time' => 'required|integer|min:0',
            'part2_questions' => 'required|integer|min:1|max:30',
            'part2_time' => 'required|integer|min:0',
            'part3_questions' => 'required|integer|min:1|max:25',
            'part3_time' => 'required|integer|min:0',
            // カスタム問題IDの配列（オプション）
            'custom_question_ids' => 'nullable|array',
            'custom_question_ids.*' => 'integer|exists:questions,id',
        ], [
            'name.required' => 'イベント名を入力してください',
            'passphrase.required' => 'パスフレーズを入力してください',
            'passphrase.unique' => 'このパスフレーズは既に使用されています',
            'begin.required' => '開始日時を入力してください',
            'end.required' => '終了日時を入力してください',
            'end.after' => '終了日時は開始日時より後に設定してください',
            'exam_type.required' => '出題形式を選択してください',
        ]);

        // フロントエンドから既に秒単位で送られてくるので、変換不要
        // ただし整数型に変換
        $validated['part1_time'] = (int)$validated['part1_time'];
        $validated['part2_time'] = (int)$validated['part2_time'];
        $validated['part3_time'] = (int)$validated['part3_time'];

        // カスタム問題IDを一旦取り出す
        $customQuestionIds = $validated['custom_question_ids'] ?? [];
        unset($validated['custom_question_ids']);

        $event->update($validated);

        // カスタム問題を更新
        if ($validated['question_selection_mode'] === 'custom' && !empty($customQuestionIds)) {
            $syncData = [];
            foreach ($customQuestionIds as $order => $questionId) {
                $syncData[$questionId] = ['order' => $order];
            }
            $event->customQuestions()->sync($syncData);
        } else {
            // ランダムモードの場合、カスタム問題をクリア
            $event->customQuestions()->detach();
        }

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
        $event->end = Carbon::now();
        $event->save();

        return redirect()->route('admin.events.index')
            ->with('success', 'イベントを早期終了しました');
    }

    /**
     * ランダムなパスフレーズを生成(API)
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
        do {
            $passphrase = strtolower(Str::random(8));
        } while (Event::where('passphrase', $passphrase)->exists());

        return $passphrase;
    }
}