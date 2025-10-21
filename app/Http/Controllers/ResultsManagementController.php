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
     * 成績一覧ダッシュボード(管理者用)
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
        
        // デバッグログ
        \Log::info('SessionDetail called for session: ' . $sessionId);
        
        // answersテーブルから全回答を取得（questionとchoicesもロード）
        $answers = Answer::where('exam_session_id', $sessionId)
            ->with(['question', 'question.choices'])
            ->orderBy('part')
            ->orderBy('question_id')
            ->get();

        \Log::info('Total answers found: ' . $answers->count());

        // 総合スコア
        $totalCorrect = $answers->where('is_correct', 1)->count();
        $totalQuestions = $answers->count();
        $rank = $this->calculateRank($totalCorrect, $totalQuestions);

        // パート別にグループ化して詳細情報を構築
        $answersByPart = [];

        foreach ([1, 2, 3] as $part) {
            $partAnswers = $answers->where('part', $part);
            
            $correct = $partAnswers->where('is_correct', 1)->count();
            $total = $partAnswers->count();
            
            $questions = [];
            foreach ($partAnswers as $answer) {
                // 正解の選択肢を取得
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

                $questions[] = [
                    'question_id' => $answer->question_id,
                    'question_number' => $answer->question->number,
                    'question_text' => $answer->question->text,
                    'question_image' => $answer->question->image,
                    'user_choice' => $answer->choice,
                    'correct_choice' => $correctChoice,
                    'is_correct' => (bool)$answer->is_correct,
                    'choices' => $choicesArray,
                ];
            }
            
            $answersByPart[(string)$part] = [
                'score' => [
                    'correct' => $correct,
                    'total' => $total,
                    'percentage' => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
                ],
                'questions' => $questions,
            ];
        }

        \Log::info('AnswersByPart structure: ' . json_encode(array_keys($answersByPart)));

        $responseData = [
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
                'total_score' => $totalCorrect,
                'total_questions' => $totalQuestions,
                'percentage' => $totalQuestions > 0 
                    ? round(($totalCorrect / $totalQuestions) * 100, 1) 
                    : 0,
                'rank' => $rank,
            ],
            'answersByPart' => $answersByPart,
        ];

        \Log::info('Response data keys: ' . implode(', ', array_keys($responseData)));
        \Log::info('AnswersByPart exists: ' . (isset($responseData['answersByPart']) ? 'yes' : 'no'));

        return Inertia::render('Admin/Results/SessionDetail', $responseData);
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
                    'session_uuid' => $session->session_uuid,
                    'total_score' => $correctCount,
                    'total_questions' => $totalQuestions,
                    'percentage' => $totalQuestions > 0 
                        ? round(($correctCount / $totalQuestions) * 100, 1) 
                        : 0,
                    'rank' => $this->calculateRank($correctCount, $totalQuestions),
                    'finished_at' => $session->finished_at->toIso8601String(),
                    'part_scores' => $partScores,
                ];
            });

        // 平均スコア計算
        $averageScore = $sessions->avg('total_score');
        $bestScore = $sessions->max('total_score');
        $averagePercentage = $sessions->avg('percentage');

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
                'average_percentage' => round($averagePercentage, 1),
            ],
        ]);
    }

    /**
     * ランク別一覧
     */
    public function gradeList(Request $request)
    {
        $rankFilter = $request->get('rank');

        // 完了したセッションを取得
        $sessions = ExamSession::with('user')
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->latest('finished_at')
            ->get()
            ->map(function ($session) {
                $correctCount = Answer::where('exam_session_id', $session->id)
                    ->where('is_correct', 1)
                    ->count();
                
                $totalQuestions = Answer::where('exam_session_id', $session->id)
                    ->count();

                $rank = $this->calculateRank($correctCount, $totalQuestions);

                return [
                    'id' => $session->id,
                    'user_id' => $session->user_id,
                    'session_uuid' => $session->session_uuid,
                    'total_score' => $correctCount,
                    'total_questions' => $totalQuestions,
                    'percentage' => $totalQuestions > 0 
                        ? round(($correctCount / $totalQuestions) * 100, 1) 
                        : 0,
                    'rank' => $rank,
                    'finished_at' => $session->finished_at->toIso8601String(),
                    'user' => [
                        'id' => $session->user->id,
                        'name' => $session->user->name,
                        'email' => $session->user->email,
                    ],
                ];
            });

        // ランクでフィルタリング
        if ($rankFilter && $rankFilter !== 'all') {
            $sessions = $sessions->filter(function ($session) use ($rankFilter) {
                return $session['rank'] === $rankFilter;
            });
        }

        // ランク別の統計
        $rankCounts = [
            'Platinum' => 0,
            'Gold' => 0,
            'Silver' => 0,
            'Bronze' => 0,
            'Unranked' => 0,
        ];

        foreach ($sessions as $session) {
            $rankCounts[$session['rank']]++;
        }

        return Inertia::render('Admin/Results/GradeList', [
            'sessions' => $sessions->values(),
            'rankCounts' => $rankCounts,
            'currentRank' => $rankFilter ?? 'all',
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

        // ランク別統計
        $rankCounts = [
            'Platinum' => 0,
            'Gold' => 0,
            'Silver' => 0,
            'Bronze' => 0,
            'Unranked' => 0,
        ];

        foreach ($sessions as $session) {
            $correct = Answer::where('exam_session_id', $session->id)
                ->where('is_correct', 1)
                ->count();
            
            $total = Answer::where('exam_session_id', $session->id)
                ->count();

            $rank = $this->calculateRank($correct, $total);
            $rankCounts[$rank]++;
        }

        return Inertia::render('Admin/Results/Statistics', [
            'dailyStats' => $dailyStats,
            'scoreDistribution' => $scoreDistribution,
            'partAverages' => $partAverages,
            'rankCounts' => $rankCounts,
            'totalSessions' => $sessions->count(),
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