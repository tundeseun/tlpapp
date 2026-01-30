<?php

// database/migrations/xxxx_add_quiz_flags_to_lesson_progress.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('lesson_progress', function (Blueprint $table) {
      $table->boolean('video_completed')->default(false)->index();
      $table->boolean('quiz_passed')->default(false)->index();
    });
  }
  public function down(): void {
    Schema::table('lesson_progress', function (Blueprint $table) {
      $table->dropColumn(['video_completed','quiz_passed']);
    });
  }
};
