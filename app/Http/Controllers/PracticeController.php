<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\PracticeQuestion;

class PracticeController extends Controller
{
    public function index()
    {
        return redirect()->route('practice.show', ['section' => 1]);
    }

    /**
     * 練習問題表示(修正版 - 試験タイプ対応)
     */
    public function show(Request $request, $section)
    {
        $user = Auth::user();
        $section = (int) $section;
        
        if (!in_array($section, [1, 2, 3])) {
            return redirect()->route('dashboard')
                ->with('error', '無効なセクションです。');
        }
        
        // セッションコードから試験タイプを取得
        $sessionCode = session('exam_session_code');
        $event = $this->getEventBySessionCode($sessionCode);
        $examType = $event ? $event->exam_type : 'full';
        
        // 練習問題数を取得
        $practiceQuestionCount = $this->getPracticeQuestionCountByEvent($section, $examType);
        
        // 該当セクションの練習問題を取得
        $practiceQuestions = \App\Models\PracticeQuestion::with(['choices' => function($query) use ($section) {
            $query->where('part', $section)->orderBy('label');
        }])
            ->where('part', $section)
            ->orderBy('number')
            ->limit($practiceQuestionCount)
            ->get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'number' => $q->number,
                    'part' => $q->part,
                    'text' => $q->text,
                    'explanation' => $q->explanation,
                    'image' => $q->image,
                    'choices' => $q->choices->map(function ($c) {
                        return [
                            'id' => $c->id,
                            'label' => $c->label,
                            'text' => $c->text,
                            'image' => $c->image,
                            'part' => $c->part,
                            'is_correct' => $c->is_correct,
                        ];
                    }),
                ];
            });
        
        // 練習問題用のセッションIDを生成
        $practiceSessionId = (string) Str::uuid();
        
        // キャッシュに保存(1時間有効)
        Cache::put("practice_session_{$user->id}_{$practiceSessionId}", [
            'user_id' => $user->id,
            'section' => $section,
            'started_at' => now(),
            'exam_type' => $examType,
        ], 60 * 60);
        
        Log::info('練習問題セッション作成', [
            'user_id' => $user->id,
            'section' => $section,
            'practice_session_id' => $practiceSessionId,
            'exam_type' => $examType,
            'question_count' => $practiceQuestions->count(),
        ]);
        
        return Inertia::render('Practice', [
            'practiceSessionId' => $practiceSessionId,
            'practiceQuestions' => $practiceQuestions,
            'currentSection' => $section,
            'sectionTime' => $this->getPracticeTimeLimitByEvent($section, $examType),
            'totalSections' => 3,
            'examType' => $examType,
        ]);
    }

    /**
     * 練習問題完了処理(共通)
     */
    public function complete(Request $request)
    {
        try {
            $isGuest = !Auth::check();
            
            $validated = $request->validate([
                'practiceSessionId' => 'required|uuid',
                'part' => 'required|integer|in:1,2,3',
                'answers' => 'required|array',
                'answers.*' => 'string|in:A,B,C,D,E',
                'timeSpent' => 'required|integer|min:1',
                'totalQuestions' => 'nullable|integer|min:1',
            ]);

            $sessionId = $validated['practiceSessionId'];
            $part = $validated['part'];
            
            // セッション検証
            if ($isGuest) {
                $guestId = session()->getId();
                $sessionData = Cache::get("guest_practice_session_{$guestId}_{$sessionId}");
                $userId = $guestId;
            } else {
                $user = Auth::user();
                $sessionData = Cache::get("practice_session_{$user->id}_{$sessionId}");
                $userId = $user->id;
            }
            
            if (!$sessionData) {
                Log::warning('不正な練習セッション', [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'ip' => $request->ip(),
                    'is_guest' => $isGuest
                ]);
                
                // ★ 修正: Inertia::render() を使用してエラーページを表示
                return Inertia::render('Error', [
                    'status' => 400,
                    'message' => 'セッションが無効です。練習を最初からやり直してください。'
                ]);
            }

            // 回答データのサニタイズ
            $sanitizedAnswers = $this->sanitizeAnswers($validated['answers']);
            
            // 該当パートの練習問題を取得
            $questions = PracticeQuestion::where('part', $part)
                ->with('choices')
                ->orderBy('number')
                ->get()
                ->map(function ($q) use ($sanitizedAnswers) {
                    $correctChoice = $q->choices->firstWhere('is_correct', true);
                    $correctAnswer = $correctChoice ? $correctChoice->label : 'A';
                    
                    return [
                        'id' => $q->id,
                        'number' => $q->number,
                        'part' => $q->part,
                        'text' => $q->text,
                        'image' => $q->image,
                        'choices' => $q->choices->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'label' => $c->label,
                                'text' => $c->text,
                                'is_correct' => (bool) $c->is_correct,
                                'image' => $c->image,
                                'part' => $c->part,
                            ];
                        }),
                        'explanation' => $q->explanation,
                        'answer' => $correctAnswer,
                        'selected' => $sanitizedAnswers[$q->id] ?? null,
                    ];
                });

            // セッションをクリア
            if ($isGuest) {
                Cache::forget("guest_practice_session_{$guestId}_{$sessionId}");
            } else {
                Cache::forget("practice_session_{$user->id}_{$sessionId}");
            }

            // ★ 修正: Inertia::render() を使用して直接レンダリング
            return Inertia::render('PracticeExplanation', [
                'practiceQuestions' => $questions,
                'answers' => $sanitizedAnswers,
                'currentPart' => $part,
                'timeSpent' => $validated['timeSpent'],
                'isGuest' => $isGuest,
                'isLastPart' => $part == 3,
                'nextAction' => $part == 3 ? 'exam' : 'next-part',
                'clearOldSessions' => true,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Practice completion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // ★ 修正: back() の代わりに Inertia::render() でエラー表示
            return Inertia::render('Error', [
                'status' => 500,
                'message' => '処理中にエラーが発生しました。'
            ]);
        }
    }

    /**
     * ★ 追加: ゲスト用パート別練習問題表示
     */
    public function guestShow($section = 1)
    {
        if (!in_array($section, [1, 2, 3])) {
            abort(404);
        }

        $guestId = session()->getId();
        $sessionId = (string) Str::uuid();
        
        Cache::put("guest_practice_session_{$guestId}_{$sessionId}", [
            'guest_id' => $guestId,
            'started_at' => now(),
            'part' => $section,
        ], 30 * 60);

        $questions = PracticeQuestion::where('part', $section)
            ->with(['choices' => function($query) use ($section) {
                $query->where('part', $section)->orderBy('label');
            }])
            ->orderBy('number')
            ->get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'number' => $q->number,
                    'part' => $q->part,
                    'text' => $q->text,
                    'image' => $q->image,
                    'choices' => $q->choices->map(function ($c) {
                        return [
                            'id' => $c->id,
                            'label' => $c->label,
                            'text' => $c->text,
                            'part' => $c->part,
                            'image' => $c->image,
                        ];
                    }),
                ];
            });

        Log::info('ゲスト練習問題表示', [
            'guest_id' => $guestId,
            'section' => $section,
            'questions_count' => $questions->count(),
        ]);

        return Inertia::render('Practice', [
            'practiceQuestions' => $questions,
            'currentPart' => (int)$section,
            'partTime' => 300,
            'practiceSessionId' => $sessionId,
            'isGuest' => true,
        ]);
    }

    /**
     * ★ 追加: ゲスト用練習問題完了処理
     */
    public function guestComplete(Request $request)
    {
        try {
            $validated = $request->validate([
                'practiceSessionId' => 'required|uuid',
                'part' => 'required|integer|in:1,2,3',
                'answers' => 'required|array',
                'answers.*' => 'string|in:A,B,C,D,E',
                'timeSpent' => 'required|integer|min:1',
                'totalQuestions' => 'nullable|integer|min:1',
            ]);

            $guestId = session()->getId();
            $sessionId = $validated['practiceSessionId'];
            $part = $validated['part'];
            

            // ★ セッション情報を取得して保持
            $guestName = session('guest_name') ?? session('guest_info.name') ?? 'ゲスト';
            $guestSchool = session('guest_school_name') ?? session('guest_info.school_name') ?? '学校名未入力';
            
            Log::info('ゲスト練習完了処理開始', [
                'guest_id' => $guestId,
                'part' => $part,
                'guest_name' => $guestName,
                'guest_school' => $guestSchool,
                'session_all' => session()->all(),
            ]);
            
            // セッション検証
            $sessionData = Cache::get("guest_practice_session_{$guestId}_{$sessionId}");
            
            if (!$sessionData) {
                Log::warning('不正なゲスト練習セッション', [
                    'guest_id' => $guestId,
                    'session_id' => $sessionId,
                    'ip' => $request->ip()
                ]);
                
                // ★ 修正: Inertia::render() を使用してエラーページを表示
                return Inertia::render('Error', [
                    'status' => 400,
                    'message' => 'セッションが無効です。練習を最初からやり直してください。'
                ]);
            }

            // 回答データのサニタイズ
            $sanitizedAnswers = $this->sanitizeAnswers($validated['answers']);
            
            // 該当パートの練習問題を取得
            $questions = PracticeQuestion::where('part', $part)
                ->with('choices')
                ->orderBy('number')
                ->get()
                ->map(function ($q) use ($sanitizedAnswers) {
                    $correctChoice = $q->choices->firstWhere('is_correct', true);
                    $correctAnswer = $correctChoice ? $correctChoice->label : 'A';
                    
                    return [
                        'id' => $q->id,
                        'number' => $q->number,
                        'part' => $q->part,
                        'text' => $q->text,
                        'image' => $q->image,
                        'choices' => $q->choices->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'label' => $c->label,
                                'text' => $c->text,
                                'is_correct' => (bool) $c->is_correct,
                                'image' => $c->image,
                                'part' => $c->part,
                            ];
                        }),
                        'explanation' => $q->explanation,
                        'answer' => $correctAnswer,
                        'selected' => $sanitizedAnswers[$q->id] ?? null,
                    ];
                });

            // セッションをクリア
            Cache::forget("guest_practice_session_{$guestId}_{$sessionId}");

            // ★ 重要: セッション情報を再保存(念のため)
            session([
                'guest_name' => $guestName,
                'guest_school_name' => $guestSchool,
                'guest_info' => [
                    'name' => $guestName,
                    'school_name' => $guestSchool,
                ],
                'is_guest' => true,
            ]);

            Log::info('ゲスト練習問題完了', [
                'guest_id' => $guestId,
                'part' => $part,
                'answers_count' => count($sanitizedAnswers),
                'guest_name' => $guestName,
                'guest_school' => $guestSchool,
            ]);

            // ★ 修正: Inertia::render() を使用して直接レンダリング
            return Inertia::render('PracticeExplanation', [
                'practiceQuestions' => $questions,
                'answers' => $sanitizedAnswers,
                'currentPart' => $part,
                'timeSpent' => $validated['timeSpent'],
                'isGuest' => true,
                'guestName' => $guestName,
                'guestSchool' => $guestSchool,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Guest practice completion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // ★ 修正: Inertia::render() を使用してエラーページを表示
            return Inertia::render('Error', [
                'status' => 500,
                'message' => '処理中にエラーが発生しました。'
            ]);
        }
    }

    /**
     * 回答データのサニタイズ
     */
    private function sanitizeAnswers(array $answers): array
    {
        $sanitized = [];
        $validChoices = ['A', 'B', 'C', 'D', 'E'];
        
        foreach ($answers as $questionId => $answer) {
            if (!is_numeric($questionId) || $questionId < 1) {
                continue;
            }
            
            $cleanAnswer = strtoupper(trim($answer));
            if (in_array($cleanAnswer, $validChoices)) {
                $sanitized[(int)$questionId] = $cleanAnswer;
            }
        }
        
        return $sanitized;
    }

    /**
     * セッションコードからイベント情報を取得
     */
    private function getEventBySessionCode($sessionCode)
    {
        if (!$sessionCode) {
            return null;
        }
        
        $event = \App\Models\Event::where('passphrase', $sessionCode)
            ->where('begin', '<=', now())
            ->where('end', '>=', now())
            ->first();
        
        return $event;
    }

    /**
     * 練習問題の問題数を取得
     */
    private function getPracticeQuestionCountByEvent($part, $examType = 'full')
    {
        // 全バージョン共通で全問出題
        $practiceQuestionCounts = [
            1 => 4,
            2 => 2,
            3 => 2,
        ];
        
        return $practiceQuestionCounts[$part] ?? 2;
    }

    /**
     * 練習問題の時間を取得
     */
    private function getPracticeTimeLimitByEvent($part, $examType = 'full')
    {
        // full版の練習時間設定
        $fullPracticeTimeLimits = [
            1 => 300,   // 5分(300秒) - 4問
            2 => 180,   // 3分(180秒) - 2問
            3 => 120,   // 2分(120秒) - 2問
        ];
        
        // 45min版と30min版の練習時間設定(全パート2分固定)
        $shortPracticeTimeLimits = [
            1 => 120,   // 2分(120秒) - 4問
            2 => 120,   // 2分(120秒) - 2問
            3 => 120,   // 2分(120秒) - 2問
        ];
        
        switch ($examType) {
            case '45min':
            case '30min':
                return $shortPracticeTimeLimits[$part] ?? 120;
            case 'full':
            default:
                return $fullPracticeTimeLimits[$part] ?? 120;
        }
    }
}