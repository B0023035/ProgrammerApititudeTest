<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'graduation_year',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ユーザーの試験セッション
     */
    public function examSessions()
    {
        return $this->hasMany(ExamSession::class);
    }

    /**
     * 現在アクティブな試験セッション
     */
    public function activeExamSession()
    {
        return $this->hasOne(ExamSession::class)->whereNull('finished_at')->latest();
    }

    /**
     * ユーザーの回答
     */
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * 特定パートの回答
     */
    public function answersForPart($part)
    {
        return $this->answers()->where('part', $part);
    }

    /**
     * 最新の完了した試験セッション
     */
    public function latestCompletedExam()
    {
        return $this->examSessions()->whereNotNull('finished_at')->latest('finished_at');
    }

    /**
     * 現在の学年を計算（卒業年度から逆算）
     * 年度は4月1日に切り替わる
     * @return int|null
     */
    public function getCurrentGrade()
    {
        if (!$this->graduation_year) {
            return null;
        }
        
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        
        // 4月以降なら現在の年度、1-3月なら前年度
        $academicYear = $currentMonth >= 4 ? $currentYear : $currentYear - 1;
        
        // 学年を計算
        // 例: 2025年度で2026年卒 → 4 - (2026 - 2025) = 3年生
        // 例: 2025年度で2027年卒 → 4 - (2027 - 2025) = 2年生
        // 例: 2025年度で2028年卒 → 4 - (2028 - 2025) = 1年生
        // 例: 2025年度で2025年卒 → 4 - (2025 - 2025) = 4 → 卒業生
        return 4 - ($this->graduation_year - $academicYear);
    }
}
