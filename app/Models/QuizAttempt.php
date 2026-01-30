<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
  protected $fillable = [
    'quiz_id', 'user_id', 'score_percent', 'passed', 'answers', 'submitted_at'
  ];

  protected $casts = [
    'answers' => 'array',
    'submitted_at' => 'datetime',
    'passed' => 'boolean',
  ];

  public function quiz() {
    return $this->belongsTo(Quiz::class);
  }

  public function user() {
    return $this->belongsTo(User::class);
  }
}
