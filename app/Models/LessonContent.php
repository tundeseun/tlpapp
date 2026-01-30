<?php

// app/Models/LessonContent.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonContent extends Model {
  protected $fillable = ['lesson_id','content_type','video_provider','video_url','text_html','attachments'];
  protected $casts = ['attachments' => 'array'];

  public function lesson(): BelongsTo { return $this->belongsTo(Lesson::class); }
}
