<?php
// database/migrations/xxxx_create_lesson_contents_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('lesson_contents', function (Blueprint $table) {
      $table->id();
      $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
      $table->string('content_type')->index(); // video|text
      $table->string('video_provider')->nullable()->index(); // youtube|gdrive|...
      $table->text('video_url')->nullable();
      $table->longText('text_html')->nullable();
      $table->json('attachments')->nullable();
      $table->timestamps();
      $table->index(['lesson_id','content_type']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('lesson_contents');
  }
};
