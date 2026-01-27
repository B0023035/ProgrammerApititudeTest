<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 試験の解答保存に関するコントローラー
 */
class ExamAnswerController extends Controller
{
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
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

        // 現在の回答を一時保存(JSON形式)
        $answers = $this->examService->sanitizeAnswers($request->input('answers', []));
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

            // ゲストは処理しない
            if (!$user) {
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

            if (!$examSession) {
                return response()->json([
                    'success' => false,
                    'message' => '無効な試験セッションです。',
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

                // プライマリキーで直接取得し、排他ロック
                $examSession = ExamSession::lockForUpdate()
                    ->find($cacheSession['exam_session_id']);

                // 取得後に条件チェック
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

                if (!isset($securityLog['part_' . $validated['part'] . '_answers'])) {
                    $securityLog['part_' . $validated['part'] . '_answers'] = [];
                }

                // 複数回答を一括更新
                foreach ($validated['answers'] as $questionId => $choice) {
                    if (!is_numeric($questionId)) {
                        continue;
                    }
                    $securityLog['part_' . $validated['part'] . '_answers'][$questionId] = $choice;
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

                // デッドロック検出とリトライ
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

                    // 指数バックオフで待機
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
}
