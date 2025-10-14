<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ExamSession;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultsManagementController extends Controller
{
    /**
     * 成績一覧ダッシュボード
     */
    public function index(Request $request)
    {
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $grade = $request->get('grade');
        $search = $request->get('search');

        // 基本クエリ
        $query = ExamSession::with('user')
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at');

        // 学年フィルター（メールアドレスから推測）
        if ($grade) {
            $query->whereHas('user', function($q) use ($grade) {
                $q->where('email', 'like', "B00{$grade}%");
            });
        }

        // 検索フィルター
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // ソート
        if ($sortBy === 'score') {
            $query->orderByRaw('(
                SELECT COUNT(*) 
                FROM answers 
                WHERE answers.exam_session_id = exam_sessions.id 
                AND answers.is_correct = 1
            ) ' . $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $sessions = $query->paginate(20);

        // 各セッションのスコアを計算
        foreach ($sessions as $session) {
            $session->score = $this->calculateScore($session->id);
            $session->total_questions = 95; // Part1(40) + Part2(30) + Part3(25)
        }

        // 統計情報
        $stats = $this->getOverallStats();

        return view('results.index', compact('sessions', 'stats', 'sortBy', 'sortOrder', 'grade', 'search'));
    }

    /**
     * 個人成績詳細
     */
    public function userDetail($userId)
    {
        $user = User::findOrFail($userId);
        
        // 全セッション取得
        $sessions = ExamSession::where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->orderBy('finished_at', 'desc')
            ->get();

        // 各セッションの詳細スコア
        $sessionDetails = [];
        foreach ($sessions as $session) {
            $sessionDetails[] = [
                'session' => $session,
                'scores' => $this->getDetailedScore($session->id),
                'time_taken' => $this->calculateTimeTaken($session)
            ];
        }

        // パート別平均
        $partAverages = $this->getUserPartAverages($userId);

        // 成長グラフデータ
        $growthData = $this->getUserGrowthData($userId);

        return view('results.user-detail', compact('user', 'sessionDetails', 'partAverages', 'growthData'));
    }

    /**
     * セッション詳細
     */
    public function sessionDetail($sessionId)
    {
        $session = ExamSession::with('user')->findOrFail($sessionId);
        
        // 全回答取得
        $answers = Answer::where('exam_session_id', $sessionId)
            ->with(['question', 'question.choices'])
            ->orderBy('part')
            ->orderBy('question_id')
            ->get();

        // パート別に整理
        $answersByPart = $answers->groupBy('part');

        // パート別スコア
        $partScores = [];
        foreach ($answersByPart as $part => $partAnswers) {
            $correct = $partAnswers->where('is_correct', 1)->count();
            $total = $partAnswers->count();
            $partScores[$part] = [
                'correct' => $correct,
                'total' => $total,
                'percentage' => $total > 0 ? round(($correct / $total) * 100, 1) : 0
            ];
        }

        return view('results.session-detail', compact('session', 'answersByPart', 'partScores'));
    }

    /**
     * 学年別一覧
     */
    public function gradeList(Request $request)
    {
        $grade = $request->get('grade', '23');
        
        // 学年のユーザー取得
        $users = User::where('email', 'like', "B00{$grade}%")
            ->orderBy('email')
            ->get();

        $userStats = [];
        foreach ($users as $user) {
            $sessions = ExamSession::where('user_id', $user->id)
                ->whereNotNull('finished_at')
                ->whereNull('disqualified_at')
                ->get();

            if ($sessions->count() > 0) {
                $totalScore = 0;
                $bestScore = 0;
                foreach ($sessions as $session) {
                    $score = $this->calculateScore($session->id);
                    $totalScore += $score;
                    $bestScore = max($bestScore, $score);
                }

                $userStats[] = [
                    'user' => $user,
                    'attempts' => $sessions->count(),
                    'average_score' => round($totalScore / $sessions->count(), 1),
                    'best_score' => $bestScore,
                    'latest_date' => $sessions->max('finished_at')
                ];
            }
        }

        // 平均スコアでソート
        usort($userStats, function($a, $b) {
            return $b['average_score'] <=> $a['average_score'];
        });

        return view('results.grade-list', compact('userStats', 'grade'));
    }

    /**
     * 全体統計グラフ
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
        $scoreDistribution = $this->getScoreDistribution();

        // パート別平均正答率
        $partAverages = $this->getOverallPartAverages();

        // 時間帯別受験数
        $hourlyStats = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->select(DB::raw('HOUR(started_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return view('results.statistics', compact('dailyStats', 'scoreDistribution', 'partAverages', 'hourlyStats'));
    }

    /**
     * ヘルパーメソッド: スコア計算
     */
    private function calculateScore($sessionId)
    {
        return Answer::where('exam_session_id', $sessionId)
            ->where('is_correct', 1)
            ->count();
    }

    /**
     * ヘルパーメソッド: 詳細スコア取得
     */
    private function getDetailedScore($sessionId)
    {
        $scores = [];
        for ($part = 1; $part <= 3; $part++) {
            $correct = Answer::where('exam_session_id', $sessionId)
                ->where('part', $part)
                ->where('is_correct', 1)
                ->count();
            
            $total = Answer::where('exam_session_id', $sessionId)
                ->where('part', $part)
                ->count();

            $scores[$part] = [
                'correct' => $correct,
                'total' => $total,
                'percentage' => $total > 0 ? round(($correct / $total) * 100, 1) : 0
            ];
        }
        return $scores;
    }

    /**
     * ヘルパーメソッド: 経過時間計算
     */
    private function calculateTimeTaken($session)
    {
        if ($session->started_at && $session->finished_at) {
            $start = \Carbon\Carbon::parse($session->started_at);
            $end = \Carbon\Carbon::parse($session->finished_at);
            return $start->diffInMinutes($end);
        }
        return null;
    }

    /**
     * ヘルパーメソッド: ユーザーパート別平均
     */
    private function getUserPartAverages($userId)
    {
        $averages = [];
        for ($part = 1; $part <= 3; $part++) {
            $correct = Answer::whereHas('examSession', function($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->whereNotNull('finished_at')
                      ->whereNull('disqualified_at');
                })
                ->where('part', $part)
                ->where('is_correct', 1)
                ->count();

            $total = Answer::whereHas('examSession', function($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->whereNotNull('finished_at')
                      ->whereNull('disqualified_at');
                })
                ->where('part', $part)
                ->count();

            $averages[$part] = $total > 0 ? round(($correct / $total) * 100, 1) : 0;
        }
        return $averages;
    }

    /**
     * ヘルパーメソッド: ユーザー成長データ
     */
    private function getUserGrowthData($userId)
    {
        $sessions = ExamSession::where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->orderBy('finished_at')
            ->get();

        $data = [];
        foreach ($sessions as $session) {
            $data[] = [
                'date' => $session->finished_at->format('Y-m-d H:i'),
                'score' => $this->calculateScore($session->id),
                'percentage' => round(($this->calculateScore($session->id) / 95) * 100, 1)
            ];
        }
        return $data;
    }

    /**
     * ヘルパーメソッド: 全体統計
     */
    private function getOverallStats()
    {
        $completedSessions = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->get();

        $totalScore = 0;
        $scores = [];
        foreach ($completedSessions as $session) {
            $score = $this->calculateScore($session->id);
            $totalScore += $score;
            $scores[] = $score;
        }

        return [
            'total_attempts' => $completedSessions->count(),
            'total_users' => User::count(),
            'average_score' => $completedSessions->count() > 0 ? round($totalScore / $completedSessions->count(), 1) : 0,
            'highest_score' => count($scores) > 0 ? max($scores) : 0,
            'lowest_score' => count($scores) > 0 ? min($scores) : 0
        ];
    }

    /**
     * ヘルパーメソッド: スコア分布
     */
    private function getScoreDistribution()
    {
        $sessions = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->get();

        $distribution = array_fill(0, 10, 0); // 0-9, 10-19, ..., 90-95

        foreach ($sessions as $session) {
            $score = $this->calculateScore($session->id);
            $percentage = round(($score / 95) * 100);
            $index = min(9, floor($percentage / 10));
            $distribution[$index]++;
        }

        return $distribution;
    }

    /**
     * ヘルパーメソッド: 全体パート別平均
     */
    private function getOverallPartAverages()
    {
        $averages = [];
        for ($part = 1; $part <= 3; $part++) {
            $correct = Answer::whereHas('examSession', function($q) {
                    $q->whereNotNull('finished_at')->whereNull('disqualified_at');
                })
                ->where('part', $part)
                ->where('is_correct', 1)
                ->count();

            $total = Answer::whereHas('examSession', function($q) {
                    $q->whereNotNull('finished_at')->whereNull('disqualified_at');
                })
                ->where('part', $part)
                ->count();

            $averages[$part] = $total > 0 ? round(($correct / $total) * 100, 1) : 0;
        }
        return $averages;
    }
}