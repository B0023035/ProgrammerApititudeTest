@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- ヘッダー -->
    <div class="mb-8">
        <a href="{{ route('results.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
            ← 一覧に戻る
        </a>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">セッション詳細</h1>
                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                        <span>ユーザー: <a href="{{ route('results.user-detail', $session->user_id) }}" class="text-blue-600 hover:underline">{{ $session->user->name }}</a></span>
                        <span>受験日時: {{ $session->finished_at->format('Y-m-d H:i') }}</span>
                        <span>所要時間: {{ $session->started_at->diffInMinutes($session->finished_at) }}分</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-blue-600">
                        {{ array_sum(array_column($partScores, 'correct')) }}/95
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ round((array_sum(array_column($partScores, 'correct')) / 95) * 100, 1) }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- パート別サマリー -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @foreach($partScores as $part => $score)
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-lg font-semibold text-gray-800 mb-4">Part {{ $part }}</div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl font-bold text-gray-800">{{ $score['correct'] }}/{{ $score['total'] }}</span>
                <span class="text-lg font-semibold text-blue-600">{{ $score['percentage'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: {{ $score['percentage'] }}%"></div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- 各パートの詳細 -->
    @foreach($answersByPart as $part => $answers)
    <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">Part {{ $part }} の解答</h2>
                <span class="text-sm text-gray-600">
                    {{ $partScores[$part]['correct'] }}/{{ $partScores[$part]['total'] }} 正解
                </span>
            </div>
        </div>
        
        <div class="divide-y divide-gray-200">
            @foreach($answers as $answer)
            <div class="p-6 {{ $answer->is_correct ? 'bg-green-50' : 'bg-red-50' }}">
                <div class="flex items-start">
                    <!-- 問題番号と正誤マーク -->
                    <div class="flex-shrink-0 mr-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center {{ $answer->is_correct ? 'bg-green-500' : 'bg-red-500' }} text-white font-bold">
                            {{ $answer->question->number }}
                        </div>
                    </div>

                    <!-- 問題内容 -->
                    <div class="flex-1">
                        <div class="mb-4">
                            @if($answer->question->image)
                                <img src="{{ asset('storage/questions/' . $answer->question->image) }}" 
                                     alt="Question {{ $answer->question->number }}" 
                                     class="max-w-md rounded-lg shadow">
                            @endif
                            @if($answer->question->text)
                                <p class="text-gray-800 text-lg">{{ $answer->question->text }}</p>
                            @endif
                        </div>

                        <!-- 選択肢 -->
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($answer->question->choices as $choice)
                            <div class="flex items-center p-3 rounded-lg border-2
                                {{ $choice->label === $answer->choice && $answer->is_correct ? 'border-green-500 bg-green-100' : '' }}
                                {{ $choice->label === $answer->choice && !$answer->is_correct ? 'border-red-500 bg-red-100' : '' }}
                                {{ $choice->is_correct && $choice->label !== $answer->choice ? 'border-blue-500 bg-blue-50' : '' }}
                                {{ !$choice->is_correct && $choice->label !== $answer->choice ? 'border-gray-200 bg-white' : '' }}">
                                
                                <span class="font-bold mr-3 text-lg">{{ $choice->label }}.</span>
                                
                                @if($choice->image)
                                    <img src="{{ asset('storage/choices/' . $choice->image) }}" 
                                         alt="Choice {{ $choice->label }}" 
                                         class="max-h-20">
                                @else
                                    <span class="text-gray-800">{{ $choice->text }}</span>
                                @endif

                                <!-- マーク表示 -->
                                <div class="ml-auto flex items-center space-x-2">
                                    @if($choice->label === $answer->choice)
                                        <span class="text-sm font-semibold {{ $answer->is_correct ? 'text-green-700' : 'text-red-700' }}">
                                            あなたの回答
                                        </span>
                                    @endif
                                    @if($choice->is_correct)
                                        <span class="px-2 py-1 bg-blue-500 text-white text-xs font-bold rounded">
                                            正解
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- 結果表示 -->
                        <div class="mt-4 p-3 rounded-lg {{ $answer->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            @if($answer->is_correct)
                                <span class="font-semibold">✓ 正解です！</span>
                            @else
                                <span class="font-semibold">✗ 不正解</span>
                                <span class="ml-2">正解は 
                                    <strong>{{ $answer->question->choices->where('is_correct', 1)->first()->label }}</strong> 
                                    です
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection