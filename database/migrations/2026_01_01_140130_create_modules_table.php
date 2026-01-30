<?php

// database/migrations/xxxx_create_modules_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('modules', function (Blueprint $table) {
      $table->id();
      $table->foreignId('course_id')->constrained()->cascadeOnDelete();
      $table->string('title');
      $table->text('description')->nullable();
      $table->unsignedInteger('position')->default(1)->index();
      $table->boolean('is_published')->default(false)->index();
      $table->timestamps();
      $table->index(['course_id','position']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('modules');
  }
};
