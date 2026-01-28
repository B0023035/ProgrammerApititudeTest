<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\ExamSession;
use App\Models\Question;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ExamController extends Controller
{
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    /**
 * 本番テスト開始処理(セッションコード必須版)
 */
public function start()
{
    $user = Auth::user();

    Log::info('=== exam.start 呼び出し ===', [
        'user_id' => $user->id,
    ]);

    // ★ セッションコードの確認(最重要)
    $sessionCode = session('exam_session_code') ?? session('verified_session_code');
    
    if (!$sessionCode) {
        Log::error('セッションコードが未入力', [
            'user_id' => $user->id,
        ]);
        return redirect()->route('test.start')
            ->with('error', 'セッションコードを入力してください。');
    }

    // イベント情報を取得
    $event = $this->getEventBySessionCode($sessionCode);

    if (!$event) {
        Log::error('セッションコードが無効', [
            'user_id' => $user->id,
            'session_code' => $sessionCode,
        ]);
        
        // セッションコードをクリア
        session()->forget(['exam_session_code', 'verified_session_code']);
        
        return redirect()->route('test.start')
            ->with('error', 'セッションコードが無効または期限切れです。');
    }

    $examType = $event->exam_type;

    Log::info('試験タイプ確認', [
        'user_id' => $user->id,
        'exam_type' => $examType,
        'event_id' => $event->id,
        'event_name' => $event->name,
    ]);

    // 未完了セッションがあるかチェック
    $existingSession = ExamSession::where('user_id', $user->id)
        ->where('event_id', $event->id)
        ->whereNull('finished_at')
        ->whereNull('disqualified_at')
        ->first();

    if ($existingSession) {
        Log::info('既存セッションに復帰', [
            'user_id' => $user->id,
            'exam_session_id' => $existingSession->id,
            'current_part' => $existingSession->current_part,
            'event_id' => $event->id,
        ]);

        return redirect()->route('exam.part', ['part' => $existingSession->current_part]);
    }

    // セッション実施時の学年を計算して保存（卒業年度から計算）
    $currentYear = (int) date('Y');
    $graduationYear = (int) ($user->graduation_year ?? 0);
    // 卒業年度 - 現在年 + 1 = 学年（例: 2028年卒業, 2026年現在 → 2028-2026+1=3年生）
    $grade = $graduationYear > 0 ? max(1, min(($graduationYear - $currentYear + 1), 10)) : null;

    // 問題選択モードに応じて各パートの問題を事前に決定
    $selectionMode = $event->question_selection_mode ?? 'sequential';
    $questionIds = $this->determineQuestionIds($event, $selectionMode, $examType);

    // 新規セッション作成
    $session = ExamSession::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'grade' => $grade,
        'started_at' => now(),
        'current_part' => 1,
        'current_question' => 1,
        'remaining_time' => 0,
        'security_log' => json_encode([
            'exam_type' => $examType,
            'event_id' => $event->id,
            'event_name' => $event->name,
            'selection_mode' => $selectionMode,
            'question_ids' => $questionIds,
        ]),
    ]);

    Log::info('新しい試験セッション作成', [
        'user_id' => $user->id,
        'exam_session_id' => $session->id,
        'session_uuid' => $session->session_uuid,
        'exam_type' => $examType,
        'event_id' => $event->id,
        'starting_part' => 1,
        'selection_mode' => $selectionMode,
        'question_ids' => $questionIds,
    ]);

    return redirect()->route('exam.part', ['part' => 1]);
}


    /**
     * ユーザーのすべてのキャッシュを削除
     */
    private function cleanupAllUserCache($userId)
    {
        // パートごとの解答キャッシュを削除
        for ($part = 1; $part <= 3; $part++) {
            Cache::forget("exam_answers_{$userId}_{$part}");
            Cache::forget("guest_exam_answers_{$userId}_part_{$part}");
            Cache::forget("guest_exam_result_{$userId}_part_{$part}");
        }

        // パートセッションキーをすべて削除
        $partSessionKeys = Cache::get("exam_part_session_keys_{$userId}", []);
        foreach ($partSessionKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget("exam_part_session_keys_{$userId}");

        // 練習問題のキャッシュも削除
        for ($part = 1; $part <= 3; $part++) {
            $practiceKeys = Cache::get("practice_session_keys_{$userId}_{$part}", []);
            foreach ($practiceKeys as $key) {
                Cache::forget($key);
            }
            Cache::forget("practice_session_keys_{$userId}_{$part}");
        }

        Log::info('ユーザーのすべてのキャッシュを削除', [
            'user_id' => $userId,
            'cache_keys_deleted' => count($partSessionKeys ?? []),
        ]);
    }

    /**
     * 問題選択モードに応じて各パートの問題IDを決定
     */
    private function determineQuestionIds($event, $selectionMode, $examType)
    {
        $questionIds = [
            'part_1' => [],
            'part_2' => [],
            'part_3' => [],
        ];

        for ($part = 1; $part <= 3; $part++) {
            $questionCount = $this->getQuestionCountByEvent($part, $examType, $event);

            if ($selectionMode === 'custom' && $event->isCustomQuestionMode()) {
                // カスタム問題モード: イベントに紐付けられた問題を使用
                $questionIds["part_{$part}"] = $event->getCustomQuestionsForPart($part)
                    ->pluck('id')
                    ->toArray();
            } elseif ($selectionMode === 'random') {
                // ランダムモード: 問題をランダムに選択
                $questionIds["part_{$part}"] = Question::where('part', $part)
                    ->inRandomOrder()
                    ->take($questionCount)
                    ->pluck('id')
                    ->toArray();
            } else {
                // シーケンシャルモード（デフォルト）: 問題番号順に選択
                $questionIds["part_{$part}"] = Question::where('part', $part)
                    ->orderBy('number')
                    ->take($questionCount)
                    ->pluck('id')
                    ->toArray();
            }
        }

        return $questionIds;
    }

/**
 * パート画面表示(セッションコード必須版)
 */
public function part(Request $request, $part)
{
    $user = Auth::user();
    $part = (int) $part;

    Log::info('=== exam.part 呼び出し ===', [
        'user_id' => $user->id,
        'requested_part' => $part,
    ]);

    // パート番号の検証
    if (!in_array($part, [1, 2, 3])) {
        Log::warning('無効なパート番号でリダイレクト', [
            'user_id' => $user->id,
            'part' => $part,
        ]);
        return redirect()->route('test.start')
            ->with('error', '無効なパート番号です。');
    }

    // ★ セッションコードの確認(最重要)
    $sessionCode = session('exam_session_code') ?? session('verified_session_code');
    
    if (!$sessionCode) {
        Log::error('セッションコードが未設定', [
            'user_id' => $user->id,
            'part' => $part,
        ]);
        return redirect()->route('test.start')
            ->with('error', 'セッションコードを入力してください。');
    }

    // イベント情報を取得
    $event = $this->getEventBySessionCode($sessionCode);

    if (!$event) {
        Log::error('セッションコードが無効', [
            'user_id' => $user->id,
            'session_code' => $sessionCode,
        ]);
        
        session()->forget(['exam_session_code', 'verified_session_code']);
        
        return redirect()->route('test.start')
            ->with('error', 'セッションコードが無効または期限切れです。');
    }

    $examType = $event->exam_type;

    Log::info('イベント情報取得成功', [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'exam_type' => $examType,
        'event_name' => $event->name,
    ]);

    // セッション取得
    $session = ExamSession::where('user_id', $user->id)
        ->where('event_id', $event->id)
        ->whereNull('finished_at')
        ->whereNull('disqualified_at')
        ->first();

    Log::info('既存セッションの確認', [
        'user_id' => $user->id,
        'session_found' => $session ? 'yes' : 'no',
        'session_id' => $session ? $session->id : null,
    ]);

    // セッションがない場合は新規作成
    if (!$session) {
        Log::info('セッションが存在しないため新規作成', [
            'user_id' => $user->id,
            'requested_part' => $part,
            'exam_type' => $examType,
        ]);

        $currentYear = (int) date('Y');
        $graduationYear = (int) ($user->graduation_year ?? 0);
        // 卒業年度 - 現在年 + 1 = 学年
        $grade = $graduationYear > 0 ? max(1, min(($graduationYear - $currentYear + 1), 10)) : null;

        $session = ExamSession::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'grade' => $grade,
            'started_at' => now(),
            'current_part' => $part,
            'current_question' => 1,
            'remaining_time' => 0,
            'security_log' => json_encode([
                'exam_type' => $examType,
                'event_id' => $event->id,
            ]),
        ]);

        Log::info('新規セッション作成完了', [
            'user_id' => $user->id,
            'exam_session_id' => $session->id,
            'starting_part' => $part,
            'exam_type' => $examType,
        ]);
    }

    // 既に完了したパートへのアクセス試行をチェック
    if ($part < $session->current_part) {
        Log::warning('既に完了したパートへのアクセス試行', [
            'user_id' => $user->id,
            'requested_part' => $part,
            'current_part' => $session->current_part,
        ]);
        
        return redirect()->route('exam.part', ['part' => $session->current_part])
            ->with('info', "第{$part}部は既に完了しています。第{$session->current_part}部から続けてください。");
    }

    // current_part を更新(要求されたパートに進む)
    if ($part > $session->current_part) {
        $session->update([
            'current_part' => $part,
        ]);
        
        Log::info('パート進行', [
            'user_id' => $user->id,
            'from_part' => $session->current_part,
            'to_part' => $part,
        ]);
    }

    // 完了済みセッションの場合は新規作成を促す
    if ($session->finished_at) {
        Log::warning('完了済みセッションへのアクセス', [
            'user_id' => $user->id,
            'exam_session_id' => $session->id,
            'finished_at' => $session->finished_at,
        ]);

        return redirect()->route('test.start')
            ->with('error', '試験は既に完了しています。新しい試験を開始してください。');
    }

    // パート時間制限を取得
    $partTimeLimit = $this->getPartTimeLimitByEvent($part, $examType, $event);

    Log::info('パート時間制限取得', [
        'part' => $part,
        'exam_type' => $examType,
        'event_id' => $event->id,
        'time_limit_seconds' => $partTimeLimit,
        'time_limit_minutes' => $partTimeLimit > 0 ? round($partTimeLimit / 60, 2) : 'unlimited',
    ]);

    // 残り時間の処理
    if ($session->remaining_time > 0) {
        $remainingTime = $session->remaining_time;
        Log::info('既存の残り時間を使用', [
            'remaining_time' => $remainingTime,
            'remaining_minutes' => round($remainingTime / 60, 2),
        ]);
    } else {
        // 初回アクセス時:パート時間制限を設定
        $remainingTime = $partTimeLimit;

        $session->update([
            'remaining_time' => $remainingTime,
            'started_at' => $session->started_at ?? now(),
        ]);
        
        Log::info('新規に残り時間を設定', [
            'remaining_time' => $remainingTime,
            'part_time_limit' => $partTimeLimit,
            'remaining_minutes' => $remainingTime > 0 ? round($remainingTime / 60, 2) : 'unlimited',
        ]);
    }

    // 時間切れチェック(0=無制限の場合はスキップ)
    if ($partTimeLimit > 0 && $remainingTime <= 0) {
        return $this->autoCompletePartDueToTimeout($session);
    }

    // セキュリティ用のセッションIDを生成
    $sessionId = (string) Str::uuid();

    // セッションキーを記録
    $sessionKeys = Cache::get("exam_part_session_keys_{$user->id}", []);
    $sessionKeys[] = "exam_part_session_{$user->id}_{$sessionId}";
    Cache::put("exam_part_session_keys_{$user->id}", $sessionKeys, 2 * 60 * 60);

    // セッション情報をキャッシュに保存(30分で期限切れ)
    Cache::put("exam_part_session_{$user->id}_{$sessionId}", [
        'user_id' => $user->id,
        'exam_session_id' => $session->id,
        'part' => $part,
        'started_at' => now(),
        'exam_type' => $examType,
        'event_id' => $event->id,
    ], 30 * 60);

    // security_log から保存済みの解答を取得
    $securityLog = json_decode($session->security_log ?? '{}', true);
    $savedAnswers = $securityLog['part_'.$part.'_answers'] ?? [];
    
    // セッション作成時に決定された問題IDを取得
    $questionIds = $securityLog['question_ids']['part_'.$part] ?? [];

    Log::info('保存済み解答の読み込み', [
        'user_id' => $user->id,
        'part' => $part,
        'saved_answers_count' => count($savedAnswers),
        'saved_question_ids_count' => count($questionIds),
        'exam_type' => $examType,
    ]);

    // 問題数を取得
    $questionCount = $this->getQuestionCountByEvent($part, $examType, $event);

    Log::info('問題数取得', [
        'part' => $part,
        'exam_type' => $examType,
        'question_count' => $questionCount,
    ]);

    // セッションに問題IDが保存されている場合はそれを使用、そうでなければ問題選択モードに従う
    if (!empty($questionIds)) {
        // セッションに保存された問題IDを使用（ランダムでも同じ問題が出る）
        Log::info('セッションに保存された問題IDを使用', [
            'question_ids' => $questionIds,
        ]);
        
        $displayNumber = 0;
        $questions = Question::with(['choices' => function ($query) use ($part) {
            $query->where('part', $part)->orderBy('label');
        }])
            ->whereIn('id', $questionIds)
            ->get()
            // 保存された順序を維持
            ->sortBy(function($q) use ($questionIds) {
                return array_search($q->id, $questionIds);
            })
            ->values()
            ->map(function ($q) use ($savedAnswers, &$displayNumber) {
                $displayNumber++;
                return [
                    'id' => $q->id,
                    'number' => $displayNumber, // 表示用の連番
                    'original_number' => $q->number, // DBの元の問題番号
                    'part' => $q->part,
                    'text' => $q->text,
                    'image' => $q->image,
                    'choices' => $q->choices->map(function ($c) {
                        return [
                            'id' => $c->id,
                            'label' => $c->label,
                            'text' => $c->text,
                            'image' => $c->image,
                            'part' => $c->part,
                        ];
                    }),
                    'selected' => isset($savedAnswers[$q->id]) ? $savedAnswers[$q->id] : null,
                ];
            });
    } else {
        // 旧セッション互換: 問題選択モードを取得して問題を選択
        $selectionMode = $event->question_selection_mode ?? 'sequential';

        Log::info('問題選択モード（旧セッション互換）', [
            'selection_mode' => $selectionMode,
            'event_id' => $event->id,
        ]);

        // 問題を取得（モードに応じて異なる取得方法）
        if ($selectionMode === 'custom' && $event->isCustomQuestionMode()) {
            // カスタム問題モード: イベントに紐付けられた問題のみを取得
            $displayNumber = 0;
            $questions = $event->getCustomQuestionsForPart($part)
                ->load(['choices' => function ($query) use ($part) {
                    $query->where('part', $part)->orderBy('label');
                }])
                ->map(function ($q) use ($savedAnswers, &$displayNumber) {
                    $displayNumber++;
                    return [
                        'id' => $q->id,
                        'number' => $displayNumber, // 表示用の連番
                        'original_number' => $q->number, // DBの元の問題番号
                        'part' => $q->part,
                        'text' => $q->text,
                        'image' => $q->image,
                        'choices' => $q->choices->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'label' => $c->label,
                                'text' => $c->text,
                                'image' => $c->image,
                                'part' => $c->part,
                            ];
                        }),
                        'selected' => isset($savedAnswers[$q->id]) ? $savedAnswers[$q->id] : null,
                    ];
                });
        } else {
            // シーケンシャルモード（デフォルト）: 問題番号順に取得
            $displayNumber = 0;
            $questions = Question::with(['choices' => function ($query) use ($part) {
                $query->where('part', $part)->orderBy('label');
            }])
                ->where('part', $part)
                ->orderBy('number')
                ->take($questionCount)
                ->get()
                ->map(function ($q) use ($savedAnswers, &$displayNumber) {
                    $displayNumber++;
                    return [
                        'id' => $q->id,
                        'number' => $displayNumber, // 表示用の連番
                        'original_number' => $q->number, // DBの元の問題番号
                        'part' => $q->part,
                        'text' => $q->text,
                        'image' => $q->image,
                        'choices' => $q->choices->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'label' => $c->label,
                                'text' => $c->text,
                                'image' => $c->image,
                                'part' => $c->part,
                            ];
                        }),
                        'selected' => isset($savedAnswers[$q->id]) ? $savedAnswers[$q->id] : null,
                    ];
                });
        }
    }

    Log::info('問題データの生成完了', [
        'user_id' => $user->id,
        'part' => $part,
        'questions_count' => $questions->count(),
        'exam_type' => $examType,
        'expected_count' => $questionCount,
    ]);

    return Inertia::render('Part', [
        'examSessionId' => $sessionId,
        'practiceSessionId' => $sessionId,
        'practiceQuestions' => $questions,
        'part' => $part,
        'questions' => $questions,
        'currentPart' => $part,
        'partTime' => $partTimeLimit,
        'remainingTime' => $remainingTime,
        'currentQuestion' => $session->current_question,
        'totalParts' => 3,
        'examType' => $examType,
    ]);
}

    /**
 * パート完了処理(改善版) - トランザクションスコープ最適化
 */
