<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ExamSession;
use App\Models\ExamViolation;
use App\Models\Question;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExamService
{
    /**
     * ユーザーのすべてのキャッシュを削除
     */
    public function cleanupAllUserCache($userId): void
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
    public function determineQuestionIds($event, $selectionMode, $examType): array
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
     * キャッシュクリーンアップ用のヘルパーメソッド
     */
    public function cleanupExamCache($userId, $examSessionId): void
    {
        // 全パートの解答キャッシュを削除
        for ($part = 1; $part <= 3; $part++) {
            Cache::forget("exam_answers_{$userId}_{$part}");
        }
    }

    /**
     * ゲストユーザー用キャッシュクリーンアップ
     */
    public function cleanupGuestExamCache($guestId, $sessionId): void
    {
        // 全パートの解答キャッシュを削除
        for ($part = 1; $part <= 3; $part++) {
            Cache::forget("guest_exam_answers_{$guestId}_part_{$part}");
            Cache::forget("guest_exam_result_{$guestId}_part_{$part}");
        }
    }

    /**
     * 回答データのサニタイズ
     */
    public function sanitizeAnswers(array $answers): array
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
                $sanitized[(int) $questionId] = $cleanAnswer;
            }
        }

        return $sanitized;
    }

    /**
     * セッション失格処理
     */
    public function disqualifySession($examSession, $reason = 'Security violation', $securityLog = []): void
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
     * パート制限時間取得（基本）
     */
    public function getPartTimeLimit($part): int
    {
        $timeLimits = [
            1 => 1800,  // 30分(1800秒)
            2 => 1800,  // 30分
            3 => 1800,  // 30分
        ];

        return $timeLimits[$part] ?? 1800;
    }

    /**
     * イベント情報からパート時間制限を取得
     */
    public function getPartTimeLimitByEvent($part, $examType = 'full', $event = null): int
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
        $fallbackTimes = match ($examType) {
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
    public function getQuestionCountByEvent($part, $examType = 'full', $event = null): int
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
        $fallbackQuestions = match ($examType) {
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
    public function getEventBySessionCode($sessionCode): ?Event
    {
        if (!$sessionCode) {
            return null;
        }

        $event = Event::where('passphrase', $sessionCode)
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
     * 全パートの解答を収集
     */
    public function collectAllAnswers(array $securityLog): array
    {
        $allAnswers = [];
        for ($p = 1; $p <= 3; $p++) {
            if (isset($securityLog['part_' . $p . '_answers'])) {
                $allAnswers = $allAnswers + $securityLog['part_' . $p . '_answers'];
            }
        }
        return $allAnswers;
    }

    /**
     * 採点データを準備
     */
    public function prepareAnswersForInsert(array $allAnswers, $user, $examSession): array
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
     * ランク判定
     */
    public function calculateRank(float $totalScore, int $maxTotalScore): array
    {
        // ランク判定（95問満点の基準を問題数に応じてスケーリング）
        $baseMax = 95;
        $scaleFactor = $maxTotalScore / $baseMax;

        $platinumThreshold = 61 * $scaleFactor;
        $goldThreshold = 51 * $scaleFactor;
        $silverThreshold = 36 * $scaleFactor;

        if ($totalScore >= $platinumThreshold) {
            return ['rank' => 'A', 'rankName' => 'Platinum'];
        } elseif ($totalScore >= $goldThreshold) {
            return ['rank' => 'B', 'rankName' => 'Gold'];
        } elseif ($totalScore >= $silverThreshold) {
            return ['rank' => 'C', 'rankName' => 'Silver'];
        } else {
            return ['rank' => 'D', 'rankName' => 'Bronze'];
        }
    }

    /**
     * 問題をフォーマット
     */
    public function formatQuestions($questions, array $savedAnswers = [], &$displayNumber = 0): array
    {
        return $questions->map(function ($q) use ($savedAnswers, &$displayNumber) {
            $displayNumber++;
            return [
                'id' => $q->id,
                'number' => $displayNumber,
                'original_number' => $q->number,
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
        })->toArray();
    }
}
