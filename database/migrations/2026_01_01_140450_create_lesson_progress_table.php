<?php

// database/migrations/xxxx_create_lesson_progress_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('lesson_progress', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
      $table->string('status')->default('not_started')->index(); // not_started|in_progress|completed
      $table->unsignedInteger('watched_seconds')->default(0);
      $table->unsignedInteger('last_position_seconds')->default(0);
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();

      $table->unique(['user_id','lesson_id']);
      $table->index(['user_id','status']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('lesson_progress');
  }
};