public function completePart(Request $request)
{
    $validated = $request->validate([
        '_token' => 'required|string',  // ★ CSRF トークン検証
        'part' => 'required|integer|min:1|max:3',
        'examSessionId' => 'required|string',
        'answers' => 'nullable|array',
        'timeSpent' => 'nullable|integer|min:0',
        'startTime' => 'nullable|integer',
        'endTime' => 'nullable|integer',
        'totalQuestions' => 'required|integer|min:1',
    ]);

    $user = Auth::user();
    $part = $validated['part'];
    $cacheSessionId = $validated['examSessionId'];

    Log::info("=== completePart 開始 ===", [
        'user_id' => $user->id,
        'part' => $part,
        'cache_session_id' => $cacheSessionId,
        'answers_count' => count($validated['answers'] ?? []),
    ]);

    // ★改善1: トランザクション外でデータ準備
    $cacheKey = "exam_part_session_{$user->id}_{$cacheSessionId}";
    $cacheSession = Cache::get($cacheKey);

    if (!$cacheSession) {
        Log::error('キャッシュセッション見つからず', [
            'user_id' => $user->id,
            'cache_key' => $cacheKey,
        ]);
        return back()->withErrors(['examSessionId' => '無効なセッションです。']);
    }

    $examType = $cacheSession['exam_type'] ?? 'full';
    $answers = $validated['answers'] ?? [];

    try {
        // ★改善2: 短いトランザクションで検証とロック
        DB::beginTransaction();

        $examSession = ExamSession::lockForUpdate()
            ->find($cacheSession['exam_session_id']);

        if (!$examSession 
            || $examSession->user_id !== $user->id
            || $examSession->finished_at
            || $examSession->disqualified_at) {
            DB::rollBack();
            Log::error('ExamSession見つからずまたは無効', [
                'user_id' => $user->id,
                'exam_session_id' => $cacheSession['exam_session_id'],
            ]);
            return back()->withErrors(['examSessionId' => '試験セッションが見つかりません。']);
        }

        // security_log更新
        $securityLog = json_decode($examSession->security_log ?? '{}', true);
        
        if (!isset($securityLog['part_'.$part.'_answers'])) {
            $securityLog['part_'.$part.'_answers'] = [];
        }

        foreach ($answers as $questionId => $choice) {
            $securityLog['part_'.$part.'_answers'][$questionId] = $choice;
        }

        $examSession->update([
            'security_log' => json_encode($securityLog),
        ]);

        DB::commit();

        Log::info("第{$part}部完了 - security_log更新", [
            'user_id' => $user->id,
            'part' => $part,
            'answers_count' => count($securityLog['part_'.$part.'_answers']),
            'is_empty' => count($answers) === 0 ? 'yes' : 'no',
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Part completion failed (security_log update)', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return back()->withErrors(['general' => 'システムエラーが発生しました。']);
    }

    // ★改善3: 次のアクション決定（トランザクション外）
    if ($part < 3) {
        // 第一部・第二部完了後は次の部の練習問題へ
        try {
            DB::beginTransaction();

            $examSession = ExamSession::lockForUpdate()
                ->find($examSession->id);

            $nextPart = $part + 1;
            $examSession->update([
                'current_part' => $nextPart,
                'current_question' => 1,
                'remaining_time' => 0,
            ]);

            DB::commit();

            Cache::forget($cacheKey);

            Log::info("第{$part}部完了 - 第{$nextPart}部練習問題へ遷移", [
                'user_id' => $user->id,
                'completed_part' => $part,
                'next_part' => $nextPart,
            ]);

            return redirect()->route('practice.show', ['section' => $nextPart])
                ->with('success', "第{$part}部が完了しました。第{$nextPart}部の練習問題を開始してください。");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('次のパートへの遷移失敗', [
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['general' => 'システムエラーが発生しました。']);
        }
                
    } else {
        // ★改善4: 第三部完了後は全パートの解答をanswerテーブルに保存
        Log::info('第三部完了 - 全パート採点開始', [
            'user_id' => $user->id,
            'exam_session_id' => $examSession->id,
        ]);

        // トランザクション外でデータ準備
        $securityLog = json_decode($examSession->security_log ?? '{}', true);
        $allAnswers = $this->collectAllAnswers($securityLog);

        Log::info('全パート解答統合完了', [
            'user_id' => $user->id,
            'total_answers' => count($allAnswers),
            'part1_answers' => count($securityLog['part_1_answers'] ?? []),
            'part2_answers' => count($securityLog['part_2_answers'] ?? []),
            'part3_answers' => count($securityLog['part_3_answers'] ?? []),
        ]);

        // トランザクション外で採点データ準備
        $answersToInsert = $this->prepareAnswersForInsert($allAnswers, $user, $examSession);

        // ★改善5: 短いトランザクションで一括UPSERT
        try {
            DB::beginTransaction();

            if (!empty($answersToInsert)) {
                Answer::upsert(
                    $answersToInsert,
                    ['user_id', 'question_id'],
                    ['exam_session_id', 'part', 'choice', 'is_correct', 'updated_at']
                );
            }

            // セッション完了
            $examSession = ExamSession::lockForUpdate()
                ->find($examSession->id);

            $examSession->update([
                'finished_at' => now(),
                'current_part' => 3,
                'security_log' => null,
            ]);

            DB::commit();

            // キャッシュクリーンアップ
            Cache::forget($cacheKey);
            for ($p = 1; $p <= 3; $p++) {
                Cache::forget("exam_answers_{$user->id}_{$p}");
            }

            Log::info('試験完了 - 全パート採点完了', [
                'user_id' => $user->id,
                'exam_session_id' => $examSession->id,
                'total_answers' => count($allAnswers),
                'saved_count' => count($answersToInsert),
            ]);

            return redirect()->route('exam.result', ['sessionUuid' => $examSession->session_uuid])
                ->with('success', '試験が完了しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('試験完了処理失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['general' => 'システムエラーが発生しました。']);
        }
    }
}

/**
 * ★新規追加: security_logから全パートの解答を収集
 */
private function collectAllAnswers(array $securityLog): array
{
    $allAnswers = [];
    for ($p = 1; $p <= 3; $p++) {
        if (isset($securityLog['part_'.$p.'_answers'])) {
            $allAnswers = $allAnswers + $securityLog['part_'.$p.'_answers'];
        }
    }
    return $allAnswers;
}

/**
 * ★新規追加: 採点データを準備
 */
private function prepareAnswersForInsert(array $allAnswers, $user, $examSession): array
{
    $questionIds = array_keys($allAnswers);
    
    $questions = Question::with(['choices' => function ($query) {
        $query->where('is_correct', 1);
    }])
    ->whereIn('id', $questionIds)
    ->get()
    ->keyBy('id');
    
    $answersToInsert = [];
    
    foreach ($allAnswers as $questionId => $choice) {
        if (!is_numeric($questionId) || !in_array($choice, ['A', 'B', 'C', 'D', 'E'])) {
            continue;
        }

        $question = $questions->get($questionId);
        if (!$question) {
            continue;
        }

        $correctChoice = $question->choices->first();
        $isCorrect = false;
        
        if ($correctChoice) {
            $isCorrect = (trim($correctChoice->label) === trim($choice));
        }

        $answersToInsert[] = [
            'user_id' => $user->id,
            'exam_session_id' => $examSession->id,
            'question_id' => $questionId,
            'part' => $question->part,
            'choice' => $choice,
            'is_correct' => $isCorrect,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    return $answersToInsert;
}

    /**
     * キャッシュクリーンアップ用のヘルパーメソッド
     */
    private function cleanupExamCache($userId, $examSessionId)
    {
        // 全パートの解答キャッシュを削除
        for ($part = 1; $part <= 3; $part++) {
            Cache::forget("exam_answers_{$userId}_{$part}");
        }
    }

    /**
     * ゲストユーザー用キャッシュクリーンアップ
     */
    private function cleanupGuestExamCache($guestId, $sessionId)
    {
        // 全パートの解答キャッシュを削除
        for ($part = 1; $part <= 3; $part++) {
            Cache::forget("guest_exam_answers_{$guestId}_part_{$part}");
            Cache::forget("guest_exam_result_{$guestId}_part_{$part}");
        }
    }

    /**
 * 問題をバッチで取得(5問ずつ) - インデックス最適化版
 */
public function getQuestionsBatch(Request $request, $part, $offset = 0)
{
    $user = Auth::user();
    $part = (int) $part;
    $offset = (int) $offset;
    $batchSize = 5; // 一度に5問取得

    // パート番号の検証
    if (!in_array($part, [1, 2, 3])) {
        return response()->json(['error' => '無効なパート番号です'], 400);
    }

    // ★改善1: セッションコードの確認
    $sessionCode = session('exam_session_code') ?? session('verified_session_code');
    $event = $sessionCode ? $this->getEventBySessionCode($sessionCode) : null;
    $examType = $event ? $event->exam_type : 'full';

    // 問題数を取得
    $questionCount = $this->getQuestionCountByEvent($part, $examType, $event);

    // ★改善2: プライマリキーとインデックスを使った効率的な取得
    // 順序付きロック: user_id, event_id の順で検索
    $session = ExamSession::where('user_id', $user->id)
        ->when($event, function ($query) use ($event) {
            return $query->where('event_id', $event->id);
        })
        ->whereNull('finished_at')
        ->whereNull('disqualified_at')
        ->orderBy('id', 'asc') // ★追加: デッドロック防止
        ->first();

    if (!$session) {
        return response()->json(['error' => 'セッションが見つかりません'], 404);
    }

    // security_logから保存済みの解答を取得
    $securityLog = json_decode($session->security_log ?? '{}', true);
    $savedAnswers = $securityLog['part_'.$part.'_answers'] ?? [];

    // ★改善3: 必要なカラムのみ取得してパフォーマンス向上
    // インデックスを活用: part + number の複合インデックス
    $questions = Question::select(['id', 'number', 'part', 'text', 'image'])
        ->with(['choices' => function ($query) use ($part) {
            $query->select(['id', 'question_id', 'label', 'text', 'image', 'part', 'is_correct'])
                ->where('part', $part)
                ->orderBy('label');
        }])
        ->where('part', $part)
        ->orderBy('number')
        ->offset($offset)
        ->limit(min($batchSize, $questionCount - $offset))
        ->get()
        ->map(function ($q) use ($savedAnswers) {
            return [
                'id' => $q->id,
                'number' => $q->number,
                'part' => $q->part,
                'text' => $q->text,
                'image' => $q->image,
                'choices' => $q->choices->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'label' => $c->label,
                        'text' => $c->text,
                        'image' => $c->image,
                        'part' => $c->part,
                    ];
                }),
                'selected' => $savedAnswers[$q->id] ?? null,
            ];
        });

    Log::info('問題バッチ取得成功', [
        'user_id' => $user->id,
        'part' => $part,
        'offset' => $offset,
        'batch_size' => $batchSize,
        'retrieved' => $questions->count(),
        'has_more' => ($offset + $batchSize) < $questionCount,
    ]);

    return response()->json([
        'questions' => $questions,
        'hasMore' => ($offset + $batchSize) < $questionCount,
        'total' => $questionCount,
        'loaded' => $offset + $questions->count(),
    ]);
}

    // ===== プライベートメソッド =====

    /**
     * 試験提出データの検証
     */
    private function validateExamSubmission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'examSessionId' => 'required|string|size:36',
            'part' => 'required|integer|in:1,2,3',
            'answers' => 'required|array|max:50',
            'answers.*' => 'string|in:A,B,C,D,E',
            'timeSpent' => 'integer|min:1|max:3600',
            'securityLog' => 'array',
        ]);

        if ($validator->fails()) {
            Log::warning('試験データ検証失敗', [
                'user_id' => Auth::id(),
                'errors' => $validator->errors(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('test.start')
                ->withErrors($validator)
                ->with('error', '送信されたデータが正しくありません。');
        }

        return true;
    }

    /**
     * ゲスト試験提出データの検証
     */
    private function validateGuestExamSubmission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'examSessionId' => 'required|string|size:36',
            'part' => 'required|integer|in:1,2,3',
            'answers' => 'required|array|max:50',
            'answers.*' => 'string|in:A,B,C,D,E',
            'timeSpent' => 'integer|min:1|max:3600',
        ]);

        if ($validator->fails()) {
            Log::warning('ゲスト試験データ検証失敗', [
                'guest_id' => session()->getId(),
                'errors' => $validator->errors(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('guest.test.start')
                ->withErrors($validator)
                ->with('error', '送信されたデータが正しくありません。');
        }

        return true;
    }

    /**
     * 回答データのサニタイズ
     */
    private function sanitizeAnswers(array $answers): array
    {
        $sanitized = [];
        $validChoices = ['A', 'B', 'C', 'D', 'E'];

        foreach ($answers as $questionId => $answer) {
            // 問題IDは数値のみ
            if (! is_numeric($questionId) || $questionId < 1) {
                continue;
            }

            // 回答は A-E のみ
            $cleanAnswer = strtoupper(trim($answer));
            if (in_array($cleanAnswer, $validChoices)) {
                $sanitized[(int) $questionId] = $cleanAnswer;
            }
        }

        return $sanitized;
    }

    /**
     * パート制限時間取得
     */
    private function getPartTimeLimit($part)
    {
        $timeLimits = [
            1 => 1800,  // 30分(1800秒)
            2 => 1800,  // 30分
            3 => 1800,  // 30分
        ];

        return $timeLimits[$part] ?? 1800;
    }

    /**
     * 時間切れによる自動パート完了処理
     */
    private function autoCompletePartDueToTimeout($examSession)
    {
        if ($examSession->current_part < 3) {
            $examSession->update([
                'current_part' => $examSession->current_part + 1,
                'current_question' => 1,
                'remaining_time' => 0,
            ]);

            return redirect()->route('exam.part', ['part' => $examSession->current_part])
                ->with('message', '制限時間が終了しました。次のパートに進みます。');
        } else {
            $examSession->update([
                'finished_at' => now(),
                'remaining_time' => 0,
            ]);

            return redirect()->route('showResult')
                ->with('message', '制限時間が終了したため、試験を完了しました。');
        }
    }

    /**
     * ゲスト用時間切れによる自動パート完了処理
     */
    private function guestAutoCompletePartDueToTimeout($examSession, $guestId)
    {
        $existingSessionKey = "guest_exam_session_{$guestId}";

        if ($examSession['current_part'] < 3) {
            $examSession['current_part'] = $examSession['current_part'] + 1;
            $examSession['current_question'] = 1;
            $examSession['remaining_time'] = 0;

            Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);

            return redirect()->route('guest.exam.part', ['part' => $examSession['current_part']])
                ->with('message', '制限時間が終了しました。次のパートに進みます。');
        } else {
            $examSession['finished_at'] = now();
            $examSession['remaining_time'] = 0;

            Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);

            return redirect()->route('guestShowResult')
                ->with('message', '制限時間が終了したため、試験を完了しました。');
        }
    }

   /**
 * イベント情報からパート時間制限を取得
 */
