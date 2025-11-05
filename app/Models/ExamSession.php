<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_uuid',  // 追加
        'event_id',
        'started_at',
        'finished_at',
        'disqualified_at',
        'disqualification_reason',
        'current_part',
        'current_question',
        'remaining_time',
        'security_log',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'disqualified_at' => 'datetime',
        'security_log' => 'array',
    ];

    /**
     * モデルの「起動」メソッド
     */
    protected static function boot()
    {
        parent::boot();

        // 新規作成時に自動的にUUIDを生成
        static::creating(function ($model) {
            if (empty($model->session_uuid)) {
                $model->session_uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * このセッションのユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // イベントとのリレーション（これを追加）
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * このセッションの違反記録
     */
    public function violations(): HasMany
    {
        return $this->hasMany(ExamViolation::class);
    }

    /**
     * このセッションの回答
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'user_id', 'user_id')
            ->whereBetween('created_at', [$this->started_at, $this->finished_at ?? now()]);
    }

    /**
     * セッションが失格しているかチェック
     */
    public function isDisqualified(): bool
    {
        return ! is_null($this->disqualified_at);
    }

    /**
     * セッションが完了しているかチェック
     */
    public function isFinished(): bool
    {
        return ! is_null($this->finished_at);
    }

    /**
     * セッションがアクティブかチェック
     */
    public function isActive(): bool
    {
        return is_null($this->finished_at) && is_null($this->disqualified_at);
    }

    /**
     * 違反回数を取得
     */
    public function getViolationCount(): int
    {
        return $this->violations()->count();
    }

    /**
     * 特定の違反タイプの回数を取得
     */
    public function getViolationCountByType(string $type): int
    {
        return $this->violations()->where('violation_type', $type)->count();
    }
}
