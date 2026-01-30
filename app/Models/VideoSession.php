<?php

// app/Models/VideoSession.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoSession extends Model {
  protected $fillable = [
    'user_id','lesson_id','session_token','started_at','last_heartbeat_at','max_position_seconds','ended_at'
  ];
  protected $casts = ['started_at'=>'datetime','last_heartbeat_at'=>'datetime','ended_at'=>'datetime'];
}

