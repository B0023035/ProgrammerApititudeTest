<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Answer;
use Inertia\Inertia;

class ResultsComlinkController extends Controller
{
    public function index()
    {
        // 完了したセッションを取得
        $sessions = ExamSession::with('user')
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->latest('finished_at')
            ->get()
            ->map(function ($session) {
                // answersテーブルから正答数を集計
                $correctCount = Answer::where('exam_session_id', $session->id)
                    ->where('is_correct', 1)
                    ->count();
                
                $totalQuestions = Answer::where('exam_session_id', $session->id)
                    ->count();

                // ランク判定
                $percentage = $totalQuestions > 0 ? ($correctCount / $totalQuestions) * 100 : 0;
                $rank = 'Unranked';
                if ($percentage >= 90) $rank = 'Platinum';
                elseif ($percentage >= 75) $rank = 'Gold';
                elseif ($percentage >= 60) $rank = 'Silver';
                elseif ($percentage >= 40) $rank = 'Bronze';

                return [
                    'id' => $session->id,
                    'user_id' => $session->user_id,
                    'session_uuid' => $session->session_uuid,
                    'total_score' => $correctCount,
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

        return Inertia::render('Admin/ResultsComlink', [
            'sessions' => $sessions,
        ]);
    }
}