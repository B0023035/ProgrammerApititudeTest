<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PracticeQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['section', 'question', 'options', 'answer'];

    public function choices() {
        return $this->hasMany(PracticeChoice::class, 'question_id');
    }

}
