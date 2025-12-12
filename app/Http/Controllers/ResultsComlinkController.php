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

                $totalQuestions = Question::count();

                // ResultsManagementController と同じランク判定基準を使用
                $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
                $rank = 'Bronze';
                if ($score >= 61) {
                    $rank = 'Platinum';
                } elseif ($score >= 51) {
                    $rank = 'Gold';
                } elseif ($score >= 36) {
                    $rank = 'Silver';
                } else {
                    $rank = 'Bronze';
                }

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
}
