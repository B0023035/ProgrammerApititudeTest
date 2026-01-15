<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\ExamSession;
use App\Models\ExamViolation;
use App\Models\Question;
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
        'violationCount' => 0,
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
     * 進捗保存処理(セキュリティ対応版)
     */
    public function saveProgress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'examSessionId' => 'required|string',
            'answers' => 'required|array',
            'currentQuestion' => 'required|integer|min:1',
            'remainingTime' => 'required|integer|min:0',
            'violationCount' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'データが無効です。']);
        }

        $user = Auth::user();
        $sessionId = $request->input('examSessionId');

        // セッション検証(修正版)
        $examSession = ExamSession::where('user_id', $user->id)
            ->where('id', $sessionId)
            ->whereNull('finished_at')
            ->whereNull('disqualified_at')
            ->first();

        if (! $examSession) {
            return response()->json(['success' => false, 'message' => 'セッションが無効です。']);
        }

        if ($examSession->disqualified_at) {
            return response()->json(['success' => false, 'message' => 'セッションが無効または失格です。']);
        }

        $examSession->update([
            'current_question' => $request->input('currentQuestion'),
            'remaining_time' => max(0, $request->input('remainingTime')),
        ]);

        // 現在の回答を一時保存(JSON形式)
        $answers = $this->sanitizeAnswers($request->input('answers', []));
        Cache::put("exam_answers_{$user->id}_{$examSession->current_part}", $answers, 3600);

        return response()->json(['success' => true]);
    }

    /**
     * 単一解答の即時保存(本番試験用)
     */
    public function saveAnswer(Request $request)
    {
        $validated = $request->validate([
            'examSessionId' => 'required|uuid',
            'questionId' => 'required|integer|exists:questions,id',
            'choice' => 'required|string|in:A,B,C,D,E',
            'part' => 'required|integer|in:1,2,3',
            'remainingTime' => 'integer|min:0',
        ]);

        try {
            $user = Auth::user();

            // ★ ゲストは処理しない
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ゲストモードでは保存できません',
                ], 403);
            }

            $cacheSessionId = $validated['examSessionId'];

            // キャッシュからセッション情報を取得
            $cacheKey = "exam_part_session_{$user->id}_{$cacheSessionId}";
            $cacheSession = Cache::get($cacheKey);

            if (! $cacheSession) {
                return response()->json([
                    'success' => false,
                    'message' => '無効なセッションです。',
                ], 403);
            }

            // データベースのExamSessionを取得
            $examSession = ExamSession::where('user_id', $user->id)
                ->where('id', $cacheSession['exam_session_id'])
                ->whereNull('finished_at')
                ->whereNull('disqualified_at')
                ->first();

            if (! $examSession) {
                return response()->json([
                    'success' => false,
                    'message' => '無効な試験セッションです。',
                ], 403);
            }

            // 現在の解答状況を security_log に保存
            $securityLog = json_decode($examSession->security_log ?? '{}', true);

            if (! isset($securityLog['part_'.$validated['part'].'_answers'])) {
                $securityLog['part_'.$validated['part'].'_answers'] = [];
            }

            $securityLog['part_'.$validated['part'].'_answers'][$validated['questionId']] = $validated['choice'];
            $securityLog['last_updated'] = now()->toISOString();

            $examSession->update([
                'security_log' => json_encode($securityLog),
                'remaining_time' => $validated['remainingTime'] ?? $examSession->remaining_time,
            ]);

            Log::info('解答一時保存成功', [
                'user_id' => $user->id,
                'exam_session_id' => $examSession->id,
                'question_id' => $validated['questionId'],
                'choice' => $validated['choice'],
                'part' => $validated['part'],
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('解答保存失敗', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。',
            ], 500);
        }
    }

    /**
 * 複数回答を一括保存(バッチ処理) - デッドロック対策版
 */
