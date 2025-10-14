<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ExamAnswer;
use Inertia\Inertia;

class ResultsComlinkController extends Controller
{
    public function index()
    {
        // 実際のデータをLaravelから取得
        $sessions = ExamSession::with(['user', 'answers'])
            ->latest()
            ->get();

        return Inertia::render('Admin/ResultsComlink', [
            'sessions' => $sessions,
        ]);
    }
}