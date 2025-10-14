<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'exam_session_id',
        'question_id',
        'part',
        'choice',
        'is_correct',
    ];
    
    protected $casts = [
        'user_id' => 'integer',
        'question_id' => 'integer',
        'part' => 'integer',
        'is_correct' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
    
    public function getSelectedChoiceAttribute()
    {
        return $this->question->choices()->where('label', $this->choice)->first();
    }
}