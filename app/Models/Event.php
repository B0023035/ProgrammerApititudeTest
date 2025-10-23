<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'passphrase',
        'begin',
        'end',
        'exam_type',
    ];

    protected $casts = [
        'begin' => 'datetime',
        'end' => 'datetime',
    ];

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