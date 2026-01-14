<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Event;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     * スコア計算(正答: +1点、誤答: -0.25点、未回答: 0点)
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
     * ランク計算(新基準) - 問題数に応じてスケーリング
     * 95問基準: Platinum≥61, Gold≥51, Silver≥36, Bronze<36
     * スケーリング: 閾値 × (実際の問題数 / 95)
     */
    private function calculateRank($score, $actualQuestionCount = 95)
    {
        // 基準は95問でのスコア
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
     * セッションのパート別問題数を取得
     */
    private function getSessionPartQuestionCounts($session)
    {
        if (!$session) {
            return $this->getPartQuestionCounts(); // デフォルト
        }
        
        // security_logからquestion_idsを取得
        $securityLog = $session->security_log ?? [];
        if (isset($securityLog['question_ids']) && is_array($securityLog['question_ids'])) {
            $questionIds = $securityLog['question_ids'];
            return [
                1 => count($questionIds['1'] ?? []),
                2 => count($questionIds['2'] ?? []),
                3 => count($questionIds['3'] ?? []),
            ];
        }
        
        // イベントから取得
        if ($session->event) {
            $event = $session->event;
            $mode = $event->question_selection_mode ?? 'sequential';
            
            // パート別問題数が設定されている場合
            if ($event->part1_questions !== null || $event->part2_questions !== null || $event->part3_questions !== null) {
                return [
                    1 => $event->part1_questions ?? 40,
                    2 => $event->part2_questions ?? 30,
                    3 => $event->part3_questions ?? 25,
                ];
            }
            
            // customモード、または問題数が未設定の場合は実際の回答数をカウント
            if ($mode === 'custom' || $mode === 'random') {
                $counts = [1 => 0, 2 => 0, 3 => 0];
                $answers = Answer::where('exam_session_id', $session->id)->get();
                foreach ($answers as $answer) {
                    if (isset($counts[$answer->part])) {
                        $counts[$answer->part]++;
                    }
                }
                // 回答がある場合のみ返す
                if (array_sum($counts) > 0) {
                    return $counts;
                }
            }
        }
        
        // 最終手段: 実際に回答された問題数をパート別にカウント        // 最終手段: 実際に回答された問題数をパート別にカウント
        $answers = Answer::where('exam_session_id', $session->id)->get();
        if ($answers->count() > 0) {
            $counts = [1 => 0, 2 => 0, 3 => 0];
            foreach ($answers as $answer) {
                if (isset($counts[$answer->part])) {
                    $counts[$answer->part]++;
                }
            }
            return $counts;
        }
        
        return $this->getPartQuestionCounts();
    }

    /**
     * 成績一覧ダッシュボード(管理者用)
     */
    public function index(Request $request)
    {
        $sessions = ExamSession::with(['user', 'event'])
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->latest('finished_at')
            ->get()
            ->map(function ($session) {
                $score = $this->calculateScore($session->id);
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
        $session = ExamSession::with(['user', 'event'])->findOrFail($sessionId);
        $partQuestionCounts = $this->getSessionPartQuestionCounts($session);
        $totalQuestions = $this->getSessionQuestionCount($session);

        $answers = Answer::where('exam_session_id', $sessionId)
            ->with(['question', 'question.choices'])
            ->orderBy('part')
            ->orderBy('question_id')
            ->get();

        $totalScore = $this->calculateScore($sessionId);
        $rank = $this->calculateRank($totalScore, $totalQuestions);

        $answersByPart = [];

        foreach ([1, 2, 3] as $part) {
            $partAnswers = $answers->where('part', $part);
            $partScore = $this->calculateScore($sessionId, $part);
            $total = $partQuestionCounts[$part];

            // 正答数をカウント(表示用)
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
                        'is_correct' => (bool) $choice->is_correct,
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
                    'is_correct' => (bool) $answer->is_correct,
                    'score' => $questionScore,
                    'choices' => $choicesArray,
                ];
            }

            $answersByPart[(string) $part] = [
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
                'event' => $session->event ? [
                    'id' => $session->event->id,
                    'name' => $session->event->name,
                    'passphrase' => $session->event->passphrase,
                ] : null,
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

        $sessions = ExamSession::with('event')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->orderBy('finished_at', 'desc')
            ->get()
            ->map(function ($session) {
                $totalScore = $this->calculateScore($session->id);
                $partQuestionCounts = $this->getSessionPartQuestionCounts($session);
                $totalQuestions = $this->getSessionQuestionCount($session);

                $partScores = [];
                for ($part = 1; $part <= 3; $part++) {
                    $partScore = $this->calculateScore($session->id, $part);

                    // 正答数(表示用)
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
                    'rank' => $this->calculateRank($totalScore, $totalQuestions),
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
     * 学年別一覧（卒業年度別）
     */
    public function gradeList(Request $request)
    {
        $users = User::with(['examSessions' => function ($query) {
            $query->whereNotNull('finished_at')
                ->whereNull('disqualified_at');
        }])->get();

        // 卒業年度でグルーピング
        $usersByGrade = $users->groupBy(function ($user) {
            return $user->graduation_year ? $user->graduation_year . '年卒' : '未設定';
        })->map(function ($gradeUsers) {
            return $gradeUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'graduation_year' => $user->graduation_year ? $user->graduation_year . '年卒' : '未設定',
                    'exam_sessions' => $user->examSessions->map(function ($session) {
                        $score = $this->calculateScore($session->id);

                        return [
                            'total_score' => round($score, 2),
                        ];
                    }),
                ];
            });
        })->sortKeys();

        return Inertia::render('Admin/Results/GradeList', [
            'usersByGrade' => $usersByGrade,
        ]);
    }

    /**
     * 統計ページ
     */
    public function statistics(Request $request)
    {
        // フィルターパラメータ
        $grade = $request->input('grade'); // 例: 1,2,3 または 'all'
        $eventId = $request->input('event_id'); // イベントを直接選択する場合のID

        $baseQuery = ExamSession::with('event')->whereNotNull('finished_at')
            ->whereNull('disqualified_at');

        // 現在の学年度を計算
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        $academicYear = $currentMonth >= 4 ? $currentYear : $currentYear - 1;

        // 有効な学年フィルターを判定
        $validGrade = null;
        if ($grade !== null && $grade !== '' && $grade !== 'all') {
            // 'grad_YYYY' 形式の場合は卒業年度でフィルタリング
            if (strpos($grade, 'grad_') === 0) {
                $graduationYear = (int) str_replace('grad_', '', $grade);
                $baseQuery = $baseQuery->whereHas('user', function($query) use ($graduationYear) {
                    $query->where('graduation_year', $graduationYear);
                });
                $validGrade = $grade;
            } else {
                // 通常の grade (1, 2, 3) の場合、対応する graduation_year を計算してフィルタリング
                $gradeInt = (int) $grade;
                if (in_array($gradeInt, [1, 2, 3])) {
                    // 学年から卒業年度を計算
                    // 3年生: academicYear + 1, 2年生: academicYear + 2, 1年生: academicYear + 3
                    $targetGraduationYear = $academicYear + (4 - $gradeInt);
                    
                    $baseQuery = $baseQuery->whereHas('user', function($query) use ($targetGraduationYear) {
                        $query->where('graduation_year', $targetGraduationYear);
                    });
                    $validGrade = (string) $gradeInt;
                }
            }
        }

        if ($eventId) {
            $baseQuery = $baseQuery->where('event_id', (int) $eventId);
        }

        $totalSessions = (clone $baseQuery)->count();

        $totalUsers = User::count();

        $sessions = (clone $baseQuery)->get();
        
        // イベントでフィルタリングしている場合、そのイベントの問題数を取得
        $filteredEvent = null;
        $filteredEventPartCounts = null;
        if ($eventId) {
            $filteredEvent = Event::find((int) $eventId);
            if ($filteredEvent) {
                // まずイベント自体の問題数設定を確認
                if ($filteredEvent->part1_questions !== null || $filteredEvent->part2_questions !== null || $filteredEvent->part3_questions !== null) {
                    $filteredEventPartCounts = [
                        1 => $filteredEvent->part1_questions ?? 40,
                        2 => $filteredEvent->part2_questions ?? 30,
                        3 => $filteredEvent->part3_questions ?? 25,
                    ];
                } elseif ($sessions->count() > 0) {
                    // イベントに問題数設定がない場合、回答があるセッションから取得
                    foreach ($sessions as $s) {
                        $answerCount = Answer::where('exam_session_id', $s->id)->count();
                        if ($answerCount > 0) {
                            $filteredEventPartCounts = $this->getSessionPartQuestionCounts($s);
                            break;
                        }
                    }
                }
            }
        }

        // 全セッションのスコアを計算
        $scores = [];
        $rankCounts = [
            'Platinum' => 0,
            'Gold' => 0,
            'Silver' => 0,
            'Bronze' => 0,
        ];

        // パート別スコアと問題数を記録
        $partScores = [1 => [], 2 => [], 3 => []];
        $partQuestionCounts = [1 => [], 2 => [], 3 => []];

        foreach ($sessions as $session) {
            $totalScore = $this->calculateScore($session->id);
            $scores[] = $totalScore;

            // セッションごとの問題数を取得してランク計算
            $totalQuestions = $this->getSessionQuestionCount($session);
            $rank = $this->calculateRank($totalScore, $totalQuestions);
            $rankCounts[$rank]++;
            
            // パート別問題数を取得
            $sessionPartCounts = $this->getSessionPartQuestionCounts($session);

            // パート別スコアと問題数を集計
            for ($part = 1; $part <= 3; $part++) {
                $partScore = $this->calculateScore($session->id, $part);
                $partScores[$part][] = $partScore;
                $partQuestionCounts[$part][] = $sessionPartCounts[$part];
            }
        }

        $averageScore = count($scores) > 0
            ? round(array_sum($scores) / count($scores), 2)
            : 0;

        // パート別平均点と問題数情報を計算
        $partAverages = [];
        for ($part = 1; $part <= 3; $part++) {
            $avgScore = count($partScores[$part]) > 0
                ? round(array_sum($partScores[$part]) / count($partScores[$part]), 2)
                : 0;
            
            // 問題数を決定
            $questionCount = null;
            
            // 1. イベントフィルター時で回答があるセッションがあれば使用
            if ($filteredEventPartCounts !== null && $filteredEventPartCounts[$part] > 0) {
                $questionCount = $filteredEventPartCounts[$part];
            }
            
            // 2. セッションから集計した問題数を確認（0以外のみ）
            if ($questionCount === null && !empty($partQuestionCounts[$part])) {
                $validCounts = array_filter($partQuestionCounts[$part], fn($c) => $c > 0);
                if (!empty($validCounts)) {
                    $uniqueCounts = array_unique($validCounts);
                    if (count($uniqueCounts) === 1) {
                        // 全セッションが同じ問題数
                        $questionCount = reset($uniqueCounts);
                    } else {
                        // 問題数が混在 → null（後でデフォルト使用）
                        $questionCount = null;
                    }
                }
            }
            
            // 3. どちらも取れなければデフォルト値
            if ($questionCount === null || $questionCount === 0) {
                $questionCount = $part === 1 ? 40 : ($part === 2 ? 30 : 25);
            }
            
            // 最低点 = 問題数 × -0.25、最高点 = 問題数
            $minScore = round($questionCount * -0.25, 2);
            $maxScore = $questionCount;
            
            $partAverages[$part] = [
                'average' => $avgScore,
                'question_count' => $questionCount,
                'min_score' => $minScore,
                'max_score' => $maxScore,
            ];
        }

        // 月別受験者数を計算(フィルター後の $sessions を基にグルーピング)
        $monthlyData = [];
        foreach ($sessions as $s) {
            if (! $s->finished_at) continue;
            $m = (int) $s->finished_at->format('n');
            if (! isset($monthlyData[$m])) $monthlyData[$m] = 0;
            $monthlyData[$m]++;
        }

        // 月が抜けている場合は 0 を設定(1〜12)
        for ($month = 1; $month <= 12; $month++) {
            if (! isset($monthlyData[$month])) $monthlyData[$month] = 0;
        }

        ksort($monthlyData);

        // イベント選択用リスト(最近のイベントを取得)
        $eventList = Event::orderBy('begin', 'desc')
            ->take(100)
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'label' => $e->name . ' (' . strtoupper($e->passphrase) . ') — ' . ($e->begin ? $e->begin->format('Y-m-d') : ''),
                ];
            });

        // ===== 修正部分: gradeCounts の生成ロジック =====
        // 現在年を取得して、在学生の卒業年度を計算
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n'); // 1-12
        
        // 4月以降なら次年度扱い（例: 2025年4月 = 2025年度）
        // 1-3月なら前年度扱い（例: 2025年3月 = 2024年度）
        $academicYear = $currentMonth >= 4 ? $currentYear : $currentYear - 1;
        
        // 各学年の卒業予定年度を計算
        // 3年生: 今年度末 (academicYear + 1)
        // 2年生: 来年度末 (academicYear + 2)
        // 1年生: 再来年度末 (academicYear + 3)
        $gradeGraduationYears = [
            3 => $academicYear + 1,  // 3年生の卒業年
            2 => $academicYear + 2,  // 2年生の卒業年
            1 => $academicYear + 3,  // 1年生の卒業年
        ];
        
        // exam_sessions.grade ごとのセッション数を集計
        $gradeCountsRaw = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->whereNotNull('grade')
            ->selectRaw('grade, COUNT(*) as count')
            ->groupBy('grade')
            ->orderBy('grade')
            ->pluck('count', 'grade')
            ->toArray();

        // Log::info('Grade counts raw data', ['gradeCountsRaw' => $gradeCountsRaw]);

        $gradeCounts = [];

        // 1〜3年生は常に表示（卒業年度付き）
        for ($grade = 1; $grade <= 3; $grade++) {
            $count = isset($gradeCountsRaw[$grade]) ? (int) $gradeCountsRaw[$grade] : 0;
            $graduationYear = $gradeGraduationYears[$grade];
            
            $gradeCounts[] = [
                'grade' => $grade,
                'label' => "{$grade}年 ({$graduationYear}年卒)",
                'count' => $count,
            ];
        }

        // 卒業生（過去の卒業年度）を graduation_year から集計
        // 現在の在学生の卒業年度を除外するため、それより前の年度のみ表示
        $currentGraduationYears = array_values($gradeGraduationYears); // [2026, 2027, 2028]
        
        $pastGraduates = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->with('user')
            ->get()
            ->filter(function($session) {
                return $session->user && $session->user->graduation_year;
            })
            ->map(function($session) {
                return [
                    'graduation_year' => $session->user->graduation_year,
                    'session' => $session,
                ];
            })
            ->filter(function($data) use ($currentGraduationYears) {
                // 現在の在学生の卒業年度は除外
                return !in_array($data['graduation_year'], $currentGraduationYears);
            })
            ->groupBy('graduation_year')
            ->map(function($group, $graduationYear) {
                return [
                    'graduation_year' => $graduationYear,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('graduation_year') // 新しい年度順
            ->values();

        foreach ($pastGraduates as $graduate) {
            $gradeCounts[] = [
                'grade' => 'grad_' . $graduate['graduation_year'], // 一意なキーを生成
                'label' => $graduate['graduation_year'] . '年卒',
                'count' => $graduate['count'],
                'is_graduate' => true,
                'graduation_year' => $graduate['graduation_year'],
            ];
        }

        // Log::info('Final grade counts', ['gradeCounts' => $gradeCounts]);

        return Inertia::render('Admin/Results/Statistics', [
            'stats' => [
                'total_sessions' => $totalSessions,
                'total_users' => $totalUsers,
                'average_score' => $averageScore,
                'rank_distribution' => $rankCounts,
                'part_averages' => $partAverages,
                'monthly_data' => $monthlyData,
            ],
            'filters' => [
                'grade' => $validGrade,
                'event_id' => $eventId ? (int) $eventId : null,
            ],
            'events' => $eventList,
            'gradeCounts' => $gradeCounts,
        ]);
    }

    /**
     * COM連携ページ(Comlink)
     */
    public function comlink()
    {
        // イベント情報も一緒に取得
        $sessions = ExamSession::with(['user', 'event'])
            ->whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->latest('finished_at')
            ->get()
            ->map(function ($session) {
                $totalScore = $this->calculateScore($session->id);
                $totalQuestions = $this->getSessionQuestionCount($session);
                $rank = $this->calculateRank($totalScore, $totalQuestions);

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