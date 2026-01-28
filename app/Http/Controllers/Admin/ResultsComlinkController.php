<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Event;
use App\Models\ExamSession;
use App\Models\Question;
use Inertia\Inertia;

class ResultsComlinkController extends Controller
{
    /**
     * セッションの実際の問題数を取得（security_logから）
     */
    private function getSessionQuestionCount($session)
    {
        if (!$session) {
            return 95; // デフォルト
        }
        
        // security_logからquestion_idsを取得
        $securityLog = $session->security_log ?? [];
        if (isset($securityLog['question_ids']) && is_array($securityLog['question_ids'])) {
            $questionIds = $securityLog['question_ids'];
            return count($questionIds['1'] ?? []) + count($questionIds['2'] ?? []) + count($questionIds['3'] ?? []);
        }
        
        // イベントから取得
        if ($session->event) {
            $event = $session->event;
            $mode = $event->question_selection_mode ?? 'sequential';
            
            // パート別問題数が設定されている場合
            if ($event->part1_questions !== null || $event->part2_questions !== null || $event->part3_questions !== null) {
                $part1 = $event->part1_questions ?? 40;
                $part2 = $event->part2_questions ?? 30;
                $part3 = $event->part3_questions ?? 25;
                return $part1 + $part2 + $part3;
            }
            
            // customモード、または問題数が未設定の場合は実際の回答数をカウント
            if ($mode === 'custom' || $mode === 'random') {
                $answerCount = Answer::where('exam_session_id', $session->id)->count();
                if ($answerCount > 0) {
                    return $answerCount;
                }
            }
        }
        
        // 最終手段: 実際に回答された問題数をカウント
        $answerCount = Answer::where('exam_session_id', $session->id)->count();
        if ($answerCount > 0) {
            return $answerCount;
        }
        
        return 95; // デフォルト
    }

    /**
     * ランク計算（問題数に応じてスケーリング）
     * 95問基準: Platinum≥61, Gold≥51, Silver≥36, Bronze<36
     */
    private function calculateRank($score, $actualQuestionCount = 95)
    {
        $baseQuestions = 95;
        $scaleFactor = $actualQuestionCount / $baseQuestions;
        
        $platinumThreshold = 61 * $scaleFactor;
        $goldThreshold = 51 * $scaleFactor;
        $silverThreshold = 36 * $scaleFactor;
        
        if ($score >= $platinumThreshold) {
            return 'Platinum';
        }
        if ($score >= $goldThreshold) {
            return 'Gold';
        }
        if ($score >= $silverThreshold) {
            return 'Silver';
        }

        return 'Bronze';
    }

    public function index()
    {
        // 完了したセッションを取得（イベント情報も含む）
        $sessions = ExamSession::with(['user', 'event'])
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->latest('finished_at')
            ->get()
            ->map(function ($session) {
                // ResultsManagementController と同じ採点ルールを再現:
                // 正答: +1, 未回答: 0, 誤答: -0.25
                $answers = Answer::where('exam_session_id', $session->id)->get();

                $score = 0;
                foreach ($answers as $answer) {
                    if ($answer->choice === null) {
                        // 未回答: 0 点
                        continue;
                    } elseif ($answer->is_correct) {
                        // 正答: +1 点
                        $score += 1;
                    } else {
                        // 誤答: -0.25 点
                        $score -= 0.25;
                    }
                }

                // セッションの実際の問題数を取得
                $totalQuestions = $this->getSessionQuestionCount($session);
                
                // 問題数に応じたスケーリングでランク計算
                $rank = $this->calculateRank($score, $totalQuestions);

                return [
                    'id' => $session->id,
                    'user_id' => $session->user_id,
                    'session_uuid' => $session->session_uuid,
                    'total_score' => round($score, 2),
                    'total_questions' => $totalQuestions,
                    'rank' => $rank,
                    'finished_at' => $session->finished_at->toIso8601String(),
                    'user' => [
                        'id' => $session->user->id,
                        'name' => $session->user->name,
                        'email' => $session->user->email,
                    ],
                    'event' => $session->event ? [
                        'id' => $session->event->id,
                        'name' => $session->event->name,
                    ] : null,
                ];
            });

        // イベント名のリストを取得
        $events = Event::orderBy('begin', 'desc')->pluck('name')->toArray();

        return Inertia::render('Admin/ResultsComlink', [
            'sessions' => $sessions,
            'events' => $events,
        ]);
    }

    /**
     * イベント別成績一覧
     */
    public function eventResults($eventId)
    {
        $event = Event::findOrFail($eventId);

        // ステータス判定
        $now = \Carbon\Carbon::now();
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

        // 指定イベントのセッションを取得
        $sessions = ExamSession::with(['user'])
            ->where('event_id', $eventId)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->latest('finished_at')
            ->get()
            ->map(function ($session) {
                // 採点
                $answers = Answer::where('exam_session_id', $session->id)->get();

                $score = 0;
                foreach ($answers as $answer) {
                    if ($answer->choice === null) {
                        continue;
                    } elseif ($answer->is_correct) {
                        $score += 1;
                    } else {
                        $score -= 0.25;
                    }
                }

                $totalQuestions = $this->getSessionQuestionCount($session);
                $rank = $this->calculateRank($score, $totalQuestions);

                return [
                    'id' => $session->id,
                    'user_id' => $session->user_id,
                    'session_uuid' => $session->session_uuid,
                    'total_score' => round($score, 2),
                    'total_questions' => $totalQuestions,
                    'rank' => $rank,
                    'finished_at' => $session->finished_at->toIso8601String(),
                    'user' => [
                        'id' => $session->user->id,
                        'name' => $session->user->name,
                        'email' => $session->user->email,
                    ],
                ];
            });

        return Inertia::render('Admin/Results/EventResults', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'passphrase' => $event->passphrase,
                'begin' => $event->begin->toIso8601String(),
                'end' => $event->end->toIso8601String(),
                'status' => $status,
                'status_color' => $statusColor,
            ],
            'sessions' => $sessions,
        ]);
    }
}
