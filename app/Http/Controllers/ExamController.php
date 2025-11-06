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
     * 本番テスト開始処理(セキュリティ対応版) - 修正版
     */
    public function start()
    {
        $user = Auth::user();

        Log::info('=== exam.start 呼び出し ===', [
            'user_id' => $user->id,
        ]);

        // セッションコードを取得
        $sessionCode = session('exam_session_code');
        $event = $this->getEventBySessionCode($sessionCode);

        // イベントが見つからない場合はエラー
        if (! $event) {
            Log::error('セッションコードが無効', [
                'user_id' => $user->id,
                'session_code' => $sessionCode,
            ]);
            return back()->with('error', 'セッションコードが無効または期限切れです。');
        }

        $examType = $event->exam_type;

        // 未完了・未失格セッションがあるかチェック
        $existingSession = ExamSession::where('user_id', $user->id)
            ->whereNull('finished_at')
            ->whereNull('disqualified_at')
            ->first();

        if ($existingSession) {
            // 失格チェック
            $violationCount = ExamViolation::where('exam_session_id', $existingSession->id)->count();
            if ($violationCount >= 3 && ! $existingSession->disqualified_at) {
                $this->disqualifySession($existingSession, 'Multiple security violations');
                return Inertia::location(route('exam.disqualified'));
            }

            if ($existingSession->disqualified_at) {
                return Inertia::location(route('exam.disqualified'));
            }

            // ★ 重要修正: 既存セッションがある場合は現在のパートに復帰
            Log::info('既存セッションに復帰', [
                'user_id' => $user->id,
                'exam_session_id' => $existingSession->id,
                'current_part' => $existingSession->current_part,
            ]);

            return Inertia::location(route('exam.part', ['part' => $existingSession->current_part]));
        }

        // ★ 重要修正: 新規セッション作成時は常にパート1から開始
        $session = ExamSession::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'current_part' => 1,
            'current_question' => 1,
            'remaining_time' => 0,
            'security_log' => json_encode([
                'exam_type' => $examType,
                'event_id' => $event->id,
            ]),
        ]);

        Log::info('新しい試験セッション作成', [
            'user_id' => $user->id,
            'exam_session_id' => $session->id,
            'session_uuid' => $session->session_uuid,
            'exam_type' => $examType,
            'starting_part' => 1,
        ]);

        // ★ 修正: 常にパート1から開始
        return Inertia::location(route('exam.part', ['part' => 1]));
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
 * パート画面表示(セキュリティ対応版)- 修正版
 */
