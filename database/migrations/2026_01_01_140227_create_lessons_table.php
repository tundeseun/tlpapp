<?php

// database/migrations/xxxx_create_lessons_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('lessons', function (Blueprint $table) {
      $table->id();
      $table->foreignId('module_id')->constrained()->cascadeOnDelete();
      $table->string('title');
      $table->unsignedInteger('position')->default(1)->index();
      $table->string('type')->default('mixed')->index(); // video|text|mixed
      $table->unsignedInteger('duration_seconds')->nullable(); // important for strict completion
      $table->boolean('is_published')->default(false)->index();
      $table->timestamps();
      $table->index(['module_id','position']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('lessons');
  }
};
