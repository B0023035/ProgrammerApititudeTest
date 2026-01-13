<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

        protected $fillable = [
        'name',
        'passphrase',
        'begin',
        'end',
        'exam_type',
        'question_selection_mode', // 追加: random または custom
        // カスタム設定を追加
        'part1_questions',
        'part1_time',
        'part2_questions',
        'part2_time',
        'part3_questions',
        'part3_time',
    ];

    protected $casts = [
        'begin' => 'datetime',
        'end' => 'datetime',
        // カスタム設定を整数型にキャスト
        'part1_questions' => 'integer',
        'part1_time' => 'integer',
        'part2_questions' => 'integer',
        'part2_time' => 'integer',
        'part3_questions' => 'integer',
        'part3_time' => 'integer',
    ];

    /**
     * イベントで出題するカスタム問題（多対多）
     */
    public function customQuestions()
    {
        return $this->belongsToMany(Question::class, 'event_questions')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    /**
     * 指定したパートのカスタム問題を取得
     */
    public function getCustomQuestionsForPart(int $part)
    {
        return $this->customQuestions()->where('part', $part)->get();
    }

    /**
     * カスタム問題モードかどうか
     */
    public function isCustomQuestionMode(): bool
    {
        return $this->question_selection_mode === 'custom';
    }

    /**
     * イベントが現在有効かどうかを判定
     */
    public function isActive(): bool
    {
        $now = Carbon::now();

        return $now->between($this->begin, $this->end);
    }

    /**
     * イベントが開始前かどうかを判定
     */
    public function isUpcoming(): bool
    {
        return Carbon::now()->lt($this->begin);
    }

    /**
     * イベントが終了しているかどうかを判定
     */
    public function isExpired(): bool
    {
        return Carbon::now()->gt($this->end);
    }
}