private function getPartTimeLimitByEvent($part, $examType = 'full', $event = null)
{
    Log::info('=== getPartTimeLimitByEvent 呼び出し ===', [
        'part' => $part,
        'exam_type' => $examType,
        'has_event' => $event ? 'yes' : 'no',
        'event_id' => $event ? $event->id : null,
    ]);

    // イベントデータが存在する場合は必ずイベントから取得
    if ($event) {
        $timeKey = "part{$part}_time";
        $eventTime = $event->$timeKey ?? null;
        
        Log::info('イベントから時間設定取得', [
            'part' => $part,
            'time_key' => $timeKey,
            'event_time' => $eventTime,
            'all_times' => [
                'part1_time' => $event->part1_time ?? 'null',
                'part2_time' => $event->part2_time ?? 'null',
                'part3_time' => $event->part3_time ?? 'null',
            ],
        ]);
        
        if ($eventTime !== null) {
            Log::info('イベント設定の時間を返します', [
                'part' => $part,
                'seconds' => $eventTime,
                'minutes' => $eventTime > 0 ? round($eventTime / 60, 2) : '無制限',
            ]);
            return $eventTime;
        }
    }

    // フォールバック: イベントデータがない場合のデフォルト値
    $fallbackTimes = match($examType) {
        'full' => [1 => 600, 2 => 900, 3 => 1800],
        '45min' => [1 => 450, 2 => 600, 3 => 1080],
        '30min' => [1 => 300, 2 => 390, 3 => 720],
        default => [1 => 600, 2 => 900, 3 => 1800],
    };

    $result = $fallbackTimes[$part] ?? 1800;

    Log::info('フォールバック時間を返します', [
        'part' => $part,
        'exam_type' => $examType,
        'seconds' => $result,
        'minutes' => round($result / 60, 2),
    ]);

    return $result;
}

