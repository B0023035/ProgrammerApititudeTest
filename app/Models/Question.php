<?php

// app/Models/Question.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'part',
        'number',
        'text',
        'image',
    ];
    
    protected $casts = [
        'part' => 'integer',
        'number' => 'integer',
    ];
    
    public function choices()
    {
        return $this->hasMany(Choice::class)->orderBy('label');
    }
    
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    
    public function correctChoice()
    {
        return $this->hasOne(Choice::class)->where('is_correct', true);
    }
}