<?php

// app/Models/Lesson.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model {
  protected $fillable = ['module_id','title','position','type','duration_seconds','is_published'];

  public function module(): BelongsTo { return $this->belongsTo(Module::class); }
  public function contents(): HasMany { return $this->hasMany(LessonContent::class); }

  public function quiz() {
  return $this->hasOne(\App\Models\Quiz::class);
}

}

