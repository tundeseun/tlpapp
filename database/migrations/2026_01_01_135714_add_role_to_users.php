<?php
// database/migrations/xxxx_add_role_to_users.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('users', function (Blueprint $table) {
      $table->string('role')->default('student')->index(); // admin|student
    });
  }
  public function down(): void {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('role');
    });
  }
};

