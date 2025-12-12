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
     * ランク計算(新基準)
     * 〜35.75: D (Bronze)
     * 36〜50.75: C (Silver)
     * 51〜60.75: B (Gold)
     * 61〜: A (Platinum)
     */
    private function calculateRank($score)
    {
        if ($score >= 61) {
            return 'Platinum';
        }
        if ($score >= 51) {
            return 'Gold';
        }
        if ($score >= 36) {
            return 'Silver';
        }

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
    public function statistics(Request $request)
    {
        // フィルターパラメータ
        $grade = $request->input('grade'); // 例: 1,2,3 または 'all'
        $eventId = $request->input('event_id'); // イベントを直接選択する場合のID

        Log::info('Statistics filter inputs', [
            'grade' => $grade,
            'event_id' => $eventId,
        ]);

        $baseQuery = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at');

        if ($grade !== null && $grade !== '' && $grade !== 'all') {
            Log::info('Applying grade filter', ['grade' => (int) $grade]);
            $baseQuery = $baseQuery->where('grade', (int) $grade);
        }

        if ($eventId) {
            Log::info('Applying event_id filter', ['event_id' => (int) $eventId]);
            $baseQuery = $baseQuery->where('event_id', (int) $eventId);
        }

        $totalSessions = (clone $baseQuery)->count();
        Log::info('Total sessions after filter', ['count' => $totalSessions]);

        $totalUsers = User::count();

        $sessions = (clone $baseQuery)->get();

        // 全セッションのスコアを計算
        $scores = [];
        $rankCounts = [
            'Platinum' => 0,
            'Gold' => 0,
            'Silver' => 0,
            'Bronze' => 0,
        ];

        $partScores = [1 => [], 2 => [], 3 => []];

        foreach ($sessions as $session) {
            $totalScore = $this->calculateScore($session->id);
            $scores[] = $totalScore;

            // ランク集計
            $rank = $this->calculateRank($totalScore);
            $rankCounts[$rank]++;

            // パート別スコア集計
            for ($part = 1; $part <= 3; $part++) {
                $partScore = $this->calculateScore($session->id, $part);
                $partScores[$part][] = $partScore;
            }
        }

        $averageScore = count($scores) > 0
            ? round(array_sum($scores) / count($scores), 2)
            : 0;

        // 得点分布を計算 (95点満点)
        $scoreDistribution = [
            '90-95' => 0,
            '80-89' => 0,
            '70-79' => 0,
            '60-69' => 0,
            '0-59' => 0,
        ];

        foreach ($scores as $score) {
            if ($score >= 90) {
                $scoreDistribution['90-95']++;
            } elseif ($score >= 80) {
                $scoreDistribution['80-89']++;
            } elseif ($score >= 70) {
                $scoreDistribution['70-79']++;
            } elseif ($score >= 60) {
                $scoreDistribution['60-69']++;
            } else {
                $scoreDistribution['0-59']++;
            }
        }

        // パート別平均点を計算
        $partAverages = [];
        for ($part = 1; $part <= 3; $part++) {
            $partAverages[$part] = count($partScores[$part]) > 0
                ? round(array_sum($partScores[$part]) / count($partScores[$part]), 2)
                : 0;
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
                    'label' => $e->name . ' — ' . ($e->begin ? $e->begin->format('Y-m-d') : ''),
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
        
        Log::info('Academic year calculation', [
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
            'academicYear' => $academicYear,
            'gradeGraduationYears' => $gradeGraduationYears
        ]);
        
        // exam_sessions.grade ごとのセッション数を集計
        $gradeCountsRaw = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->whereNotNull('grade')
            ->selectRaw('grade, COUNT(*) as count')
            ->groupBy('grade')
            ->orderBy('grade')
            ->pluck('count', 'grade')
            ->toArray();

        Log::info('Grade counts raw data', ['gradeCountsRaw' => $gradeCountsRaw]);

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

        // 卒業生（過去の卒業年度）を admission_year から集計
        // 現在の在学生の卒業年度を除外するため、それより前の年度のみ表示
        $currentGraduationYears = array_values($gradeGraduationYears); // [2026, 2027, 2028]
        
        $pastGraduates = ExamSession::whereNotNull('finished_at')
            ->whereNull('disqualified_at')
            ->with('user')
            ->get()
            ->filter(function($session) {
                return $session->user && $session->user->admission_year;
            })
            ->map(function($session) {
                return [
                    'admission_year' => $session->user->admission_year,
                    'graduation_year' => $session->user->admission_year + 3,
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

        Log::info('Final grade counts', ['gradeCounts' => $gradeCounts]);
        
        // フィルター処理の修正
        $validGrade = null;
        if ($grade !== null && $grade !== '' && $grade !== 'all') {
            // 'grad_YYYY' 形式の場合は卒業年度でフィルタリング
            if (strpos($grade, 'grad_') === 0) {
                $graduationYear = (int) str_replace('grad_', '', $grade);
                // admission_year から卒業年度を計算してフィルタリング
                $baseQuery = $baseQuery->whereHas('user', function($query) use ($graduationYear) {
                    $query->whereRaw('admission_year + 3 = ?', [$graduationYear]);
                });
                $validGrade = $grade;
            } else {
                // 通常の grade (1, 2, 3) でフィルタリング
                $gradeInt = (int) $grade;
                if (in_array($gradeInt, [1, 2, 3])) {
                    $validGrade = (string) $gradeInt;
                } else {
                    Log::warning('Invalid grade selected', ['grade' => $grade]);
                }
            }
        }

        // validGrade が設定されている場合、再集計
        if ($validGrade !== null) {
            if (strpos($validGrade, 'grad_') === 0) {
                // 卒業年度でフィルタリング済み
                $totalSessions = (clone $baseQuery)->count();
                $sessions = (clone $baseQuery)->get();
            } else {
                // 通常の grade でフィルタリング
                $totalSessions = (clone $baseQuery)->where('grade', (int) $validGrade)->count();
                $sessions = (clone $baseQuery)->where('grade', (int) $validGrade)->get();
            }
            
            // スコアなどを再計算
            $scores = [];
            $rankCounts = [
                'Platinum' => 0,
                'Gold' => 0,
                'Silver' => 0,
                'Bronze' => 0,
            ];
            $partScores = [1 => [], 2 => [], 3 => []];

            foreach ($sessions as $session) {
                $totalScore = $this->calculateScore($session->id);
                $scores[] = $totalScore;

                $rank = $this->calculateRank($totalScore);
                $rankCounts[$rank]++;

                for ($part = 1; $part <= 3; $part++) {
                    $partScore = $this->calculateScore($session->id, $part);
                    $partScores[$part][] = $partScore;
                }
            }

            $averageScore = count($scores) > 0
                ? round(array_sum($scores) / count($scores), 2)
                : 0;

            $scoreDistribution = [
                '90-95' => 0,
                '80-89' => 0,
                '70-79' => 0,
                '60-69' => 0,
                '0-59' => 0,
            ];

            foreach ($scores as $score) {
                if ($score >= 90) {
                    $scoreDistribution['90-95']++;
                } elseif ($score >= 80) {
                    $scoreDistribution['80-89']++;
                } elseif ($score >= 70) {
                    $scoreDistribution['70-79']++;
                } elseif ($score >= 60) {
                    $scoreDistribution['60-69']++;
                } else {
                    $scoreDistribution['0-59']++;
                }
            }

            $partAverages = [];
            for ($part = 1; $part <= 3; $part++) {
                $partAverages[$part] = count($partScores[$part]) > 0
                    ? round(array_sum($partScores[$part]) / count($partScores[$part]), 2)
                    : 0;
            }

            $monthlyData = [];
            foreach ($sessions as $s) {
                if (! $s->finished_at) continue;
                $m = (int) $s->finished_at->format('n');
                if (! isset($monthlyData[$m])) $monthlyData[$m] = 0;
                $monthlyData[$m]++;
            }

            for ($month = 1; $month <= 12; $month++) {
                if (! isset($monthlyData[$month])) $monthlyData[$month] = 0;
            }
            ksort($monthlyData);
        }

        return Inertia::render('Admin/Results/Statistics', [
            'stats' => [
                'total_sessions' => $totalSessions,
                'total_users' => $totalUsers,
                'average_score' => $averageScore,
                'rank_distribution' => $rankCounts,
                'score_distribution' => $scoreDistribution,
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
        $partQuestionCounts = $this->getPartQuestionCounts();
        $totalQuestions = array_sum($partQuestionCounts);

        // イベント情報も一緒に取得
        $sessions = ExamSession::with(['user', 'event'])
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