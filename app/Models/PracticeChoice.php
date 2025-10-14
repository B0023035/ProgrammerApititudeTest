<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PracticeChoice extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'label', 'text', 'is_correct'];

    public function question()
    {
        return $this->belongsTo(PracticeQuestion::class, 'question_id', 'id');
    }
}
