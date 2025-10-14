<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Question;
use App\Models\ExamSession;
use App\Models\Answer;
use App\Models\ExamViolation;

class ExamController extends Controller
{
    /**
     * 本番テスト開始処理(セキュリティ対応版) - 修正版
     */
    public function start()
    {
        $user = Auth::user();
        
        // ★ 修正: 未完了・未失格セッションがあるかチェック (finished_at が NULL のものだけ)
        $existingSession = ExamSession::where('user_id', $user->id)
            ->whereNull('finished_at')  // ★ 完了していないもの
            ->whereNull('disqualified_at')
            ->first();
            
        if ($existingSession) {
            // 失格チェック
            $violationCount = ExamViolation::where('exam_session_id', $existingSession->id)->count();
            if ($violationCount >= 3 && !$existingSession->disqualified_at) {
                $this->disqualifySession($existingSession, 'Multiple security violations');
                return redirect()->route('exam.disqualified');
            }
            
            if ($existingSession->disqualified_at) {
                return redirect()->route('exam.disqualified');
            }
            
            // 既存セッションがある場合は復帰
            return redirect()->route('exam.part', ['part' => $existingSession->current_part])
                ->with('info', '前回の続きから開始します。');
        }
        
        // ★ 追加: 完了済みの古いセッションを完全にクリーンアップ
        $completedSessions = ExamSession::where('user_id', $user->id)
            ->whereNotNull('finished_at')
            ->get();
        
        foreach ($completedSessions as $completedSession) {
            // security_log を完全に削除
            $completedSession->update([
                'security_log' => null,
            ]);
            
            // 関連する解答キャッシュも削除
            for ($p = 1; $p <= 3; $p++) {
                Cache::forget("exam_answers_{$user->id}_{$p}");
            }
            
            Log::info('完了済みセッションをクリーンアップ', [
                'user_id' => $user->id,
                'exam_session_id' => $completedSession->id,
                'finished_at' => $completedSession->finished_at,
            ]);
        }
        
        // ★ 追加: すべての関連キャッシュを削除
        $this->cleanupAllUserCache($user->id);
        
        // 新しいセッションを作成
        $session = ExamSession::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'current_part' => 1,
            'current_question' => 1,
            'remaining_time' => 0,
            'security_log' => json_encode([]), // 空の JSON で初期化
        ]);
        
        Log::info('新しい試験セッション作成', [
            'user_id' => $user->id,
            'exam_session_id' => $session->id,
            'session_uuid' => $session->session_uuid,
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
     * パート画面表示（セキュリティ対応版）- 修正版
     */
    public function part(Request $request, $part)
    {
        $user = Auth::user();
        $part = (int) $part;
        
        // パート番号の検証
        if (!in_array($part, [1, 2, 3])) {
            return redirect()->route('test.start')
                ->with('error', '無効なパート番号です。');
        }
        
        // セッション取得
        $session = ExamSession::where('user_id', $user->id)
            ->whereNull('finished_at')
            ->whereNull('disqualified_at')
            ->first();
            
        if (!$session) {
            return redirect()->route('test.start')
                ->with('error', 'セッションが見つかりません。最初から開始してください。');
        }

        // ★ 追加: 完了済みセッションの場合は新規作成を促す
        if ($session->finished_at) {
            Log::warning('完了済みセッションへのアクセス', [
                'user_id' => $user->id,
                'exam_session_id' => $session->id,
                'finished_at' => $session->finished_at,
            ]);
            
            return redirect()->route('test.start')
                ->with('error', '試験は既に完了しています。新しい試験を開始してください。');
        }
        
        // 違反回数をチェック
        $violationCount = ExamViolation::where('exam_session_id', $session->id)->count();
        if ($violationCount >= 3) {
            if (!$session->disqualified_at) {
                $this->disqualifySession($session, 'Multiple security violations');
            }
            return redirect()->route('exam.disqualified');
        }
        
        // 残り時間の処理
        if ($session->remaining_time > 0) {
            $remainingTime = $session->remaining_time;
        } else {
            $partTimeLimit = $this->getPartTimeLimit($part);
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

        // ★ 追加: セッションキーを記録(後でクリーンアップするため)
        $sessionKeys = Cache::get("exam_part_session_keys_{$user->id}", []);
        $sessionKeys[] = "exam_part_session_{$user->id}_{$sessionId}";
        Cache::put("exam_part_session_keys_{$user->id}", $sessionKeys, 2 * 60 * 60);
        
        // セッション情報をキャッシュに保存(30分で期限切れ)
        Cache::put("exam_part_session_{$user->id}_{$sessionId}", [
            'user_id' => $user->id,
            'exam_session_id' => $session->id,
            'part' => $part,
            'started_at' => now(),
        ], 30 * 60);
        
        // security_log から保存済みの解答を取得
        $securityLog = json_decode($session->security_log ?? '{}', true);
        $savedAnswers = $securityLog['part_' . $part . '_answers'] ?? [];
        
        Log::info('保存済み解答の読み込み', [
            'user_id' => $user->id,
            'part' => $part,
            'saved_answers_count' => count($savedAnswers),
            'saved_answers' => $savedAnswers
        ]);
        
        // 問題を取得
        $questions = Question::with(['choices' => function($query) use ($part) {
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
            'first_question_selected' => $questions->first()['selected'] ?? null
        ]);
        
        return Inertia::render('Part', [
            'examSessionId' => $sessionId,
            'practiceSessionId' => $sessionId,
            'practiceQuestions' => $questions,
            'part' => $part,
            'questions' => $questions,
            'currentPart' => $part,
            'partTime' => $this->getPartTimeLimit($part),
            'remainingTime' => $remainingTime,
            'currentQuestion' => $session->current_question,
            'totalParts' => 3,
            'violationCount' => $violationCount,
        ]);
    }
        /**
         * パート完了処理(修正版)
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
                
                // キャッシュからセッション情報を取得
                $cacheKey = "exam_part_session_{$user->id}_{$cacheSessionId}";
                $cacheSession = Cache::get($cacheKey);
                
                if (!$cacheSession) {
                    DB::rollBack();
                    return back()->withErrors(['examSessionId' => '無効なセッションです。']);
                }
                
                // ExamSessionを取得
                $examSession = ExamSession::where('user_id', $user->id)
                    ->where('id', $cacheSession['exam_session_id'])
                    ->whereNull('finished_at')
                    ->whereNull('disqualified_at')
                    ->first();

                if (!$examSession) {
                    DB::rollBack();
                    return back()->withErrors(['examSessionId' => '試験セッションが見つかりません。']);
                }

    /**
     * パート完了処理(修正版)
     */

            if ($part === 3) {
                Log::info('第三部完了: 全パートの解答を answers テーブルに保存開始', [
                    'user_id' => $user->id,
                    'exam_session_id' => $examSession->id,
                ]);

                // ★ 重要: リクエストから受け取った第三部の解答をsecurity_logに保存
                $securityLog = json_decode($examSession->security_log ?? '{}', true);
                
                // 第三部の解答をsecurity_logに追加（これがないと第三部が保存されない）
                if (!isset($securityLog['part_3_answers'])) {
                    $securityLog['part_3_answers'] = [];
                }
                
                // リクエストの解答をマージ
                foreach ($validated['answers'] as $questionId => $choice) {
                    $securityLog['part_3_answers'][$questionId] = $choice;
                }
                
                // security_logを更新
                $examSession->update([
                    'security_log' => json_encode($securityLog)
                ]);
                
                Log::info('security_log更新完了', [
                    'part_1_count' => count($securityLog['part_1_answers'] ?? []),
                    'part_2_count' => count($securityLog['part_2_answers'] ?? []),
                    'part_3_count' => count($securityLog['part_3_answers'] ?? []),
                ]);
                
                // ★ 重要: array_mergeを使わず、キーを保持したまま統合
                $allAnswers = [];
                
                for ($p = 1; $p <= 3; $p++) {
                    if (isset($securityLog['part_' . $p . '_answers'])) {
                        $partAnswers = $securityLog['part_' . $p . '_answers'];
                        // ★ キーを保持して追加（+ 演算子を使用）
                        $allAnswers = $allAnswers + $partAnswers;
                        Log::info("第{$p}部の解答を追加", [
                            'count' => count($partAnswers),
                            'sample' => array_slice($partAnswers, 0, 3, true)
                        ]);
                    }
                }
                
                Log::info('統合された全解答', [
                    'total_count' => count($allAnswers),
                    'question_ids' => array_keys($allAnswers)
                ]);

                // 全パートの解答を answers テーブルに保存
                $savedCount = 0;
                foreach ($allAnswers as $questionId => $choice) {
                    if (!is_numeric($questionId) || !in_array($choice, ['A', 'B', 'C', 'D', 'E'])) {
                        Log::warning('無効な解答データをスキップ', [
                            'question_id' => $questionId,
                            'choice' => $choice
                        ]);
                        continue;
                    }

                    $question = Question::with('choices')->find($questionId);
                    if (!$question) {
                        Log::warning('問題が見つかりません', [
                            'question_id' => $questionId
                        ]);
                        continue;
                    }

                    // 正解判定を厳密に実行
                    $correctChoice = $question->choices()
                        ->where('part', $question->part)
                        ->where('is_correct', 1)
                        ->first();
                    
                    $isCorrect = false;
                    if ($correctChoice) {
                        $isCorrect = (trim($correctChoice->label) === trim($choice));
                    }
                    
                    Log::info('正解判定チェック', [
                        'question_id' => $questionId,
                        'question_number' => $question->number,
                        'question_part' => $question->part,
                        'user_choice' => $choice,
                        'correct_choice_label' => $correctChoice ? $correctChoice->label : null,
                        'is_correct' => $isCorrect ? 'true' : 'false',
                    ]);

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

                Log::info('answers テーブルへの保存完了', [
                    'user_id' => $user->id,
                    'exam_session_id' => $examSession->id,
                    'total_answers' => count($allAnswers),
                    'saved_count' => $savedCount
                ]);
            }

            // 次のアクションを決定
            if ($part < 3) {
                // 次のパートに進む
                $examSession->update([
                    'current_part' => max($examSession->current_part, $part + 1),
                    'current_question' => 1,
                    'remaining_time' => 0,
                ]);
                
                Cache::forget($cacheKey);
                DB::commit();
                
                Log::info('パート完了 - 次のパートへ', [
                    'user_id' => $user->id,
                    'completed_part' => $part,
                    'next_part' => $part + 1
                ]);
                
                return redirect()->route('practice.show', ['section' => $part + 1])
                    ->with('success', "第{$part}部が完了しました。第".($part + 1)."部の練習問題を開始してください。");
            } else {
                // 全部完了 - すべてのキャッシュとセッション情報を削除
                
                // ★ 追加: すべての関連キャッシュを削除
                Cache::forget($cacheKey);
                
                // パートごとの解答キャッシュを削除
                for ($p = 1; $p <= 3; $p++) {
                    Cache::forget("exam_answers_{$user->id}_{$p}");
                }
                
                // パートセッションキーをすべて削除
                $partSessionKeys = Cache::get("exam_part_session_keys_{$user->id}", []);
                foreach ($partSessionKeys as $key) {
                    Cache::forget($key);
                }
                Cache::forget("exam_part_session_keys_{$user->id}");
                
                // 一般的なクリーンアップも実行
                $this->cleanupExamCache($user->id, $examSession->id);
                
                // ★ 修正: security_log を null に設定して完全に削除
                $examSession->update([
                    'finished_at' => now(),
                    'current_part' => 3,
                    'security_log' => null, // ★ 重要: null に設定して完全削除
                ]);
                
                DB::commit();
                
                Log::info('試験完了 - すべてのキャッシュとsecurity_logを削除', [
                    'user_id' => $user->id,
                    'exam_session_id' => $examSession->id,
                    'session_uuid' => $examSession->session_uuid,
                    'cache_keys_deleted' => count($partSessionKeys ?? []),
                ]);
                
                // session_uuid が存在することを確認
                if (!$examSession->session_uuid) {
                    Log::error('session_uuid が存在しません', [
                        'exam_session_id' => $examSession->id,
                    ]);
                    
                    return redirect()->route('test.start')
                        ->with('error', 'セッションエラーが発生しました。');
                }
                
                return redirect()->route('exam.result', ['sessionUuid' => $examSession->session_uuid])
                    ->with('success', '試験が完了しました。');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Part completion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'part' => $part ?? null,
            ]);

            return back()->withErrors([
                'general' => 'システムエラーが発生しました。'
            ]);
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
     * 進捗保存処理（セキュリティ対応版）
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
        
        // セッション検証（修正版）
        $examSession = ExamSession::where('user_id', $user->id)
            ->where('id', $sessionId)
            ->whereNull('finished_at')
            ->whereNull('disqualified_at')
            ->first();
            
        if (!$examSession) {
            return response()->json(['success' => false, 'message' => 'セッションが無効です。']);
        }
        
        if ($examSession->disqualified_at) {
            return response()->json(['success' => false, 'message' => 'セッションが無効または失格です。']);
        }
        
        $examSession->update([
            'current_question' => $request->input('currentQuestion'),
            'remaining_time' => max(0, $request->input('remainingTime')),
        ]);
        
        // 現在の回答を一時保存（JSON形式）
        $answers = $this->sanitizeAnswers($request->input('answers', []));
        Cache::put("exam_answers_{$user->id}_{$examSession->current_part}", $answers, 3600);
        
        return response()->json(['success' => true]);
    }

    /**
     * 単一解答の即時保存（本番試験用）
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
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'ゲストモードでは保存できません'
                ], 403);
            }

            $cacheSessionId = $validated['examSessionId'];
            
            // キャッシュからセッション情報を取得
            $cacheKey = "exam_part_session_{$user->id}_{$cacheSessionId}";
            $cacheSession = Cache::get($cacheKey);
            
            if (!$cacheSession) {
                return response()->json([
                    'success' => false, 
                    'message' => '無効なセッションです。'
                ], 403);
            }
            
            // データベースのExamSessionを取得
            $examSession = ExamSession::where('user_id', $user->id)
                ->where('id', $cacheSession['exam_session_id'])
                ->whereNull('finished_at')
                ->whereNull('disqualified_at')
                ->first();

            if (!$examSession) {
                return response()->json([
                    'success' => false, 
                    'message' => '無効な試験セッションです。'
                ], 403);
            }

            // 現在の解答状況を security_log に保存
            $securityLog = json_decode($examSession->security_log ?? '{}', true);
            
            if (!isset($securityLog['part_' . $validated['part'] . '_answers'])) {
                $securityLog['part_' . $validated['part'] . '_answers'] = [];
            }
            
            $securityLog['part_' . $validated['part'] . '_answers'][$validated['questionId']] = $validated['choice'];
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
                'part' => $validated['part']
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('解答保存失敗', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。'
            ], 500);
        }
    }

    

    /**
     * 単一解答の即時保存（ゲストユーザー用）
     */
    public function guestPart(Request $request, $part)
    {
        $guestId = session()->getId();
        $part = (int) $part;
        
        // パート番号の検証
        if (!in_array($part, [1, 2, 3])) {
            return redirect()->route('guest.test.start')
                ->with('error', '無効なパート番号です。');
        }
        
        // ゲストセッション取得
        $existingSessionKey = "guest_exam_session_{$guestId}";
        $session = Cache::get($existingSessionKey);
            
        if (!$session) {
            return redirect()->route('guest.test.start')
                ->with('error', 'セッションが見つかりません。最初から開始してください。');
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
            'saved_answers' => $savedAnswers
        ]);
        
        // 該当パートの問題を取得
        $questions = Question::with(['choices' => function($query) use ($part) {
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
        
        Log::info('ゲスト問題データの生成完了', [
            'guest_id' => $guestId,
            'part' => $part,
            'questions_count' => $questions->count(),
            'first_question_selected' => $questions->first()['selected'] ?? null
        ]);
        
        return Inertia::render('Part', [
            'examSessionId' => $sessionId,
            'practiceSessionId' => $sessionId,
            'practiceQuestions' => $questions,
            'questions' => $questions,
            'currentPart' => $part,
            'part' => $part,
            'partTime' => $this->getPartTimeLimit($part),
            'remainingTime' => $remainingTime,
            'currentQuestion' => $session['current_question'],
            'totalParts' => 3,
            'isGuest' => true,
            'violationCount' => $session['violation_count'] ?? 0,
        ]);
    }

    /**
     * ゲスト用本番試験開始処理（キャッシュのみ、DBには保存しない）
     */
    public function guestStart(Request $request)
    {
        $guestId = session()->getId();

            // デバッグログ
            Log::info('=== guestStart呼び出し ===', [
                'guest_id' => $guestId,
                'all_session' => session()->all(),
                'request_data' => $request->all(),
            ]);
        
        // ゲスト情報の確認（より柔軟に）
        $guestName = session('guest_name') ?? session('guest_info.name') ?? 'ゲスト';
        $guestSchool = session('guest_school_name') ?? session('guest_info.school_name') ?? '学校名未入力';
        
        Log::info('ゲスト試験開始リクエスト', [
            'guest_id' => $guestId,
            'guest_name' => $guestName,
            'guest_school' => $guestSchool,
            'session_data' => session()->all()
        ]);

        // 既存のゲスト試験セッションがあるか確認
        $existingSessionKey = "guest_exam_session_{$guestId}";
        $existingSession = Cache::get($existingSessionKey);

        if ($existingSession) {
            // 既存セッションがあればそれを使用（DBには保存しない）
            Log::info('既存のゲスト試験セッションを使用', [
                'guest_id' => $guestId,
                'current_part' => $existingSession['current_part'] ?? 1
            ]);
            
            // ★ 修正: JSONではなくリダイレクトで返す
            return redirect()->route('guest.exam.part', ['part' => $existingSession['current_part'] ?? 1])
                ->with('success', '試験を再開します');
        }

        // 新しい試験セッションを作成（キャッシュのみ、DBには保存しない）
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
        
        // 2時間有効なキャッシュに保存（DBには保存しない）
        Cache::put($existingSessionKey, $newSession, 2 * 60 * 60);
        
        // セッションにもゲスト情報を保存（念のため）
        session([
            'guest_name' => $guestName,
            'guest_school_name' => $guestSchool,
        ]);

        Log::info('新しいゲスト試験セッション作成（キャッシュのみ）', [
            'guest_id' => $guestId,
            'guest_name' => $guestName,
            'guest_school' => $guestSchool,
        ]);

        // ★ 修正: リダイレクトで返す（JSONレスポンスを削除）
        return redirect()->route('guest.exam.part', ['part' => 1])
            ->with('success', '試験セッションが作成されました');
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
            'violationCount' => 'required|integer'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }
        
        $user = Auth::user();
        $sessionId = $request->input('examSessionId');
        
        // セッション検証（修正版）
        $examSession = ExamSession::where('user_id', $user->id)
            ->where('id', $sessionId)
            ->first();
            
        if (!$examSession || $examSession->user_id !== $user->id) {
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
                'violation_count' => $request->violationCount
            ]),
        ]);
        
        // ログに記録
        Log::warning('Exam violation detected', [
            'user_id' => $user->id,
            'exam_session_id' => $examSession->id,
            'violation_type' => $request->violationType,
            'violation_count' => $request->violationCount
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

        if (!$examSession) {
            return redirect()->route('test.start');
        }

        $violations = ExamViolation::where('exam_session_id', $examSession->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Exam/Disqualified', [
            'examSession' => $examSession,
            'violations' => $violations,
            'disqualificationReason' => $examSession->disqualification_reason
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
        
        // 各部の結果を集計
        $results = [];
        
        for ($part = 1; $part <= 3; $part++) {
            // 該当部の解答を取得
            $answers = Answer::where('user_id', $user->id)
                ->where('exam_session_id', $session->id)
                ->where('part', $part)
                ->get();
            
            // ★ 修正: 各部の正しい問題数
            if ($part == 1) {
                $totalQuestions = 40;
            } elseif ($part == 2) {
                $totalQuestions = 30;
            } else {
                $totalQuestions = 25;
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
        
        // ★ 重要: セッションに保存
        session([
            'exam_results' => [
                'results' => $results,
                'rankName' => $rankName,
                'totalScore' => round($totalScore, 2),
                'rank' => $rank,
            ],
            'isGuest' => false,
        ]);
        
        // ★ 重要: Inertiaに渡すデータ
        return Inertia::render('Result', [
            'results' => $results,  // これが重要
            'totalScore' => round($totalScore, 2),
            'rank' => $rank,
            'rankName' => $rankName,
            'isGuest' => false,
        ]);
    }
    
   /**
     * ゲストパート完了処理 - 修正版
     */
    public function guestCompletePart(Request $request)
    {
        // バリデーション
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
        if (!$sessionData) {
            Log::warning('不正なゲスト試験セッション', [
                'guest_id' => $guestId,
                'session_id' => $sessionId,
                'ip' => $request->ip()
            ]);
            
            return redirect()->route('guest.test.start')
                ->with('error', 'セッションが無効です。試験を最初からやり直してください。');
        }
        
        // ゲストセッション取得
        $existingSessionKey = "guest_exam_session_{$guestId}";
        $examSession = Cache::get($existingSessionKey);
        if (!$examSession) {
            return redirect()->route('guest.test.start')
                ->with('error', 'セッションが見つかりません。');
        }
        
        // 違反チェック
        if (($examSession['violation_count'] ?? 0) >= 3) {
            return redirect()->route('guest.exam.disqualified');
        }
        
        // 回答をキャッシュに保存(復帰用・一時保存のみ)
        $answers = $request->input('answers', []);
        $sanitizedAnswers = $this->sanitizeAnswers($answers);
        
        $answersKey = "guest_exam_answers_{$guestId}_part_{$part}";
        Cache::put($answersKey, $sanitizedAnswers, 2 * 60 * 60);
        
        Log::info("ゲスト第{$part}部の解答を保存", [
            'guest_id' => $guestId,
            'part' => $part,
            'answers_count' => count($sanitizedAnswers),
            'sample_answers' => array_slice($sanitizedAnswers, 0, 3, true)
        ]);
        
        // セッション情報を更新
        $nextPart = $part + 1;
        
        if ($nextPart <= 3) {
            // 次のパートに進む
            $examSession['current_part'] = $nextPart;
            $examSession['current_question'] = 1;
            $examSession['remaining_time'] = 0;
            Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);
            
            // このパートのキャッシュセッションを削除
            Cache::forget($cacheKey);
            
            return redirect()->route('guest.practice.show', ['section' => $part + 1])
                ->with('success', "第{$part}部が完了しました。第".($part + 1)."部の練習問題を開始してください。");
        } else {
            // ★ 重要: 全部完了時に全パートの採点を実行
            Log::info('ゲスト試験完了 - 全パート採点開始', [
                'guest_id' => $guestId
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
                
                Log::info("第{$p}部採点完了", [
                    'correct' => $correct,
                    'incorrect' => $incorrect,
                    'unanswered' => $unanswered,
                    'score' => round($score, 2),
                ]);
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
            
            // ★ 重要: Laravelセッションに結果を保存(Result.vueで使用)
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
            
            Log::info('ゲスト試験完了 - セッションに結果保存', [
                'guest_id' => $guestId,
                'guest_name' => $guestName,
                'guest_school' => $guestSchool,
                'total_score' => round($totalScore, 2),
                'rank' => $rankName,
                'results' => $results,
            ]);
            
            // セッション更新
            $examSession['finished_at'] = now();
            Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);
            
            // 試験終了時に全キャッシュを削除
            Cache::forget($cacheKey);
            
            Log::info('ゲスト試験完了 - 全キャッシュ削除', [
                'guest_id' => $guestId
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
            'session_data' => session()->all()
        ]);
            
        // ★ 重要: セッションから結果を取得(採点済み)
        $examResults = session('exam_results');
        
        if (!$examResults) {
            Log::error('セッションに結果データがありません', [
                'guest_id' => $guestId,
                'session_keys' => array_keys(session()->all())
            ]);
            
            return redirect()->route('guest.test.start')
                ->with('error', '試験結果が見つかりません。');
        }
        
        // ★ セッションから結果を直接使用（再計算しない）
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
            ]
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
            'guest_id' => $guestId
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
        
        if (!$session || ($session['violation_count'] ?? 0) < 3) {
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
            'violationCount' => 'required|integer'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }
        
        $guestId = session()->getId();
        $sessionId = $request->input('examSessionId');
        
        // セッション検証
        $cacheKey = "guest_exam_part_session_{$guestId}_{$sessionId}";
        $sessionData = Cache::get($cacheKey);
        if (!$sessionData) {
            return response()->json(['success' => false], 403);
        }
        
        // ゲストセッション取得
        $existingSessionKey = "guest_exam_session_{$guestId}";
        $examSession = Cache::get($existingSessionKey);
        if (!$examSession) {
            return response()->json(['success' => false], 403);
        }
        
        // 違反を記録
        $violationData = [
            'timestamp' => $request->timestamp,
            'violation_type' => $request->violationType,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'violation_count' => $request->violationCount
        ];
        
        $securityLog = $examSession['security_log'] ?? [];
        $securityLog[] = $violationData;
        
        $examSession['security_log'] = $securityLog;
        $examSession['violation_count'] = ($examSession['violation_count'] ?? 0) + 1;
        
        // ログに記録
        Log::warning('Guest exam violation detected', [
            'guest_id' => $guestId,
            'violation_type' => $request->violationType,
            'violation_count' => $examSession['violation_count']
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
            'disqualified' => $examSession['violation_count'] >= 3
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
                'ip' => $request->ip()
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
                'ip' => $request->ip()
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
            if (!is_numeric($questionId) || $questionId < 1) {
                continue;
            }
            
            // 回答は A-E のみ
            $cleanAnswer = strtoupper(trim($answer));
            if (in_array($cleanAnswer, $validChoices)) {
                $sanitized[(int)$questionId] = $cleanAnswer;
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
            'finished_at' => now()
        ]);

        // 管理者に通知
        Log::critical('Exam disqualification', [
            'user_id' => $examSession->user_id,
            'exam_session_id' => $examSession->id,
            'reason' => $reason,
            'violation_count' => ExamViolation::where('exam_session_id', $examSession->id)->count()
        ]);
    }
    
    /**
     * パート制限時間取得
     */
    private function getPartTimeLimit($part)
    {
        $timeLimits = [
            1 => 1800,  // 30分（1800秒）
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
     * 本番試験の説明画面表示
     */
    public function explanation(Request $request, $part = 1)
    {
        // パート番号のバリデーション
        if (!in_array($part, [1, 2, 3])) {
            $part = 1;
        }

        return Inertia::render('Explanation', [
            'nextPart' => (int)$part,
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
        if (!in_array($part, [1, 2, 3])) {
            $part = 1;
        }

        return Inertia::render('Explanation', [
            'nextPart' => (int)$part,
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

        // セッションに保存（複数の形式で保存）
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