public function part(Request $request, $part)
{
    $user = Auth::user();
    $part = (int) $part;

    Log::info('=== exam.part 呼び出し ===', [
        'user_id' => $user->id,
        'requested_part' => $part,
        'session_code' => session('exam_session_code'),
        'verified_session_code' => session('verified_session_code'),
    ]);

    // パート番号の検証
    if (! in_array($part, [1, 2, 3])) {
        Log::warning('無効なパート番号でリダイレクト', [
            'user_id' => $user->id,
            'part' => $part,
        ]);
        return redirect()->route('test.start')
            ->with('error', '無効なパート番号です。');
    }

    // セッション取得
    $session = ExamSession::where('user_id', $user->id)
        ->whereNull('finished_at')
        ->whereNull('disqualified_at')
        ->first();

    Log::info('既存セッションの確認', [
        'user_id' => $user->id,
        'session_found' => $session ? 'yes' : 'no',
        'session_id' => $session ? $session->id : null,
    ]);

    // ★★★ 重要修正: セッションがない場合は新規作成 ★★★
    if (! $session) {
        Log::info('セッションが存在しないため新規作成', [
            'user_id' => $user->id,
            'requested_part' => $part,
        ]);

        // セッションコードを取得
        $sessionCode = session('exam_session_code') ?? session('verified_session_code');
        
        Log::info('セッションコード取得試行', [
            'exam_session_code' => session('exam_session_code'),
            'verified_session_code' => session('verified_session_code'),
            'selected_code' => $sessionCode,
        ]);
        
        if (!$sessionCode) {
            Log::error('セッションコードが見つからない', [
                'user_id' => $user->id,
                'all_session_data' => session()->all(),
            ]);
            
            // ★ セッションコードがなくても、デフォルトで試験を開始できるようにする
            $examType = 'full'; // デフォルト
            
        } else {
            $event = $this->getEventBySessionCode($sessionCode);

            if (! $event) {
                Log::error('イベントが見つからない', [
                    'user_id' => $user->id,
                    'session_code' => $sessionCode,
                ]);
                
                // ★ イベントが見つからなくてもデフォルトで続行
                $examType = 'full';
            } else {
                $examType = $event->exam_type;
            }
        }

        // 新規セッション作成
        $session = ExamSession::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'current_part' => $part, // ★ 要求されたパートから開始
            'current_question' => 1,
            'remaining_time' => 0,
            'security_log' => json_encode([
                'exam_type' => $examType,
                'event_id' => $event->id ?? null,
            ]),
        ]);

        Log::info('新規セッション作成完了', [
            'user_id' => $user->id,
            'exam_session_id' => $session->id,
            'starting_part' => $part,
            'exam_type' => $examType,
        ]);
    }

    // ★★★ 追加: 要求されたパートが現在のパートより前の場合は警告 ★★★
    if ($part < $session->current_part) {
        Log::warning('既に完了したパートへのアクセス試行', [
            'user_id' => $user->id,
            'requested_part' => $part,
            'current_part' => $session->current_part,
        ]);
        
        return redirect()->route('exam.part', ['part' => $session->current_part])
            ->with('info', "第{$part}部は既に完了しています。第{$session->current_part}部から続けてください。");
    }

    // ★★★ 追加: current_part を更新（要求されたパートに進む） ★★★
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

    // 試験タイプを取得
    $securityLog = json_decode($session->security_log ?? '{}', true);
    $examType = $securityLog['exam_type'] ?? 'full';

    // 違反回数をチェック
    $violationCount = ExamViolation::where('exam_session_id', $session->id)->count();
    if ($violationCount >= 3) {
        if (! $session->disqualified_at) {
            $this->disqualifySession($session, 'Multiple security violations');
        }

        return redirect()->route('exam.disqualified');
    }

    // 残り時間の処理
    if ($session->remaining_time > 0) {
        $remainingTime = $session->remaining_time;
    } else {
        $partTimeLimit = $this->getPartTimeLimitByEvent($part, $examType);
        $remainingTime = $partTimeLimit;

        $session->update([
            'remaining_time' => $remainingTime,
            'started_at' => $session->started_at ?? now(),
        ]);
    }

    // 時間切れチェック
    if ($remainingTime <= 0) {
        return $this->autoCompletePartDueToTimeout($session);
    }

    // セキュリティ用のセッションIDを生成
    $sessionId = (string) Str::uuid();

    // セッションキーを記録(後でクリーンアップするため)
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
    ], 30 * 60);

    // security_log から保存済みの解答を取得
    $savedAnswers = $securityLog['part_'.$part.'_answers'] ?? [];

    Log::info('保存済み解答の読み込み', [
        'user_id' => $user->id,
        'part' => $part,
        'saved_answers_count' => count($savedAnswers),
        'exam_type' => $examType,
    ]);

    // 問題数を取得
    $questionCount = $this->getQuestionCountByEvent($part, $examType);

    // 問題を取得(試験タイプに応じた数だけ)
    $questions = Question::with(['choices' => function ($query) use ($part) {
        $query->where('part', $part)->orderBy('label');
    }])
        ->where('part', $part)
        ->orderBy('number')
        ->limit($questionCount)
        ->get()
        ->map(function ($q) use ($savedAnswers) {
            $questionData = [
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

            return $questionData;
        });

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
        'partTime' => $this->getPartTimeLimitByEvent($part, $examType),
        'remainingTime' => $remainingTime,
        'currentQuestion' => $session->current_question,
        'totalParts' => 3,
        'violationCount' => $violationCount,
        'examType' => $examType,
    ]);
}

    /**
     * パート完了処理(修正版) - 各部完了後は次の部の練習問題へ
     */
    /**
 * パート完了処理(修正版) - 各部完了後は次の部の練習問題へ
 */