public function saveAnswersBatch(Request $request)
{
    $validated = $request->validate([
        'examSessionId' => 'required|uuid',
        'answers' => 'required|array|max:10', // 最大10問まで
        'answers.*' => 'string|in:A,B,C,D,E',
        'part' => 'required|integer|in:1,2,3',
        'remainingTime' => 'nullable|integer|min:0',
    ]);

    $maxRetries = 3;
    $attempt = 0;

    while ($attempt < $maxRetries) {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            // ゲストは処理しない
            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'ゲストモードでは保存できません',
                ], 403);
            }

            $cacheSessionId = $validated['examSessionId'];

            // キャッシュからセッション情報を取得
            $cacheKey = "exam_part_session_{$user->id}_{$cacheSessionId}";
            $cacheSession = Cache::get($cacheKey);

            if (!$cacheSession) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => '無効なセッションです。',
                ], 403);
            }

            // ★改善1: プライマリキーで直接取得し、排他ロック
            $examSession = ExamSession::lockForUpdate()
                ->find($cacheSession['exam_session_id']);

            // ★改善2: 取得後に条件チェック
            if (!$examSession 
                || $examSession->user_id !== $user->id
                || $examSession->finished_at
                || $examSession->disqualified_at) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => '無効な試験セッションです。',
                ], 403);
            }

            // 現在の解答状況を security_log に保存
            $securityLog = json_decode($examSession->security_log ?? '{}', true);

            if (!isset($securityLog['part_'.$validated['part'].'_answers'])) {
                $securityLog['part_'.$validated['part'].'_answers'] = [];
            }

            // 複数回答を一括更新
            foreach ($validated['answers'] as $questionId => $choice) {
                if (!is_numeric($questionId)) continue;
                $securityLog['part_'.$validated['part'].'_answers'][$questionId] = $choice;
            }

            $securityLog['last_updated'] = now()->toISOString();
            
            // 時間も更新
            $updateData = [
                'security_log' => json_encode($securityLog),
            ];
            
            if (isset($validated['remainingTime'])) {
                $updateData['remaining_time'] = $validated['remainingTime'];
            }

            $examSession->update($updateData);

            DB::commit();

            Log::info('バッチ保存成功', [
                'user_id' => $user->id,
                'exam_session_id' => $examSession->id,
                'answers_count' => count($validated['answers']),
                'part' => $validated['part'],
                'remaining_time' => $validated['remainingTime'] ?? 'not updated',
                'attempt' => $attempt + 1,
            ]);

            return response()->json(['success' => true]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            // ★改善3: デッドロック検出とリトライ
            $isDeadlock = false;
            
            // MySQL のデッドロック (エラーコード 1213)
            if ($e->getCode() == '40001' || str_contains($e->getMessage(), 'Deadlock')) {
                $isDeadlock = true;
            }
            
            // PostgreSQL のデッドロック (SQLSTATE 40P01)
            if ($e->getCode() == '40P01') {
                $isDeadlock = true;
            }
            
            if ($isDeadlock) {
                $attempt++;
                
                if ($attempt >= $maxRetries) {
                    Log::error('デッドロック: 最大リトライ回数超過', [
                        'user_id' => Auth::id(),
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'サーバーが混雑しています。もう一度お試しください。',
                    ], 503);
                }
                
                // ★改善4: 指数バックオフで待機
                $waitTime = rand(100, 500) * 1000 * $attempt; // マイクロ秒
                Log::warning('デッドロック検出 - リトライ', [
                    'user_id' => Auth::id(),
                    'attempt' => $attempt,
                    'wait_ms' => $waitTime / 1000,
                ]);
                
                usleep($waitTime);
                continue; // リトライ
            }
            
            // デッドロック以外のエラー
            Log::error('バッチ保存失敗', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。',
            ], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('バッチ保存失敗 (予期しないエラー)', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。',
            ], 500);
        }
    }

    // リトライループを抜けた場合（通常はここに到達しない）
    return response()->json([
        'success' => false,
        'message' => 'リクエストの処理に失敗しました。',
    ], 500);
}

    /**
     * ゲスト用本番試験パート表示 - カスタム対応修正版
     */
    public function guestPart(Request $request, $part)
    {
        $guestId = session()->getId();
        $part = (int) $part;

        Log::info('=== guest.exam.part 呼び出し ===', [
            'guest_id' => $guestId,
            'requested_part' => $part,
        ]);

        // パート番号の検証
        if (! in_array($part, [1, 2, 3])) {
            return redirect()->route('guest.test.start')
                ->with('error', '無効なパート番号です。');
        }

        // ゲストセッション取得
        $existingSessionKey = "guest_exam_session_{$guestId}";
        $session = Cache::get($existingSessionKey);

        // セッションコードとイベント取得
        $sessionCode = session('exam_session_code') ?? session('verified_session_code');
        $event = $sessionCode ? $this->getEventBySessionCode($sessionCode) : null;
        $examType = $event ? $event->exam_type : 'full';

        // セッションがない場合は新規作成
        if (! $session) {
            Log::info('ゲストセッションが存在しないため新規作成', [
                'guest_id' => $guestId,
                'requested_part' => $part,
                'exam_type' => $examType,
            ]);

            // ゲスト情報を取得
            $guestName = session('guest_name') ?? session('guest_info.name') ?? 'ゲスト';
            $guestSchool = session('guest_school_name') ?? session('guest_info.school_name') ?? '学校名未入力';

            // 新規ゲストセッション作成
            $session = [
                'guest_id' => $guestId,
                'guest_name' => $guestName,
                'guest_school' => $guestSchool,
                'started_at' => now(),
                'current_part' => $part,
                'current_question' => 1,
                'remaining_time' => 0,
                'violation_count' => 0,
                'security_log' => [],
            ];

            // 2時間有効なキャッシュに保存
            Cache::put($existingSessionKey, $session, 2 * 60 * 60);

            Log::info('新規ゲストセッション作成完了', [
                'guest_id' => $guestId,
                'guest_name' => $guestName,
                'starting_part' => $part,
                'exam_type' => $examType,
            ]);
        }

        // 違反チェック
        if (($session['violation_count'] ?? 0) >= 3) {
            return redirect()->route('guest.exam.disqualified');
        }

        // 残り時間の処理
        if ($session['remaining_time'] > 0) {
            $remainingTime = $session['remaining_time'];
        } else {
            // ★ 修正: イベント情報を渡す
            $partTimeLimit = $this->getPartTimeLimitByEvent($part, $examType, $event);
            $remainingTime = $partTimeLimit;

            $session['remaining_time'] = $remainingTime;
            Cache::put($existingSessionKey, $session, 2 * 60 * 60);
        }

        // 時間切れチェック(0=無制限の場合はスキップ)
        $partTimeLimit = $this->getPartTimeLimitByEvent($part, $examType, $event);
        if ($partTimeLimit > 0 && $remainingTime <= 0) {
            return $this->guestAutoCompletePartDueToTimeout($session, $guestId);
        }

        // セキュリティ用のセッションIDを生成
        $sessionId = (string) Str::uuid();

        // セッション情報をキャッシュに保存(30分で期限切れ)
        Cache::put("guest_exam_part_session_{$guestId}_{$sessionId}", [
            'guest_id' => $guestId,
            'part' => $part,
            'started_at' => now(),
        ], 30 * 60);

        // キャッシュから保存済みの解答を取得
        $answersKey = "guest_exam_answers_{$guestId}_part_{$part}";
        $savedAnswers = Cache::get($answersKey, []);

        Log::info('ゲスト保存済み解答の読み込み', [
            'guest_id' => $guestId,
            'part' => $part,
            'saved_answers_count' => count($savedAnswers),
            'saved_answers' => $savedAnswers,
            'exam_type' => $examType,
        ]);

        // ★ 修正: イベント情報を渡す
        $questionCount = $this->getQuestionCountByEvent($part, $examType, $event);

        // 該当パートの問題を取得
        $questions = Question::with(['choices' => function ($query) use ($part) {
            $query->where('part', $part)->orderBy('label');
        }])
            ->where('part', $part)
            ->orderBy('number')
            ->take($questionCount)
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
                    'selected' => isset($savedAnswers[$q->id]) ? $savedAnswers[$q->id] : null,
                ];
            });

        Log::info('ゲスト問題データの生成完了', [
            'guest_id' => $guestId,
            'part' => $part,
            'questions_count' => $questions->count(),
            'exam_type' => $examType,
            'expected_count' => $questionCount,
        ]);

        // ★ 修正: イベント情報を渡す
        return Inertia::render('Part', [
            'examSessionId' => $sessionId,
            'practiceSessionId' => $sessionId,
            'practiceQuestions' => $questions,
            'part' => $part,
            'questions' => $questions,
            'currentPart' => $part,
            'partTime' => $this->getPartTimeLimitByEvent($part, $examType, $event),
            'remainingTime' => $remainingTime,
            'currentQuestion' => $session['current_question'] ?? 1,
            'totalParts' => 3,
            'violationCount' => $session['violation_count'] ?? 0,
            'examType' => $examType,
            'isGuest' => true,
        ]);
    }

    /**
     * ゲスト用本番試験開始処理(キャッシュのみ、DBには保存しない) - 修正版
     */
    public function guestStart(Request $request)
    {
        $guestId = session()->getId();

        Log::info('=== guestStart呼び出し ===', [
            'guest_id' => $guestId,
            'session_data' => session()->all(),
        ]);

        // ゲスト情報の確認
        $guestName = session('guest_name') ?? session('guest_info.name') ?? 'ゲスト';
        $guestSchool = session('guest_school_name') ?? session('guest_info.school_name') ?? '学校名未入力';

        // 既存のゲスト試験セッションがあるか確認
        $existingSessionKey = "guest_exam_session_{$guestId}";
        $existingSession = Cache::get($existingSessionKey);

        if ($existingSession && !isset($existingSession['finished_at'])) {
            // ★ 重要修正: 既存セッションがあれば現在のパートに復帰
            Log::info('既存のゲスト試験セッションを使用', [
                'guest_id' => $guestId,
                'current_part' => $existingSession['current_part'] ?? 1,
            ]);

            return redirect()->route('guest.exam.part', ['part' => $existingSession['current_part'] ?? 1]);
        }

        // ★ 重要修正: 新しい試験セッションを作成(常にパート1から開始)
        $newSession = [
            'guest_id' => $guestId,
            'guest_name' => $guestName,
            'guest_school' => $guestSchool,
            'started_at' => now(),
            'current_part' => 1,
            'current_question' => 1,
            'remaining_time' => 0,
            'violation_count' => 0,
            'security_log' => [],
        ];

        // 2時間有効なキャッシュに保存
        Cache::put($existingSessionKey, $newSession, 2 * 60 * 60);

        // セッションにもゲスト情報を保存
        session([
            'guest_name' => $guestName,
            'guest_school_name' => $guestSchool,
        ]);

        Log::info('新しいゲスト試験セッション作成(キャッシュのみ)', [
            'guest_id' => $guestId,
            'guest_name' => $guestName,
            'guest_school' => $guestSchool,
            'starting_part' => 1,
        ]);

        // ★ 修正: 常にパート1から開始
        return redirect()->route('guest.exam.part', ['part' => 1]);
    }

    /**
     * 違反報告処理
     */
    public function reportViolation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'examSessionId' => 'required|string',
            'violationType' => 'required|string',
            'timestamp' => 'required|string',
            'violationCount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }

        $user = Auth::user();
        $sessionId = $request->input('examSessionId');

        // セッション検証(修正版)
        $examSession = ExamSession::where('user_id', $user->id)
            ->where('id', $sessionId)
            ->first();

        if (! $examSession || $examSession->user_id !== $user->id) {
            return response()->json(['success' => false], 403);
        }

        // 違反を記録
        ExamViolation::create([
            'exam_session_id' => $examSession->id,
            'user_id' => $user->id,
            'violation_type' => $request->violationType,
            'violation_details' => json_encode([
                'timestamp' => $request->timestamp,
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'violation_count' => $request->violationCount,
            ]),
        ]);

        // ログに記録
        Log::warning('Exam violation detected', [
            'user_id' => $user->id,
            'exam_session_id' => $examSession->id,
            'violation_type' => $request->violationType,
            'violation_count' => $request->violationCount,
        ]);

        // 違反回数が3回に達した場合は失格処理
        $totalViolations = ExamViolation::where('exam_session_id', $examSession->id)->count();
        if ($totalViolations >= 3) {
            $this->disqualifySession($examSession, 'Multiple security violations');

            return response()->json(['success' => true, 'violation_count' => $totalViolations, 'disqualified' => true]);
        }

        return response()->json(['success' => true, 'violation_count' => $totalViolations]);
    }

    /**
     * 失格画面
     */
    public function disqualified()
    {
        $user = Auth::user();

        $examSession = ExamSession::where('user_id', $user->id)
            ->whereNotNull('disqualified_at')
            ->latest('disqualified_at')
            ->first();

        if (! $examSession) {
            return redirect()->route('test.start');
        }

        $violations = ExamViolation::where('exam_session_id', $examSession->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Exam/Disqualified', [
            'examSession' => $examSession,
            'violations' => $violations,
            'disqualificationReason' => $examSession->disqualification_reason,
        ]);
    }

    /**
     * 試験結果を表示
     */
    public function showResult($sessionUuid)
    {
        $user = Auth::user();

        // セッションを取得
        $session = ExamSession::where('session_uuid', $sessionUuid)
            ->where('user_id', $user->id)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->firstOrFail();

        // 試験タイプを取得
        $securityLog = json_decode($session->security_log ?? '{}', true);
        $examType = $securityLog['exam_type'] ?? 'full';
        $questionIds = $securityLog['question_ids'] ?? null;

        // イベント情報を取得（存在する場合）
        $event = null;
        if ($session->event_id) {
            $event = \App\Models\Event::find($session->event_id);
        }

        // 各部の結果を集計
        $results = [];
        $maxScores = []; // 各パートの満点

        for ($part = 1; $part <= 3; $part++) {
            // 該当部の解答を取得
            $answers = Answer::where('user_id', $user->id)
                ->where('exam_session_id', $session->id)
                ->where('part', $part)
                ->get();

            // 実際に出題された問題数を取得
            // 1. セッションに保存された問題IDがあればそれを使用
            // 2. なければイベント設定、最後にフォールバック
            if ($questionIds && isset($questionIds["part_{$part}"]) && count($questionIds["part_{$part}"]) > 0) {
                $totalQuestions = count($questionIds["part_{$part}"]);
            } else {
                $totalQuestions = $this->getQuestionCountByEvent($part, $examType, $event);
            }

            $correct = $answers->where('is_correct', 1)->count();
            $incorrect = $answers->where('is_correct', 0)->count();
            $unanswered = $totalQuestions - $correct - $incorrect;

            // スコア計算
            $score = ($correct * 1) + ($incorrect * -0.25);

            $results[$part] = [
                'correct' => $correct,
                'incorrect' => $incorrect,
                'unanswered' => $unanswered,
                'total' => $totalQuestions,
                'score' => round($score, 2),
            ];

            // 満点を記録
            $maxScores[$part] = $totalQuestions;
        }

        // 総合スコア
        $totalScore = $results[1]['score'] + $results[2]['score'] + $results[3]['score'];
        $maxTotalScore = $maxScores[1] + $maxScores[2] + $maxScores[3];

        // ランク判定（95問満点の基準を問題数に応じてスケーリング）
        // 基準値: Platinum=61, Gold=51, Silver=36 (95問満点時)
        $baseMax = 95;
        $scaleFactor = $maxTotalScore / $baseMax;
        
        // スケーリングされた基準値
        $platinumThreshold = 61 * $scaleFactor;
        $goldThreshold = 51 * $scaleFactor;
        $silverThreshold = 36 * $scaleFactor;

        if ($totalScore >= $platinumThreshold) {
            $rank = 'A';
            $rankName = 'Platinum';
        } elseif ($totalScore >= $goldThreshold) {
            $rank = 'B';
            $rankName = 'Gold';
        } elseif ($totalScore >= $silverThreshold) {
            $rank = 'C';
            $rankName = 'Silver';
        } else {
            $rank = 'D';
            $rankName = 'Bronze';
        }

        // セッションに保存
        session([
            'exam_results' => [
                'results' => $results,
                'rankName' => $rankName,
                'totalScore' => round($totalScore, 2),
                'rank' => $rank,
            ],
            'isGuest' => false,
        ]);

        return Inertia::render('Result', [
            'results' => $results,
            'totalScore' => round($totalScore, 2),
            'rank' => $rank,
            'rankName' => $rankName,
            'isGuest' => false,
            'examType' => $examType,
        ]);
    }

    /**
     * 過去の試験結果から賞状を表示（リザルト画面用）
     */
    public function showCertificate($sessionUuid)
    {
        $user = Auth::user();

        // セッションを取得
        $session = ExamSession::where('session_uuid', $sessionUuid)
            ->where('user_id', $user->id)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->firstOrFail();

        // 試験タイプを取得
        $securityLog = json_decode($session->security_log ?? '{}', true);
        $examType = $securityLog['exam_type'] ?? 'full';
        $questionIds = $securityLog['question_ids'] ?? null;

        // イベント情報を取得（存在する場合）
        $event = null;
        if ($session->event_id) {
            $event = \App\Models\Event::find($session->event_id);
        }

        // 各部の結果を集計
        $results = [];
        $maxScores = [];

        for ($part = 1; $part <= 3; $part++) {
            $answers = Answer::where('user_id', $user->id)
                ->where('exam_session_id', $session->id)
                ->where('part', $part)
                ->get();

            if ($questionIds && isset($questionIds["part_{$part}"]) && count($questionIds["part_{$part}"]) > 0) {
                $totalQuestions = count($questionIds["part_{$part}"]);
            } else {
                $totalQuestions = $this->getQuestionCountByEvent($part, $examType, $event);
            }

            $correct = $answers->where('is_correct', 1)->count();
            $incorrect = $answers->where('is_correct', 0)->count();
            $unanswered = $totalQuestions - $correct - $incorrect;

            $score = ($correct * 1) + ($incorrect * -0.25);

            $results[$part] = [
                'correct' => $correct,
                'incorrect' => $incorrect,
                'unanswered' => $unanswered,
                'total' => $totalQuestions,
                'score' => round($score, 2),
            ];

            $maxScores[$part] = $totalQuestions;
        }

        $totalScore = $results[1]['score'] + $results[2]['score'] + $results[3]['score'];
        $maxTotalScore = $maxScores[1] + $maxScores[2] + $maxScores[3];

        // ランク判定
        $baseMax = 95;
        $scaleFactor = $maxTotalScore / $baseMax;
        
        $platinumThreshold = 61 * $scaleFactor;
        $goldThreshold = 51 * $scaleFactor;
        $silverThreshold = 36 * $scaleFactor;

        if ($totalScore >= $platinumThreshold) {
            $rank = 'A';
            $rankName = 'Platinum';
        } elseif ($totalScore >= $goldThreshold) {
            $rank = 'B';
            $rankName = 'Gold';
        } elseif ($totalScore >= $silverThreshold) {
            $rank = 'C';
            $rankName = 'Silver';
        } else {
            $rank = 'D';
            $rankName = 'Bronze';
        }

        return Inertia::render('Certificate', [
            'results' => $results,
            'totalScore' => round($totalScore, 2),
            'rank' => $rank,
            'rankName' => $rankName,
            'userName' => $user->name,
            'schoolName' => 'YIC情報ビジネス専門学校',
            'finishedAt' => $session->finished_at,
        ]);
    }

    /**
 * ゲストパート完了処理 - 修正版(全問未回答・時間切れでも進める)
 */
