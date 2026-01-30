<?php

// database/migrations/xxxx_create_video_sessions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('video_sessions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
      $table->string('session_token')->unique();
      $table->timestamp('started_at');
      $table->timestamp('last_heartbeat_at')->nullable();
      $table->unsignedInteger('max_position_seconds')->default(0);
      $table->timestamp('ended_at')->nullable();
      $table->timestamps();

      $table->index(['user_id','lesson_id']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('video_sessions');
  }
};
