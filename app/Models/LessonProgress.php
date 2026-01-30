<?php

// app/Models/LessonProgress.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model {
  protected $table = 'lesson_progress';
  protected $fillable = ['user_id','lesson_id','status','watched_seconds','last_position_seconds','completed_at'];
  protected $casts = ['completed_at' => 'datetime'];

  public function user(): BelongsTo { return $this->belongsTo(User::class); }
  public function lesson(): BelongsTo { return $this->belongsTo(Lesson::class); }
}
