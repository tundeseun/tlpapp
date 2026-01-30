<?php

// database/migrations/xxxx_create_quiz_questions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('quiz_questions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
      $table->string('question');
      $table->json('options'); // ["A","B","C","D"]
      $table->unsignedTinyInteger('correct_index'); // 0..3
      $table->unsignedInteger('position')->default(1)->index();
      $table->timestamps();
      $table->index(['quiz_id','position']);
    });
  }
  public function down(): void { Schema::dropIfExists('quiz_questions'); }
};
