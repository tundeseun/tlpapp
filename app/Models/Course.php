<?php

// app/Models/Course.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model {
  protected $fillable = ['title','slug','description','thumbnail_url','is_published'];

  public function modules(): HasMany { return $this->hasMany(Module::class); }
}

