<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EventQuestion extends Pivot
{
    protected $table = 'event_questions';

    protected $fillable = [
        'event_id',
        'question_id',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
