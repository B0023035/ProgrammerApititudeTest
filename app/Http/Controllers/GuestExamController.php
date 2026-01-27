<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * ゲスト用試験コントローラー
 */
class GuestExamController extends Controller
{
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    /**
     * ゲスト用本番試験開始処理
     */
    public function start(Request $request)
    {
        $guestId = session()->getId();

        Log::info('=== guestStart呼び出し ===', [
            'guest_id' => $guestId,
        ]);

        $guestName = session('guest_name') ?? session('guest_info.name') ?? 'ゲスト';
        $guestSchool = session('guest_school_name') ?? session('guest_info.school_name') ?? '学校名未入力';

        $existingSessionKey = "guest_exam_session_{$guestId}";
        $existingSession = Cache::get($existingSessionKey);

        if ($existingSession && !isset($existingSession['finished_at'])) {
            return redirect()->route('guest.exam.part', ['part' => $existingSession['current_part'] ?? 1]);
        }

        $newSession = [
            'guest_id' => $guestId,
            'guest_name' => $guestName,
            'guest_school' => $guestSchool,
            'started_at' => now(),
            'current_part' => 1,
            'current_question' => 1,
            'remaining_time' => 0,
            'violation_count' => 0,
            'security_log' => [],
        ];

        Cache::put($existingSessionKey, $newSession, 2 * 60 * 60);

        session([
            'guest_name' => $guestName,
            'guest_school_name' => $guestSchool,
        ]);

        Log::info('新しいゲスト試験セッション作成', [
            'guest_id' => $guestId,
            'guest_name' => $guestName,
        ]);

        return redirect()->route('guest.exam.part', ['part' => 1]);
    }

