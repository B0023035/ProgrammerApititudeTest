<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticeChoice extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'label', 'text', 'is_correct'];

    public function question()
    {
        return $this->belongsTo(PracticeQuestion::class, 'question_id', 'id');
    }
}
