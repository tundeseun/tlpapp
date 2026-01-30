<?php

// app/Models/OtpCode.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model {
  protected $fillable = [
    'user_id','email','purpose','code_hash','expires_at','consumed_at','attempts','last_sent_at'
  ];
  protected $casts = ['expires_at' => 'datetime', 'consumed_at' => 'datetime', 'last_sent_at' => 'datetime'];
}
