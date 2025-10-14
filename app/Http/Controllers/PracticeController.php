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
     * パート別練習問題表示（共通）
     */
    public function show($section = 1)
    {
        if (!in_array($section, [1, 2, 3])) {
            abort(404);
        }

        $isGuest = !Auth::check();
        $sessionId = (string) Str::uuid();
        
        if ($isGuest) {
            $guestId = session()->getId();
            Cache::put("guest_practice_session_{$guestId}_{$sessionId}", [
                'guest_id' => $guestId,
                'started_at' => now(),
                'part' => $section,
            ], 30 * 60);
        } else {
            $user = Auth::user();
            Cache::put("practice_session_{$user->id}_{$sessionId}", [
                'user_id' => $user->id,
                'started_at' => now(),
                'part' => $section,
            ], 30 * 60);
        }

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

        return Inertia::render('Practice', [
            'practiceQuestions' => $questions,
            'currentPart' => (int)$section,
            'partTime' => 300,
            'practiceSessionId' => $sessionId,
            'isGuest' => $isGuest,
        ]);
    }

    /**
     * 練習問題完了処理（共通）
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
                
                return redirect()->route('practice.index')
                    ->with('error', 'セッションが無効です。練習を最初からやり直してください。');
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

            // 第三部完了後は本番試験への準備
            return Inertia::render('PracticeExplanation', [
                'practiceQuestions' => $questions,
                'answers' => $sanitizedAnswers,
                'currentPart' => $part,
                'timeSpent' => $validated['timeSpent'],
                'isGuest' => $isGuest,
                'isLastPart' => $part == 3,
                'nextAction' => $part == 3 ? 'exam' : 'next-part',
                // ★ 追加: 練習問題完了時に古いセッション情報をクリア
                'clearOldSessions' => true,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Practice completion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['error' => '処理中にエラーが発生しました。']);
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
                
                return redirect()->route('guest.practice.show', ['section' => 1])
                    ->with('error', 'セッションが無効です。練習を最初からやり直してください。');
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

            // ★ 重要: セッション情報を再保存（念のため）
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

            // PracticeExplanation.vue にレンダリング
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
            return redirect()->back()->withErrors(['error' => '処理中にエラーが発生しました。']);
        }
    }
}