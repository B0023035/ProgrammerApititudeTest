<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ExamSession;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResultsManagementController extends Controller
{
    /**
     * 成績一覧ダッシュボード（管理者用）
     */
    public function index(Request $request)
    {
        // 完了したセッションを取得し、正答数を集計
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
                $rank = $this->calculateRank($correctCount, $totalQuestions);

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

        // ページネーション用にコレクションを配列に変換
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $total = $sessions->count();
        $items = $sessions->forPage($currentPage, $perPage)->values();

        return Inertia::render('Admin/Results/Index', [
            'sessions' => [
                'data' => $items,
                'current_page' => $currentPage,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * セッション詳細
     */
    public function sessionDetail($sessionId)
    {
        $session = ExamSession::with('user')->findOrFail($sessionId);
        
        // answersテーブルから全回答を取得
        $answers = Answer::where('exam_session_id', $sessionId)
            ->orderBy('part')
            ->orderBy('question_id')
            ->get();

        // パート別スコア集計
        $partScores = [];
        for ($part = 1; $part <= 3; $part++) {
            $partAnswers = $answers->where('part', $part);
            $correct = $partAnswers->where('is_correct', 1)->count();
            $total = $partAnswers->count();

            $partScores[$part] = [
                'correct' => $correct,
                'total' => $total,
                'percentage' => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
            ];
        }

        // 総合スコア
        $totalCorrect = $answers->where('is_correct', 1)->count();
        $totalQuestions = $answers->count();
        $rank = $this->calculateRank($totalCorrect, $totalQuestions);

        return Inertia::render('Admin/Results/SessionDetail', [
            'session' => [
                'id' => $session->id,
                'user' => [
                    'id' => $session->user->id,
                    'name' => $session->user->name,
                    'email' => $session->user->email,
                ],
                'started_at' => $session->started_at->toIso8601String(),
                'finished_at' => $session->finished_at->toIso8601String(),
                'total_score' => $totalCorrect,
                'total_questions' => $totalQuestions,
                'rank' => $rank,
            ],
            'partScores' => $partScores,
            'answers' => $answers->map(function ($answer) {
                return [
                    'question_id' => $answer->question_id,
                    'part' => $answer->part,
                    'choice' => $answer->choice,
                    'is_correct' => (bool)$answer->is_correct,
                ];
            }),
        ]);
    }

    /**
     * ユーザー詳細
     */
    public function userDetail($userId)
    {
        $user = User::findOrFail($userId);
        
        // ユーザーの全セッションを取得
        $sessions = ExamSession::where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->orderBy('finished_at', 'desc')
            ->get()
            ->map(function ($session) {
                $correctCount = Answer::where('exam_session_id', $session->id)
                    ->where('is_correct', 1)
                    ->count();
                
                $totalQuestions = Answer::where('exam_session_id', $session->id)
                    ->count();

                // パート別スコア
                $partScores = [];
                for ($part = 1; $part <= 3; $part++) {
                    $partCorrect = Answer::where('exam_session_id', $session->id)
                        ->where('part', $part)
                        ->where('is_correct', 1)
                        ->count();
                    
                    $partTotal = Answer::where('exam_session_id', $session->id)
                        ->where('part', $part)
                        ->count();

                    $partScores[$part] = [
                        'correct' => $partCorrect,
                        'total' => $partTotal,
                        'percentage' => $partTotal > 0 ? round(($partCorrect / $partTotal) * 100, 1) : 0,
                    ];
                }

                return [
                    'id' => $session->id,
                    'total_score' => $correctCount,
                    'total_questions' => $totalQuestions,
                    'rank' => $this->calculateRank($correctCount, $totalQuestions),
                    'finished_at' => $session->finished_at->toIso8601String(),
                    'part_scores' => $partScores,
                ];
            });

        // 平均スコア計算
        $averageScore = $sessions->avg('total_score');
        $bestScore = $sessions->max('total_score');

        return Inertia::render('Admin/Results/UserDetail', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'sessions' => $sessions,
            'statistics' => [
                'total_attempts' => $sessions->count(),
                'average_score' => round($averageScore, 1),
                'best_score' => $bestScore,
            ],
        ]);
    }

    /**
     * 統計ページ
     */
    public function statistics()
    {
        // 日別受験者数
        $dailyStats = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->select(DB::raw('DATE(finished_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        // スコア分布
        $sessions = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->get();

        $scoreDistribution = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]; // 0-9%, 10-19%, ..., 90-100%

        foreach ($sessions as $session) {
            $correct = Answer::where('exam_session_id', $session->id)
                ->where('is_correct', 1)
                ->count();
            
            $total = Answer::where('exam_session_id', $session->id)
                ->count();

            if ($total > 0) {
                $percentage = ($correct / $total) * 100;
                $index = min(9, floor($percentage / 10));
                $scoreDistribution[$index]++;
            }
        }

        // パート別平均正答率
        $partAverages = [];
        for ($part = 1; $part <= 3; $part++) {
            $correctSum = DB::table('answers')
                ->join('exam_sessions', 'answers.exam_session_id', '=', 'exam_sessions.id')
                ->where('answers.part', $part)
                ->where('answers.is_correct', 1)
                ->whereNotNull('exam_sessions.finished_at')
                ->whereNull('exam_sessions.disqualified_at')
                ->count();

            $totalSum = DB::table('answers')
                ->join('exam_sessions', 'answers.exam_session_id', '=', 'exam_sessions.id')
                ->where('answers.part', $part)
                ->whereNotNull('exam_sessions.finished_at')
                ->whereNull('exam_sessions.disqualified_at')
                ->count();

            $partAverages[$part] = $totalSum > 0 ? round(($correctSum / $totalSum) * 100, 1) : 0;
        }

        return Inertia::render('Admin/Results/Statistics', [
            'dailyStats' => $dailyStats,
            'scoreDistribution' => $scoreDistribution,
            'partAverages' => $partAverages,
        ]);
    }

    /**
     * ランク計算
     */
    private function calculateRank($correctCount, $totalQuestions)
    {
        if ($totalQuestions === 0) {
            return 'Unranked';
        }

        $percentage = ($correctCount / $totalQuestions) * 100;

        if ($percentage >= 90) return 'Platinum';
        if ($percentage >= 75) return 'Gold';
        if ($percentage >= 60) return 'Silver';
        if ($percentage >= 40) return 'Bronze';
        return 'Unranked';
    }
}