    /**
     * ゲスト情報保存処理
     */
    public function storeGuestInfo(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:100',
            'guest_name' => 'required|string|max:100',
        ]);

        session([
            'guest_school_name' => $validated['school_name'],
            'guest_name' => $validated['guest_name'],
            'guest_info' => [
                'name' => $validated['guest_name'],
                'school_name' => $validated['school_name'],
            ],
            'is_guest' => true,
        ]);

        Log::info('ゲスト情報登録', [
            'school_name' => $validated['school_name'],
            'guest_name' => $validated['guest_name'],
        ]);

        return Inertia::render('ExamInstructions', [
            'isGuest' => true,
        ]);
    }

    /**
     * ゲスト用パート画面表示
     */
    public function part(Request $request, $part)
    {
        $guestId = session()->getId();
        $part = (int) $part;

        if (!in_array($part, [1, 2, 3])) {
            return redirect()->route('guest.test.start')->with('error', '無効なパート番号です。');
        }

        $existingSessionKey = "guest_exam_session_{$guestId}";
        $session = Cache::get($existingSessionKey);

        $sessionCode = session('exam_session_code') ?? session('verified_session_code');
        $event = $sessionCode ? $this->examService->getEventBySessionCode($sessionCode) : null;
        $examType = $event ? $event->exam_type : 'full';

        if (!$session) {
            $guestName = session('guest_name') ?? 'ゲスト';
            $guestSchool = session('guest_school_name') ?? '学校名未入力';

            $session = [
                'guest_id' => $guestId,
                'guest_name' => $guestName,
                'guest_school' => $guestSchool,
                'started_at' => now(),
                'current_part' => $part,
                'current_question' => 1,
                'remaining_time' => 0,
                'violation_count' => 0,
                'security_log' => [],
            ];

            Cache::put($existingSessionKey, $session, 2 * 60 * 60);
        }

        if (($session['violation_count'] ?? 0) >= 3) {
            return redirect()->route('guest.exam.disqualified');
        }

        $partTimeLimit = $this->examService->getPartTimeLimitByEvent($part, $examType, $event);

        if ($session['remaining_time'] > 0) {
            $remainingTime = $session['remaining_time'];
        } else {
            $remainingTime = $partTimeLimit;
            $session['remaining_time'] = $remainingTime;
            Cache::put($existingSessionKey, $session, 2 * 60 * 60);
        }

        $sessionId = (string) Str::uuid();
        Cache::put("guest_exam_part_session_{$guestId}_{$sessionId}", [
            'guest_id' => $guestId,
            'part' => $part,
            'started_at' => now(),
        ], 30 * 60);

        $answersKey = "guest_exam_answers_{$guestId}_part_{$part}";
        $savedAnswers = Cache::get($answersKey, []);

        $questionCount = $this->examService->getQuestionCountByEvent($part, $examType, $event);

        $questions = Question::with(['choices' => function ($query) use ($part) {
            $query->where('part', $part)->orderBy('label');
        }])
            ->where('part', $part)
            ->orderBy('number')
            ->take($questionCount)
            ->get()
            ->map(function ($q) use ($savedAnswers) {
                return [
                    'id' => $q->id,
                    'number' => $q->number,
                    'part' => $q->part,
                    'text' => $q->text,
                    'image' => $q->image,
                    'choices' => $q->choices->map(fn($c) => [
                        'id' => $c->id,
                        'label' => $c->label,
                        'text' => $c->text,
                        'image' => $c->image,
                        'part' => $c->part,
                    ]),
                    'selected' => $savedAnswers[$q->id] ?? null,
                ];
            });

        return Inertia::render('Part', [
            'examSessionId' => $sessionId,
            'practiceSessionId' => $sessionId,
            'practiceQuestions' => $questions,
            'part' => $part,
            'questions' => $questions,
            'currentPart' => $part,
            'partTime' => $partTimeLimit,
            'remainingTime' => $remainingTime,
            'currentQuestion' => $session['current_question'] ?? 1,
            'totalParts' => 3,
            'violationCount' => $session['violation_count'] ?? 0,
            'examType' => $examType,
            'isGuest' => true,
        ]);
    }

    /**
     * ゲストパート完了処理
     */
    public function completePart(Request $request)
    {
        $request->validate(['_token' => 'required|string']);

        $guestId = session()->getId();
        $sessionId = $request->input('examSessionId');
        $part = $request->input('part');

        $cacheKey = "guest_exam_part_session_{$guestId}_{$sessionId}";
        if (!Cache::get($cacheKey)) {
            return redirect()->route('guest.test.start')->with('error', 'セッションが無効です。');
        }

        $existingSessionKey = "guest_exam_session_{$guestId}";
        $examSession = Cache::get($existingSessionKey);
        if (!$examSession) {
            return redirect()->route('guest.test.start')->with('error', 'セッションが見つかりません。');
        }

        $answers = $request->input('answers', []);
        $sanitizedAnswers = $this->examService->sanitizeAnswers($answers);
        Cache::put("guest_exam_answers_{$guestId}_part_{$part}", $sanitizedAnswers, 2 * 60 * 60);

        if ($part < 3) {
            $examSession['current_part'] = $part + 1;
            $examSession['current_question'] = 1;
            $examSession['remaining_time'] = 0;
            Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);
            Cache::forget($cacheKey);

            return redirect()->route('guest.practice.show', ['section' => $part + 1])
                ->with('success', "第{$part}部が完了しました。");
        }

        // 第三部完了 - 採点
        return $this->calculateAndShowResult($guestId, $examSession, $cacheKey);
    }

    /**
     * 採点して結果表示
     */
    private function calculateAndShowResult($guestId, $examSession, $cacheKey)
    {
        $event = isset($examSession['event_id']) ? \App\Models\Event::find($examSession['event_id']) : null;
        $examType = $examSession['exam_type'] ?? 'full';

        $results = [];
        $maxScores = [];

        for ($p = 1; $p <= 3; $p++) {
            $partAnswers = Cache::get("guest_exam_answers_{$guestId}_part_{$p}", []);
            $totalQuestions = $this->examService->getQuestionCountByEvent($p, $examType, $event);

            $correct = 0;
            $incorrect = 0;

            foreach ($partAnswers as $questionId => $choice) {
                $question = Question::with('choices')->find($questionId);
                if ($question && $question->part == $p) {
                    $correctChoice = $question->choices()->where('part', $p)->where('is_correct', true)->first();
                    if ($correctChoice && trim($correctChoice->label) === trim($choice)) {
                        $correct++;
                    } else {
                        $incorrect++;
                    }
                }
            }

            $unanswered = $totalQuestions - ($correct + $incorrect);
            $score = ($correct * 1) + ($incorrect * -0.25);

            $results[$p] = [
                'correct' => $correct,
                'incorrect' => $incorrect,
                'unanswered' => $unanswered,
                'total' => $totalQuestions,
                'score' => round($score, 2),
            ];
            $maxScores[$p] = $totalQuestions;
        }

        $totalScore = $results[1]['score'] + $results[2]['score'] + $results[3]['score'];
        $maxTotalScore = $maxScores[1] + $maxScores[2] + $maxScores[3];
        $rankInfo = $this->examService->calculateRank($totalScore, $maxTotalScore);

        $guestName = $examSession['guest_name'] ?? session('guest_name') ?? 'ゲスト';
        $guestSchool = $examSession['guest_school'] ?? session('guest_school_name') ?? '学校名未入力';

        session([
            'exam_results' => [
                'results' => $results,
                'rankName' => $rankInfo['rankName'],
                'totalScore' => round($totalScore, 2),
                'rank' => $rankInfo['rank'],
            ],
            'isGuest' => true,
            'guestName' => $guestName,
            'guestSchool' => $guestSchool,
        ]);

        $examSession['finished_at'] = now();
        Cache::put("guest_exam_session_{$guestId}", $examSession, 2 * 60 * 60);
        Cache::forget($cacheKey);

        return redirect()->route('guest.result')->with('success', '試験が完了しました。');
    }

    /**
     * ゲスト試験結果表示
     */
    public function showResult()
    {
        $guestId = session()->getId();
        $examResults = session('exam_results');

        if (!$examResults) {
            return redirect()->route('guest.test.start')->with('error', '試験結果が見つかりません。');
        }

        return Inertia::render('Result', [
            'auth' => ['user' => null],
            'results' => $examResults['results'],
            'totalScore' => $examResults['totalScore'],
            'rank' => $examResults['rank'],
            'rankName' => $examResults['rankName'],
            'isGuest' => true,
            'guestName' => session('guestName') ?? session('guest_name') ?? 'ゲスト',
            'guestSchool' => session('guestSchool') ?? session('guest_school_name') ?? '学校名未入力',
        ]);
    }

    /**
     * ゲスト用データクリーンアップ
     */
    public function cleanup(Request $request)
    {
        $guestId = session()->getId();

        for ($part = 1; $part <= 3; $part++) {
            Cache::forget("guest_exam_answers_{$guestId}_part_{$part}");
            Cache::forget("guest_exam_result_{$guestId}_part_{$part}");
        }
        Cache::forget("guest_exam_session_{$guestId}");

        return response()->json(['success' => true]);
    }

    /**
     * ゲスト失格画面
     */
    public function disqualified()
    {
        $guestId = session()->getId();
        $session = Cache::get("guest_exam_session_{$guestId}");

        if (!$session || ($session['violation_count'] ?? 0) < 3) {
            return redirect()->route('guest.test.start');
        }

        return Inertia::render('Exam/Disqualified', [
            'examSession' => $session,
            'violations' => $session['security_log'] ?? [],
            'disqualificationReason' => 'Multiple security violations',
            'isGuest' => true,
        ]);
    }

    /**
     * ゲスト違反報告
     */
    public function reportViolation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'examSessionId' => 'required|string|size:36',
            'violationType' => 'required|string',
            'timestamp' => 'required|string',
            'violationCount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }

        $guestId = session()->getId();
        $sessionId = $request->input('examSessionId');

        $cacheKey = "guest_exam_part_session_{$guestId}_{$sessionId}";
        if (!Cache::get($cacheKey)) {
            return response()->json(['success' => false], 403);
        }

        $existingSessionKey = "guest_exam_session_{$guestId}";
        $examSession = Cache::get($existingSessionKey);
        if (!$examSession) {
            return response()->json(['success' => false], 403);
        }

        $securityLog = $examSession['security_log'] ?? [];
        $securityLog[] = [
            'timestamp' => $request->timestamp,
            'violation_type' => $request->violationType,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];

        $examSession['security_log'] = $securityLog;
        $examSession['violation_count'] = ($examSession['violation_count'] ?? 0) + 1;

        if ($examSession['violation_count'] >= 3) {
            $examSession['disqualified_at'] = now();
        }

        Cache::put($existingSessionKey, $examSession, 2 * 60 * 60);

        return response()->json([
            'success' => true,
            'violation_count' => $examSession['violation_count'],
            'disqualified' => $examSession['violation_count'] >= 3,
        ]);
    }

    /**
     * ゲスト本番試験の説明画面
     */
    public function explanation(Request $request, $part = 1)
    {
        if (!in_array($part, [1, 2, 3])) {
            $part = 1;
        }

        return Inertia::render('Explanation', [
            'nextPart' => (int) $part,
            'isExam' => true,
            'isGuest' => true,
        ]);
    }
}
