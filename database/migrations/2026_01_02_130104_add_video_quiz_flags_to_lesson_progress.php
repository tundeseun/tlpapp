<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('lesson_progress', function (Blueprint $table) {
      if (!Schema::hasColumn('lesson_progress', 'video_completed')) {
        $table->boolean('video_completed')->default(false)->index();
      }
      if (!Schema::hasColumn('lesson_progress', 'quiz_passed')) {
        $table->boolean('quiz_passed')->default(false)->index();
      }
    });
  }

  public function down(): void {
    Schema::table('lesson_progress', function (Blueprint $table) {
      if (Schema::hasColumn('lesson_progress', 'video_completed')) $table->dropColumn('video_completed');
      if (Schema::hasColumn('lesson_progress', 'quiz_passed')) $table->dropColumn('quiz_passed');
    });
  }
};