public function completePart(Request $request)
{
    $validated = $request->validate([
        'examSessionId' => 'required|uuid',
        'part' => 'required|integer|in:1,2,3',
        'answers' => 'required|array',
        'timeSpent' => 'required|integer|min:1',
        'startTime' => 'required|integer',
        'endTime' => 'required|integer',
        'totalQuestions' => 'required|integer|min:1',
    ]);

    try {
        DB::beginTransaction();

        $user = Auth::user();
        $part = $validated['part'];
        $cacheSessionId = $validated['examSessionId'];

        Log::info("=== completePart 開始 ===", [
            'user_id' => $user->id,
            'part' => $part,
            'cache_session_id' => $cacheSessionId,
        ]);

        // キャッシュからセッション情報を取得
        $cacheKey = "exam_part_session_{$user->id}_{$cacheSessionId}";
        $cacheSession = Cache::get($cacheKey);

        if (! $cacheSession) {
            DB::rollBack();
            Log::error('キャッシュセッション見つからず', [
                'user_id' => $user->id,
                'cache_key' => $cacheKey,
            ]);
            return back()->withErrors(['examSessionId' => '無効なセッションです。']);
        }

        $examType = $cacheSession['exam_type'] ?? 'full';

        // ExamSessionを取得
        $examSession = ExamSession::where('user_id', $user->id)
            ->where('id', $cacheSession['exam_session_id'])
            ->whereNull('finished_at')
            ->whereNull('disqualified_at')
            ->first();

        if (! $examSession) {
            DB::rollBack();
            Log::error('ExamSession見つからず', [
                'user_id' => $user->id,
                'exam_session_id' => $cacheSession['exam_session_id'],
            ]);
            return back()->withErrors(['examSessionId' => '試験セッションが見つかりません。']);
        }

        // このパートの解答をsecurity_logに保存
        $securityLog = json_decode($examSession->security_log ?? '{}', true);
        
        if (!isset($securityLog['part_'.$part.'_answers'])) {
            $securityLog['part_'.$part.'_answers'] = [];
        }

        // リクエストの解答をマージ
        foreach ($validated['answers'] as $questionId => $choice) {
            $securityLog['part_'.$part.'_answers'][$questionId] = $choice;
        }

        // security_logを更新
        $examSession->update([
            'security_log' => json_encode($securityLog),
        ]);

        Log::info("第{$part}部完了 - security_log更新", [
            'user_id' => $user->id,
            'part' => $part,
            'answers_count' => count($securityLog['part_'.$part.'_answers']),
        ]);

        // ★ 重要修正: 次のアクションを決定
        if ($part < 3) {
            // 第一部・第二部完了後は次の部の練習問題へ
            $nextPart = $part + 1;
            
            $examSession->update([
                'current_part' => $nextPart, // ★ current_part を更新
                'current_question' => 1,
                'remaining_time' => 0,
            ]);

            Cache::forget($cacheKey);
            DB::commit();

            Log::info("第{$part}部完了 - 第{$nextPart}部練習問題へ遷移", [
                'user_id' => $user->id,
                'completed_part' => $part,
                'next_part' => $nextPart,
                'updated_current_part' => $examSession->fresh()->current_part,
            ]);

            // ★ 練習問題ページへリダイレクト
            return redirect()->route('practice.show', ['section' => $nextPart])
                ->with('success', "第{$part}部が完了しました。第{$nextPart}部の練習問題を開始してください。");
                
        } else {
            // ★ 第三部完了後は全パートの解答をanswersテーブルに保存して結果表示へ
            Log::info('第三部完了 - 全パートの採点開始', [
                'user_id' => $user->id,
                'exam_session_id' => $examSession->id,
            ]);

            // 全パートの解答を統合
            $allAnswers = [];
            for ($p = 1; $p <= 3; $p++) {
                if (isset($securityLog['part_'.$p.'_answers'])) {
                    $allAnswers = $allAnswers + $securityLog['part_'.$p.'_answers'];
                }
            }

            // answersテーブルに保存
            $savedCount = 0;
            foreach ($allAnswers as $questionId => $choice) {
                if (! is_numeric($questionId) || ! in_array($choice, ['A', 'B', 'C', 'D', 'E'])) {
                    continue;
                }

                $question = Question::with('choices')->find($questionId);
                if (! $question) {
                    continue;
                }

                $correctChoice = $question->choices()
                    ->where('part', $question->part)
                    ->where('is_correct', 1)
                    ->first();

                $isCorrect = false;
                if ($correctChoice) {
                    $isCorrect = (trim($correctChoice->label) === trim($choice));
                }

                Answer::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'exam_session_id' => $examSession->id,
                        'part' => $question->part,
                        'choice' => $choice,
                        'is_correct' => $isCorrect,
                    ]
                );

                $savedCount++;
            }

            // すべてのキャッシュを削除
            Cache::forget($cacheKey);
            for ($p = 1; $p <= 3; $p++) {
                Cache::forget("exam_answers_{$user->id}_{$p}");
            }

            // セッション完了
            $examSession->update([
                'finished_at' => now(),
                'current_part' => 3,
                'security_log' => null,
            ]);

            DB::commit();

            Log::info('試験完了 - 全パート採点完了', [
                'user_id' => $user->id,
                'exam_session_id' => $examSession->id,
                'total_answers' => count($allAnswers),
                'saved_count' => $savedCount,
            ]);

            return redirect()->route('exam.result', ['sessionUuid' => $examSession->session_uuid])
                ->with('success', '試験が完了しました。');
        }

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Part completion failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return back()->withErrors(['general' => 'システムエラーが発生しました。']);
    }
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
     * ゲスト用本番試験パート表示
     */
    /**
 * ゲスト用本番試験パート表示 - 修正版
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

    // ★★★ 重要修正: セッションがない場合は新規作成 ★★★
    if (! $session) {
        Log::info('ゲストセッションが存在しないため新規作成', [
            'guest_id' => $guestId,
            'requested_part' => $part,
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
            'current_part' => $part, // ★ 要求されたパートから開始
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
        $partTimeLimit = $this->getPartTimeLimit($part);
        $remainingTime = $partTimeLimit;

        $session['remaining_time'] = $remainingTime;
        Cache::put($existingSessionKey, $session, 2 * 60 * 60);
    }

    // 時間切れチェック
    if ($remainingTime <= 0) {
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
    ]);

    // 該当パートの問題を取得
    $questions = Question::with(['choices' => function ($query) use ($part) {
        $query->where('part', $part)->orderBy('label');
    }])
        ->where('part', $part)
        ->orderBy('number')
        ->get()
        ->map(function ($q) use ($savedAnswers) {
            $questionData = [
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

            return $questionData;
        });

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
        'partTime' => $this->getPartTimeLimitByEvent($part, $examType),
        'remainingTime' => $remainingTime,
        'currentQuestion' => $session->current_question,
        'totalParts' => 3,
        'violationCount' => $violationCount,
        'examType' => $examType,
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

            return Inertia::location(route('guest.exam.part', ['part' => $existingSession['current_part'] ?? 1]));
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
        return Inertia::location(route('guest.exam.part', ['part' => 1]));
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

        // 各部の結果を集計
        $results = [];

        for ($part = 1; $part <= 3; $part++) {
            // 該当部の解答を取得
            $answers = Answer::where('user_id', $user->id)
                ->where('exam_session_id', $session->id)
                ->where('part', $part)
                ->get();

            // 試験タイプに応じた正しい問題数
            $totalQuestions = $this->getQuestionCountByEvent($part, $examType);

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
        }

        // 総合スコア
        $totalScore = $results[1]['score'] + $results[2]['score'] + $results[3]['score'];

        // ランク判定
        if ($totalScore >= 61) {
            $rank = 'A';
            $rankName = 'Platinum';
        } elseif ($totalScore >= 51) {
            $rank = 'B';
            $rankName = 'Gold';
        } elseif ($totalScore >= 36) {
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
     * ゲストパート完了処理 - 修正版(各部完了後は次の部の練習問題へ)
     */
    public function guestCompletePart(Request $request)
    {
        $validated = $request->validate([
            'examSessionId' => 'required|uuid',
            'part' => 'required|integer|in:1,2,3',
            'answers' => 'required|array',
            'timeSpent' => 'required|integer|min:1',
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

        // 回答をキャッシュに保存
        $answers = $request->input('answers', []);
        $sanitizedAnswers = $this->sanitizeAnswers($answers);
        $answersKey = "guest_exam_answers_{$guestId}_part_{$part}";
        Cache::put($answersKey, $sanitizedAnswers, 2 * 60 * 60);

        Log::info("ゲスト第{$part}部の解答を保存", [
            'guest_id' => $guestId,
            'part' => $part,
            'answers_count' => count($sanitizedAnswers),
        ]);

        // ★ 重要修正: 次のアクションを決定
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
            // ★ 第三部完了時に全パートの採点を実行
            Log::info('ゲスト試験完了 - 全パート採点開始', [
                'guest_id' => $guestId,
            ]);

            // 各パートの結果を集計
            $results = [];

            for ($p = 1; $p <= 3; $p++) {
                $partAnswersKey = "guest_exam_answers_{$guestId}_part_{$p}";
                $partAnswers = Cache::get($partAnswersKey, []);

                // 各部の正しい問題数
                if ($p == 1) {
                    $totalQuestions = 40;
                } elseif ($p == 2) {
                    $totalQuestions = 30;
                } else {
                    $totalQuestions = 25;
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
            }

            // 総合スコア
            $totalScore = $results[1]['score'] + $results[2]['score'] + $results[3]['score'];

            // ランク判定
            if ($totalScore >= 61) {
                $rank = 'A';
                $rankName = 'Platinum';
            } elseif ($totalScore >= 51) {
                $rank = 'B';
                $rankName = 'Gold';
            } elseif ($totalScore >= 36) {
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
     * イベント情報から試験時間を取得
     */
    private function getPartTimeLimitByEvent($part, $examType = 'full')
    {
        // full版の時間設定
        $fullTimeLimits = [
            1 => 600,   // 10分(600秒) - 40問
            2 => 900,   // 15分(900秒) - 30問
            3 => 1800,  // 30分(1800秒) - 25問
        ];

        // 45min版の時間設定(1問あたりの時間は同じ)
        $min45TimeLimits = [
            1 => 450,   // 7.5分(450秒) - 30問 (15秒/問)
            2 => 600,   // 10分(600秒) - 20問 (30秒/問)
            3 => 1080,  // 18分(1080秒) - 15問 (72秒/問)
        ];

        // 30min版の時間設定(1問あたりの時間は同じ)
        $min30TimeLimits = [
            1 => 300,   // 5分(300秒) - 20問 (15秒/問)
            2 => 390,   // 6.5分(390秒) - 13問 (30秒/問)
            3 => 720,   // 12分(720秒) - 10問 (72秒/問)
        ];

        switch ($examType) {
            case '45min':
                return $min45TimeLimits[$part] ?? 1080;
            case '30min':
                return $min30TimeLimits[$part] ?? 720;
            case 'full':
            default:
                return $fullTimeLimits[$part] ?? 1800;
        }
    }

    /**
     * イベント情報から問題数を取得
     */
    private function getQuestionCountByEvent($part, $examType = 'full')
    {
        // full版の問題数
        $fullQuestionCounts = [
            1 => 40,
            2 => 30,
            3 => 25,
        ];

        // 45min版の問題数
        $min45QuestionCounts = [
            1 => 30,  // 7.5分 / 15秒/問
            2 => 20,  // 10分 / 30秒/問
            3 => 15,  // 18分 / 72秒/問
        ];

        // 30min版の問題数
        $min30QuestionCounts = [
            1 => 20,  // 5分 / 15秒/問
            2 => 13,  // 6.5分 / 30秒/問
            3 => 10,  // 12分 / 72秒/問
        ];

        switch ($examType) {
            case '45min':
                return $min45QuestionCounts[$part] ?? 15;
            case '30min':
                return $min30QuestionCounts[$part] ?? 10;
            case 'full':
            default:
                return $fullQuestionCounts[$part] ?? 25;
        }
    }

    /**
     * セッションコードからイベント情報を取得
     */
    private function getEventBySessionCode($sessionCode)
    {
        if (! $sessionCode) {
            return null;
        }

        $event = \App\Models\Event::where('passphrase', $sessionCode)
            ->where('begin', '<=', now())
            ->where('end', '>=', now())
            ->first();

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
}
