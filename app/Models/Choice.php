<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'question_id',
        'part',
        'label',
        'text',
        'image',
        'is_correct',
    ];

    protected $casts = [
        'question_id' => 'integer',
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