public function guestCompletePart(Request $request)
{
    // ★ 修正: answers を nullable に変更、timeSpent も柔軟に
    $validated = $request->validate([
            '_token' => 'required|string',  // ★ CSRF トークン検証
    ]);

    $guestId = session()->getId();
    $sessionId = $validated['examSessionId'];
    $part = $validated['part'];

    // セッション情報の検証
    $cacheKey = "guest_exam_part_session_{$guestId}_{$sessionId}";
    $sessionData = Cache::get($cacheKey);
    if (! $sessionData) {
        return redirect()->route('guest.test.start')
            ->with('error', 'セッションが無効です。試験を最初からやり直してください。');
    }

    // ゲストセッション取得
    $existingSessionKey = "guest_exam_session_{$guestId}";
    $examSession = Cache::get($existingSessionKey);
    if (! $examSession) {
        return redirect()->route('guest.test.start')
            ->with('error', 'セッションが見つかりません。');
    }

    // ★ 修正: 解答が空でもキャッシュに保存
    $answers = $request->input('answers', []);
    $sanitizedAnswers = $this->sanitizeAnswers($answers);
    $answersKey = "guest_exam_answers_{$guestId}_part_{$part}";
    Cache::put($answersKey, $sanitizedAnswers, 2 * 60 * 60);

    Log::info("ゲスト第{$part}部の解答を保存", [
        'guest_id' => $guestId,
        'part' => $part,
        'answers_count' => count($sanitizedAnswers),
        'is_empty' => count($sanitizedAnswers) === 0 ? 'yes' : 'no',  // ★ 空かどうかをログ出力
    ]);

    // 次のアクションを決定
    if ($part < 3) {
        // 第一部・第二部完了後は次の部の練習問題へ
        $examSession['current_part'] = $part + 1;
        $examSession['current_question'] = 1;
        $examSession['remaining_time'] = 0;
        Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);

        Cache::forget($cacheKey);

        Log::info("ゲスト第{$part}部完了 - 第".($part + 1)."部練習問題へ", [
            'guest_id' => $guestId,
            'completed_part' => $part,
            'next_part' => $part + 1,
        ]);

        return redirect()->route('guest.practice.show', ['section' => $part + 1])
            ->with('success', "第{$part}部が完了しました。第".($part + 1).'部の練習問題を開始してください。');
    } else {
        // 第三部完了時に全パートの採点を実行
        Log::info('ゲスト試験完了 - 全パート採点開始', [
            'guest_id' => $guestId,
        ]);

        // イベント情報を取得（存在する場合）
        $event = null;
        $examType = $examSession['exam_type'] ?? 'full';
        $questionIds = $examSession['question_ids'] ?? null;
        if (isset($examSession['event_id'])) {
            $event = \App\Models\Event::find($examSession['event_id']);
        }

        // 各パートの結果を集計
        $results = [];
        $maxScores = [];

        for ($p = 1; $p <= 3; $p++) {
            $partAnswersKey = "guest_exam_answers_{$guestId}_part_{$p}";
            $partAnswers = Cache::get($partAnswersKey, []);

            // 実際に出題された問題数を取得
            if ($questionIds && isset($questionIds["part_{$p}"]) && count($questionIds["part_{$p}"]) > 0) {
                $totalQuestions = count($questionIds["part_{$p}"]);
            } elseif ($event) {
                $totalQuestions = $this->getQuestionCountByEvent($p, $examType, $event);
            } else {
                // フォールバック: デフォルト問題数
                $totalQuestions = match($p) {
                    1 => 40,
                    2 => 30,
                    3 => 25,
                };
            }

            $correct = 0;
            $incorrect = 0;

            // 正解判定
            foreach ($partAnswers as $questionId => $choice) {
                $question = Question::with('choices')->find($questionId);
                if ($question && $question->part == $p) {
                    $correctChoice = $question->choices()
                        ->where('part', $p)
                        ->where('is_correct', true)
                        ->first();

                    if ($correctChoice && trim($correctChoice->label) === trim($choice)) {
                        $correct++;
                    } else {
                        $incorrect++;
                    }
                }
            }

            $unanswered = $totalQuestions - ($correct + $incorrect);
            $score = ($correct * 1) + ($incorrect * -0.25);

            $results[$p] = [
                'correct' => $correct,
                'incorrect' => $incorrect,
                'unanswered' => $unanswered,
                'total' => $totalQuestions,
                'score' => round($score, 2),
            ];

            $maxScores[$p] = $totalQuestions;
        }

        // 総合スコア
        $totalScore = $results[1]['score'] + $results[2]['score'] + $results[3]['score'];
        $maxTotalScore = $maxScores[1] + $maxScores[2] + $maxScores[3];

        // ランク判定（95問満点の基準を問題数に応じてスケーリング）
        // 基準値: Platinum=61, Gold=51, Silver=36 (95問満点時)
        $baseMax = 95;
        $scaleFactor = $maxTotalScore / $baseMax;
        
        // スケーリングされた基準値
        $platinumThreshold = 61 * $scaleFactor;
        $goldThreshold = 51 * $scaleFactor;
        $silverThreshold = 36 * $scaleFactor;

        if ($totalScore >= $platinumThreshold) {
            $rank = 'A';
            $rankName = 'Platinum';
        } elseif ($totalScore >= $goldThreshold) {
            $rank = 'B';
            $rankName = 'Gold';
        } elseif ($totalScore >= $silverThreshold) {
            $rank = 'C';
            $rankName = 'Silver';
        } else {
            $rank = 'D';
            $rankName = 'Bronze';
        }

        // ゲスト情報を取得
        $guestName = $examSession['guest_name'] ?? session('guest_name') ?? 'ゲスト';
        $guestSchool = $examSession['guest_school'] ?? session('guest_school_name') ?? '学校名未入力';

        // セッションに結果を保存
        session([
            'exam_results' => [
                'results' => $results,
                'rankName' => $rankName,
                'totalScore' => round($totalScore, 2),
                'rank' => $rank,
            ],
            'isGuest' => true,
            'guestName' => $guestName,
            'guestSchool' => $guestSchool,
        ]);

        // セッション更新
        $examSession['finished_at'] = now();
        Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);
        Cache::forget($cacheKey);

        Log::info('ゲスト試験完了', [
            'guest_id' => $guestId,
            'total_score' => round($totalScore, 2),
            'rank' => $rankName,
            'part1_answers' => count(Cache::get("guest_exam_answers_{$guestId}_part_1", [])),
            'part2_answers' => count(Cache::get("guest_exam_answers_{$guestId}_part_2", [])),
            'part3_answers' => count(Cache::get("guest_exam_answers_{$guestId}_part_3", [])),
        ]);

        return redirect()->route('guest.result')
            ->with('success', '試験が完了しました。');
    }
}

    /**
     * ゲスト試験結果を表示
     */
    public function guestShowResult()
    {
        $guestId = session()->getId();

        Log::info('ゲスト結果表示リクエスト', [
            'guest_id' => $guestId,
            'session_data' => session()->all(),
        ]);

        // ★ 重要: セッションから結果を取得(採点済み)
        $examResults = session('exam_results');

        if (! $examResults) {
            Log::error('セッションに結果データがありません', [
                'guest_id' => $guestId,
                'session_keys' => array_keys(session()->all()),
            ]);

            return redirect()->route('guest.test.start')
                ->with('error', '試験結果が見つかりません。');
        }

        // ★ セッションから結果を直接使用(再計算しない)
        $results = $examResults['results'];
        $totalScore = $examResults['totalScore'];
        $rank = $examResults['rank'];
        $rankName = $examResults['rankName'];

        // ゲスト情報を取得
        $guestName = session('guest_name') ?? session('guestName') ?? 'ゲスト';
        $guestSchool = session('guest_school_name') ?? session('guestSchool') ?? '学校名未入力';

        Log::info('ゲスト結果表示', [
            'guest_id' => $guestId,
            'guest_name' => $guestName,
            'guest_school' => $guestSchool,
            'total_score' => $totalScore,
            'rank' => $rankName,
            'results_summary' => [
                'part1_correct' => $results[1]['correct'],
                'part2_correct' => $results[2]['correct'],
                'part3_correct' => $results[3]['correct'],
            ],
        ]);

        // ★ 修正: auth を null として明示的に渡す
        return Inertia::render('Result', [
            'auth' => [
                'user' => null,  // ★ ゲストなので null
            ],
            'results' => $results,
            'totalScore' => $totalScore,
            'rank' => $rank,
            'rankName' => $rankName,
            'isGuest' => true,
            'guestName' => $guestName,
            'guestSchool' => $guestSchool,
        ]);
    }

    /**
     * ゲスト用データクリーンアップ(Result.vueから呼ばれる)
     */
    public function guestCleanup(Request $request)
    {
        $guestId = session()->getId();

        // キャッシュから全解答データを削除
        for ($part = 1; $part <= 3; $part++) {
            Cache::forget("guest_exam_answers_{$guestId}_part_{$part}");
            Cache::forget("guest_exam_result_{$guestId}_part_{$part}");
        }

        // ゲストセッション削除
        Cache::forget("guest_exam_session_{$guestId}");

        Log::info('ゲストデータクリーンアップ完了', [
            'guest_id' => $guestId,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * ゲスト失格画面
     */
    public function guestDisqualified()
    {
        $guestId = session()->getId();

        $existingSessionKey = "guest_exam_session_{$guestId}";
        $session = Cache::get($existingSessionKey);

        if (! $session || ($session['violation_count'] ?? 0) < 3) {
            return redirect()->route('guest.test.start');
        }

        return Inertia::render('Exam/Disqualified', [
            'examSession' => $session,
            'violations' => $session['security_log'] ?? [],
            'disqualificationReason' => 'Multiple security violations',
            'isGuest' => true,
        ]);
    }

    /**
     * ゲスト違反報告処理
     */
    public function guestReportViolation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'examSessionId' => 'required|string|size:36',
            'violationType' => 'required|string',
            'timestamp' => 'required|string',
            'violationCount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }

        $guestId = session()->getId();
        $sessionId = $request->input('examSessionId');

        // セッション検証
        $cacheKey = "guest_exam_part_session_{$guestId}_{$sessionId}";
        $sessionData = Cache::get($cacheKey);
        if (! $sessionData) {
            return response()->json(['success' => false], 403);
        }

        // ゲストセッション取得
        $existingSessionKey = "guest_exam_session_{$guestId}";
        $examSession = Cache::get($existingSessionKey);
        if (! $examSession) {
            return response()->json(['success' => false], 403);
        }

        // 違反を記録
        $violationData = [
            'timestamp' => $request->timestamp,
            'violation_type' => $request->violationType,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'violation_count' => $request->violationCount,
        ];

        $securityLog = $examSession['security_log'] ?? [];
        $securityLog[] = $violationData;

        $examSession['security_log'] = $securityLog;
        $examSession['violation_count'] = ($examSession['violation_count'] ?? 0) + 1;

        // ログに記録
        Log::warning('Guest exam violation detected', [
            'guest_id' => $guestId,
            'violation_type' => $request->violationType,
            'violation_count' => $examSession['violation_count'],
        ]);

        // 違反回数が3回に達した場合は失格処理
        if ($examSession['violation_count'] >= 3) {
            $examSession['disqualified_at'] = now();
            $examSession['disqualification_reason'] = 'Multiple security violations';
        }

        Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);

        return response()->json([
            'success' => true,
            'violation_count' => $examSession['violation_count'],
            'disqualified' => $examSession['violation_count'] >= 3,
        ]);
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
     * セッション失格処理
     */
    private function disqualifySession($examSession, $reason = 'Security violation', $securityLog = [])
    {
        $examSession->update([
            'disqualified_at' => now(),
            'disqualification_reason' => $reason,
            'security_log' => json_encode($securityLog),
            'finished_at' => now(),
        ]);

        // 管理者に通知
        Log::critical('Exam disqualification', [
            'user_id' => $examSession->user_id,
            'exam_session_id' => $examSession->id,
            'reason' => $reason,
            'violation_count' => ExamViolation::where('exam_session_id', $examSession->id)->count(),
        ]);
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

    /**
     * ゲスト本番試験の説明画面表示
     */
    public function guestExplanation(Request $request, $part = 1)
    {
        // パート番号のバリデーション
        if (! in_array($part, [1, 2, 3])) {
            $part = 1;
        }

        return Inertia::render('Explanation', [
            'nextPart' => (int) $part,
            'isExam' => true,
            'isGuest' => true,
        ]);
    }

    /**
     * ゲスト情報保存処理
     */
    public function storeGuestInfo(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:100',
            'guest_name' => 'required|string|max:100',
        ]);

        // セッションに保存(複数の形式で保存)
        session([
            'guest_school_name' => $validated['school_name'],
            'guest_name' => $validated['guest_name'],
            'guest_info' => [
                'name' => $validated['guest_name'],
                'school_name' => $validated['school_name'],
            ],
            'is_guest' => true,
        ]);

        Log::info('ゲスト情報登録', [
            'school_name' => $validated['school_name'],
            'guest_name' => $validated['guest_name'],
            'session_id' => session()->getId(),
        ]);

        // ExamInstructionsを直接レンダリング
        return Inertia::render('ExamInstructions', [
            'isGuest' => true,
        ]);
    }

    /**
     * ユーザーの過去の試験結果一覧を表示
     */
    public function myResults()
    {
        $user = Auth::user();

        // 完了済みセッションを取得（失格でないもの）
        $sessions = ExamSession::where('user_id', $user->id)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->with('event')
            ->orderBy('finished_at', 'desc')
            ->get();

        $results = [];
        $eventNames = [];

        foreach ($sessions as $session) {
            // 試験タイプを取得
            $securityLog = json_decode($session->security_log ?? '{}', true);
            $examType = $securityLog['exam_type'] ?? 'full';
            $questionIds = $securityLog['question_ids'] ?? null;

            // イベント情報
            $event = $session->event;
            $eventName = $event ? $event->name : '一般試験';
            
            // イベント名を収集
            if (!in_array($eventName, $eventNames)) {
                $eventNames[] = $eventName;
            }

            // 各部の結果を集計
            $partResults = [];
            $maxScores = [];

            for ($part = 1; $part <= 3; $part++) {
                $answers = Answer::where('user_id', $user->id)
                    ->where('exam_session_id', $session->id)
                    ->where('part', $part)
                    ->get();

                // 問題数を取得
                if ($questionIds && isset($questionIds["part_{$part}"]) && count($questionIds["part_{$part}"]) > 0) {
                    $totalQuestions = count($questionIds["part_{$part}"]);
                } else {
                    $totalQuestions = $this->getQuestionCountByEvent($part, $examType, $event);
                }

                $correct = $answers->where('is_correct', 1)->count();
                $incorrect = $answers->where('is_correct', 0)->count();
                $unanswered = $totalQuestions - $correct - $incorrect;
                $score = ($correct * 1) + ($incorrect * -0.25);

                $partResults[$part] = [
                    'correct' => $correct,
                    'incorrect' => $incorrect,
                    'unanswered' => $unanswered,
                    'total' => $totalQuestions,
                    'score' => round($score, 2),
                ];

                $maxScores[$part] = $totalQuestions;
            }

            // 総合スコア
            $totalScore = $partResults[1]['score'] + $partResults[2]['score'] + $partResults[3]['score'];
            $maxTotalScore = $maxScores[1] + $maxScores[2] + $maxScores[3];

            // ランク判定
            $baseMax = 95;
            $scaleFactor = $maxTotalScore / $baseMax;
            
            $platinumThreshold = 61 * $scaleFactor;
            $goldThreshold = 51 * $scaleFactor;
            $silverThreshold = 36 * $scaleFactor;

            if ($totalScore >= $platinumThreshold) {
                $rank = 'A';
                $rankName = 'Platinum';
            } elseif ($totalScore >= $goldThreshold) {
                $rank = 'B';
                $rankName = 'Gold';
            } elseif ($totalScore >= $silverThreshold) {
                $rank = 'C';
                $rankName = 'Silver';
            } else {
                $rank = 'D';
                $rankName = 'Bronze';
            }

            $results[] = [
                'id' => $session->id,
                'session_uuid' => $session->session_uuid,
                'event_name' => $eventName,
                'finished_at' => $session->finished_at->toIso8601String(),
                'total_score' => round($totalScore, 2),
                'max_score' => $maxTotalScore,
                'rank' => $rank,
                'rank_name' => $rankName,
                'part_results' => $partResults,
            ];
        }

        return Inertia::render('MyResults', [
            'results' => $results,
            'events' => $eventNames,
        ]);
    }

    /**
     * ユーザーの試験結果詳細を表示
     */
    public function myResultDetail($sessionId)
    {
        $user = Auth::user();

        // セッションを取得（自分のものかチェック）
        $session = ExamSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->with(['event'])
            ->firstOrFail();

        // 試験タイプを取得
        $securityLog = json_decode($session->security_log ?? '{}', true);
        $examType = $securityLog['exam_type'] ?? 'full';
        $questionIds = $securityLog['question_ids'] ?? null;

        // イベント情報
        $event = $session->event;

        // 各パートの回答詳細を取得
        $answersByPart = [];
        $totalQuestions = 0;

        for ($part = 1; $part <= 3; $part++) {
            $answers = Answer::where('user_id', $user->id)
                ->where('exam_session_id', $session->id)
                ->where('part', $part)
                ->get();

            // 問題数を取得
            if ($questionIds && isset($questionIds["part_{$part}"]) && count($questionIds["part_{$part}"]) > 0) {
                $partQuestionCount = count($questionIds["part_{$part}"]);
            } else {
                $partQuestionCount = $this->getQuestionCountByEvent($part, $examType, $event);
            }

            $correct = $answers->where('is_correct', 1)->count();
            $incorrect = $answers->where('is_correct', 0)->count();
            $percentage = $partQuestionCount > 0 ? round(($correct / $partQuestionCount) * 100, 1) : 0;

            $answersByPart[(string)$part] = [
                'score' => [
                    'correct' => $correct,
                    'total' => $partQuestionCount,
                    'percentage' => $percentage,
                ],
            ];

            $totalQuestions += $partQuestionCount;
        }

        // 総合スコア計算
        $allAnswers = Answer::where('exam_session_id', $session->id)->get();
        $score = 0;
        foreach ($allAnswers as $answer) {
            if ($answer->choice === null) {
                continue;
            } elseif ($answer->is_correct) {
                $score += 1;
            } else {
                $score -= 0.25;
            }
        }

        // ランク計算
        $baseMax = 95;
        $scaleFactor = $totalQuestions / $baseMax;
        
        $platinumThreshold = 61 * $scaleFactor;
        $goldThreshold = 51 * $scaleFactor;
        $silverThreshold = 36 * $scaleFactor;

        if ($score >= $platinumThreshold) {
            $rankName = 'Platinum';
        } elseif ($score >= $goldThreshold) {
            $rankName = 'Gold';
        } elseif ($score >= $silverThreshold) {
            $rankName = 'Silver';
        } else {
            $rankName = 'Bronze';
        }

        $percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100, 1) : 0;

        return Inertia::render('MyResultDetail', [
            'session' => [
                'id' => $session->id,
                'session_uuid' => $session->session_uuid,
                'started_at' => $session->started_at->toIso8601String(),
                'finished_at' => $session->finished_at->toIso8601String(),
                'total_score' => round($score, 2),
                'total_questions' => $totalQuestions,
                'percentage' => $percentage,
                'rank' => $rankName,
                'event' => $event ? [
                    'id' => $event->id,
                    'name' => $event->name,
                ] : null,
            ],
            'answersByPart' => $answersByPart,
        ]);
    }
}
