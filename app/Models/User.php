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
}
