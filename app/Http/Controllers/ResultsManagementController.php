<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ExamSession;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Choice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResultsManagementController extends Controller
{
    /**
     * 各パートの問題数を取得
     */
    private function getPartQuestionCounts()
    {
        return [
            1 => Question::where('part', 1)->count(),
            2 => Question::where('part', 2)->count(),
            3 => Question::where('part', 3)->count(),
        ];
    }

    /**
     * スコア計算（正答: +1点、誤答: -0.25点、未回答: 0点）
     */
    private function calculateScore($examSessionId, $part = null)
    {
        $query = Answer::where('exam_session_id', $examSessionId);
        
        if ($part !== null) {
            $query->where('part', $part);
        }
        
        $answers = $query->get();
        
        $score = 0;
        foreach ($answers as $answer) {
            if ($answer->choice === null) {
                // 未回答: 0点
                continue;
            } elseif ($answer->is_correct) {
                // 正答: +1点
                $score += 1;
            } else {
                // 誤答: -0.25点
                $score -= 0.25;
            }
        }
        
        return $score;
    }

    /**
     * ランク計算（新基準）
     * ～35.75: D (Bronze)
     * 36～50.75: C (Silver)
     * 51～60.75: B (Gold)
     * 61～: A (Platinum)
     */
    private function calculateRank($score)
    {
        if ($score >= 61) return 'Platinum';
        if ($score >= 51) return 'Gold';
        if ($score >= 36) return 'Silver';
        return 'Bronze';
    }

    /**
     * 成績一覧ダッシュボード(管理者用)
     */
    public function index(Request $request)
    {
        $partQuestionCounts = $this->getPartQuestionCounts();
        $totalQuestions = array_sum($partQuestionCounts);

        $sessions = ExamSession::with('user')
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->latest('finished_at')
            ->get()
            ->map(function ($session) use ($totalQuestions) {
                $score = $this->calculateScore($session->id);
                $rank = $this->calculateRank($score);

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
        $partQuestionCounts = $this->getPartQuestionCounts();
        $totalQuestions = array_sum($partQuestionCounts);
        
        $answers = Answer::where('exam_session_id', $sessionId)
            ->with(['question', 'question.choices'])
            ->orderBy('part')
            ->orderBy('question_id')
            ->get();

        $totalScore = $this->calculateScore($sessionId);
        $rank = $this->calculateRank($totalScore);

        $answersByPart = [];

        foreach ([1, 2, 3] as $part) {
            $partAnswers = $answers->where('part', $part);
            $partScore = $this->calculateScore($sessionId, $part);
            $total = $partQuestionCounts[$part];
            
            // 正答数をカウント（表示用）
            $correct = $partAnswers->where('is_correct', 1)->count();
            
            $questions = [];
            foreach ($partAnswers as $answer) {
                $correctChoice = null;
                foreach ($answer->question->choices as $choice) {
                    if ($choice->is_correct) {
                        $correctChoice = $choice->label;
                        break;
                    }
                }

                $choicesArray = [];
                foreach ($answer->question->choices as $choice) {
                    $choicesArray[] = [
                        'label' => $choice->label,
                        'text' => $choice->text,
                        'image' => $choice->image,
                        'is_correct' => (bool)$choice->is_correct,
                    ];
                }

                // 各問題のスコアを計算
                $questionScore = 0;
                if ($answer->choice === null) {
                    $questionScore = 0;
                } elseif ($answer->is_correct) {
                    $questionScore = 1;
                } else {
                    $questionScore = -0.25;
                }

                $questions[] = [
                    'question_id' => $answer->question_id,
                    'question_number' => $answer->question->number,
                    'question_text' => $answer->question->text,
                    'question_image' => $answer->question->image,
                    'user_choice' => $answer->choice,
                    'correct_choice' => $correctChoice,
                    'is_correct' => (bool)$answer->is_correct,
                    'score' => $questionScore,
                    'choices' => $choicesArray,
                ];
            }
            
            $answersByPart[(string)$part] = [
                'score' => [
                    'correct' => $correct,
                    'total' => $total,
                    'points' => round($partScore, 2),
                    'percentage' => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
                ],
                'questions' => $questions,
            ];
        }

        return Inertia::render('Admin/Results/SessionDetail', [
            'session' => [
                'id' => $session->id,
                'session_uuid' => $session->session_uuid,
                'user' => [
                    'id' => $session->user->id,
                    'name' => $session->user->name,
                    'email' => $session->user->email,
                ],
                'started_at' => $session->started_at->toIso8601String(),
                'finished_at' => $session->finished_at->toIso8601String(),
                'total_score' => round($totalScore, 2),
                'total_questions' => $totalQuestions,
                'percentage' => $totalQuestions > 0 
                    ? round(($totalScore / $totalQuestions) * 100, 1) 
                    : 0,
                'rank' => $rank,
            ],
            'answersByPart' => $answersByPart,
        ]);
    }

    /**
     * ユーザー詳細
     */
    public function userDetail($userId)
    {
        $user = User::findOrFail($userId);
        $partQuestionCounts = $this->getPartQuestionCounts();
        $totalQuestions = array_sum($partQuestionCounts);
        
        $sessions = ExamSession::where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->orderBy('finished_at', 'desc')
            ->get()
            ->map(function ($session) use ($partQuestionCounts, $totalQuestions) {
                $totalScore = $this->calculateScore($session->id);

                $partScores = [];
                for ($part = 1; $part <= 3; $part++) {
                    $partScore = $this->calculateScore($session->id, $part);
                    
                    // 正答数（表示用）
                    $partCorrect = Answer::where('exam_session_id', $session->id)
                        ->where('part', $part)
                        ->where('is_correct', 1)
                        ->count();

                    $partScores[$part] = [
                        'correct' => $partCorrect,
                        'total' => $partQuestionCounts[$part],
                        'points' => round($partScore, 2),
                        'percentage' => $partQuestionCounts[$part] > 0 
                            ? round(($partCorrect / $partQuestionCounts[$part]) * 100, 1) 
                            : 0,
                    ];
                }

                return [
                    'id' => $session->id,
                    'session_uuid' => $session->session_uuid,
                    'total_score' => round($totalScore, 2),
                    'total_questions' => $totalQuestions,
                    'percentage' => $totalQuestions > 0 
                        ? round(($totalScore / $totalQuestions) * 100, 1) 
                        : 0,
                    'rank' => $this->calculateRank($totalScore),
                    'finished_at' => $session->finished_at->toIso8601String(),
                    'part1_score' => $partScores[1]['points'],
                    'part2_score' => $partScores[2]['points'],
                    'part3_score' => $partScores[3]['points'],
                ];
            });

        return Inertia::render('Admin/Results/UserDetail', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'sessions' => $sessions,
        ]);
    }

    /**
     * 学年別一覧
     */
    public function gradeList(Request $request)
    {
        $users = User::with(['examSessions' => function ($query) {
            $query->whereNotNull('finished_at')
                  ->whereNull('disqualified_at');
        }])->get();

        $usersByGrade = $users->groupBy(function ($user) {
            return $user->grade ?? '未設定';
        })->map(function ($gradeUsers) {
            return $gradeUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'grade' => $user->grade ?? '未設定',
                    'exam_sessions' => $user->examSessions->map(function ($session) {
                        $score = $this->calculateScore($session->id);
                        return [
                            'total_score' => round($score, 2),
                        ];
                    }),
                ];
            });
        });

        return Inertia::render('Admin/Results/GradeList', [
            'usersByGrade' => $usersByGrade,
        ]);
    }

    /**
     * 統計ページ
     */
    public function statistics()
    {
        $totalSessions = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->count();
        
        $totalUsers = User::count();
        
        $sessions = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->get();
        
        $totalScore = 0;
        
        foreach ($sessions as $session) {
            $score = $this->calculateScore($session->id);
            $totalScore += $score;
        }
        
        $averageScore = $totalSessions > 0 
            ? round($totalScore / $totalSessions, 2) 
            : 0;

        return Inertia::render('Admin/Results/Statistics', [
            'stats' => [
                'total_sessions' => $totalSessions,
                'total_users' => $totalUsers,
                'average_score' => $averageScore,
            ],
        ]);
    }

    /**
 * COM連携ページ（Comlink）
 */
public function comlink()
{
    $partQuestionCounts = $this->getPartQuestionCounts();
    $totalQuestions = array_sum($partQuestionCounts);
    
    $sessions = ExamSession::with('user')
        ->whereNotNull('finished_at')
        ->whereNull('disqualified_at')
        ->latest('finished_at')
        ->get()
        ->map(function ($session) use ($totalQuestions) {
            $totalScore = $this->calculateScore($session->id);
            $rank = $this->calculateRank($totalScore);

            return [
                'id' => $session->id,
                'user_id' => $session->user_id,
                'session_uuid' => $session->session_uuid,
                'total_score' => round($totalScore, 2),
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