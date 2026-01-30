<?php

// database/migrations/xxxx_create_quizzes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('quizzes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete();
      $table->unsignedInteger('pass_mark')->default(70); // percent
      $table->unsignedInteger('max_attempts')->default(0); // 0 = unlimited
      $table->boolean('is_published')->default(false)->index();
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('quizzes'); }
};