/**
 * イベント情報から問題数を取得
 */
private function getQuestionCountByEvent($part, $examType = 'full', $event = null)
{
    Log::info('=== getQuestionCountByEvent 呼び出し ===', [
        'part' => $part,
        'exam_type' => $examType,
        'has_event' => $event ? 'yes' : 'no',
    ]);

    if ($event) {
        $questionKey = "part{$part}_questions";
        $eventQuestions = $event->$questionKey ?? null;
        
        Log::info('イベントから問題数取得', [
            'part' => $part,
            'question_key' => $questionKey,
            'event_questions' => $eventQuestions,
        ]);
        
        if ($eventQuestions !== null && $eventQuestions > 0) {
            Log::info('イベント設定の問題数を返します', [
                'part' => $part,
                'questions' => $eventQuestions,
            ]);
            return $eventQuestions;
        }
    }

    // フォールバック: イベントデータがない場合のデフォルト値
    $fallbackQuestions = match($examType) {
        'full' => [1 => 40, 2 => 30, 3 => 25],
        '45min' => [1 => 30, 2 => 20, 3 => 15],
        '30min' => [1 => 20, 2 => 13, 3 => 10],
        default => [1 => 40, 2 => 30, 3 => 25],
    };

    $result = $fallbackQuestions[$part] ?? 25;

    Log::info('フォールバック問題数を返します', [
        'part' => $part,
        'exam_type' => $examType,
        'questions' => $result,
    ]);

    return $result;
}


    /**
     * セッションコードからイベント情報を取得
     */
    private function getEventBySessionCode($sessionCode)
    {
        if (!$sessionCode) {
            return null;
        }

        $event = \App\Models\Event::where('passphrase', $sessionCode)
            ->where('begin', '<=', now())
            ->where('end', '>=', now())
            ->first();

        Log::info('セッションコードからイベント検索', [
            'session_code' => $sessionCode,
            'event_found' => $event ? 'yes' : 'no',
            'event_id' => $event ? $event->id : null,
        ]);

        return $event;
    }

    /**
     * 本番テストの説明画面表示
     */
    public function explanation(Request $request, $part = 1)
    {
        // パート番号のバリデーション
        if (! in_array($part, [1, 2, 3])) {
            $part = 1;
        }

        return Inertia::render('Explanation', [
            'nextPart' => (int) $part,
            'isExam' => true,
            'isGuest' => false,
        ]);
    }
}
