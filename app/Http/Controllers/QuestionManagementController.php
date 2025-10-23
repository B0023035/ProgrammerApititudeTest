<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionManagementController extends Controller
{
    public function index(Request $request)
    {
        $part = $request->get('part', 1);
        
        $questions = Question::where('part', $part)
            ->with('choices')
            ->orderBy('number')
            ->paginate(20);
        
        return Inertia::render('Admin/Questions/Index', [
            'questions' => $questions,
            'currentPart' => $part,
        ]);
    }

    public function show($id)
    {
        $question = Question::with('choices')->findOrFail($id);
        return Inertia::render('Admin/Questions/Show', ['question' => $question]);
    }

    public function create()
    {
        return Inertia::render('Admin/Questions/Create');
    }

    public function store(Request $request)
    {
        // 実装予定
        return redirect()->route('admin.questions.index');
    }

    public function edit($id)
    {
        $question = Question::with('choices')->findOrFail($id);
        return Inertia::render('Admin/Questions/Edit', ['question' => $question]);
    }

    public function update(Request $request, $id)
    {
        // 実装予定
        return redirect()->route('admin.questions.index');
    }

    public function destroy($id)
    {
        Question::findOrFail($id)->delete();
        return redirect()->route('admin.questions.index');
    }
}