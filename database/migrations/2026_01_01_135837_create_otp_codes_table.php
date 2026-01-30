<?php
// database/migrations/xxxx_create_otp_codes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('otp_codes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->string('email')->index();
      $table->string('purpose')->index(); // email_verify | forgot_login
      $table->string('code_hash');
      $table->timestamp('expires_at');
      $table->timestamp('consumed_at')->nullable();
      $table->unsignedInteger('attempts')->default(0);
      $table->timestamp('last_sent_at')->nullable();
      $table->timestamps();

      $table->index(['email', 'purpose', 'expires_at']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('otp_codes');
  }
};

