<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * 試験結果の表示に関するコントローラー
 */
class ExamResultController extends Controller
{
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
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
        $maxScores = [];

        for ($part = 1; $part <= 3; $part++) {
            $answers = Answer::where('user_id', $user->id)
                ->where('exam_session_id', $session->id)
                ->where('part', $part)
                ->get();

            // 実際に出題された問題数を取得
            if ($questionIds && isset($questionIds["part_{$part}"]) && count($questionIds["part_{$part}"]) > 0) {
                $totalQuestions = count($questionIds["part_{$part}"]);
            } else {
                $totalQuestions = $this->examService->getQuestionCountByEvent($part, $examType, $event);
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

            $maxScores[$part] = $totalQuestions;
        }

        // 総合スコア
        $totalScore = $results[1]['score'] + $results[2]['score'] + $results[3]['score'];
        $maxTotalScore = $maxScores[1] + $maxScores[2] + $maxScores[3];

        // ランク判定
        $rankInfo = $this->examService->calculateRank($totalScore, $maxTotalScore);

        // セッションに保存
        session([
            'exam_results' => [
                'results' => $results,
                'rankName' => $rankInfo['rankName'],
                'totalScore' => round($totalScore, 2),
                'rank' => $rankInfo['rank'],
            ],
            'isGuest' => false,
        ]);

        return Inertia::render('Result', [
            'results' => $results,
            'totalScore' => round($totalScore, 2),
            'rank' => $rankInfo['rank'],
            'rankName' => $rankInfo['rankName'],
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
                $totalQuestions = $this->examService->getQuestionCountByEvent($part, $examType, $event);
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
        $rankInfo = $this->examService->calculateRank($totalScore, $maxTotalScore);

        return Inertia::render('Certificate', [
            'results' => $results,
            'totalScore' => round($totalScore, 2),
            'rank' => $rankInfo['rank'],
            'rankName' => $rankInfo['rankName'],
            'userName' => $user->name,
            'schoolName' => 'YIC情報ビジネス専門学校',
            'finishedAt' => $session->finished_at,
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
                    $totalQuestions = $this->examService->getQuestionCountByEvent($part, $examType, $event);
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
            $rankInfo = $this->examService->calculateRank($totalScore, $maxTotalScore);

            $results[] = [
                'id' => $session->id,
                'session_uuid' => $session->session_uuid,
                'event_name' => $eventName,
                'finished_at' => $session->finished_at->toIso8601String(),
                'total_score' => round($totalScore, 2),
                'max_score' => $maxTotalScore,
                'rank' => $rankInfo['rank'],
                'rank_name' => $rankInfo['rankName'],
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
                $partQuestionCount = $this->examService->getQuestionCountByEvent($part, $examType, $event);
            }

            $correct = $answers->where('is_correct', 1)->count();
            $incorrect = $answers->where('is_correct', 0)->count();
            $percentage = $partQuestionCount > 0 ? round(($correct / $partQuestionCount) * 100, 1) : 0;

            $answersByPart[(string) $part] = [
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
        $rankInfo = $this->examService->calculateRank($score, $totalQuestions);
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
                'rank' => $rankInfo['rankName'],
                'event' => $event ? [
                    'id' => $event->id,
                    'name' => $event->name,
                ] : null,
            ],
            'answersByPart' => $answersByPart,
        ]);
    }
}
