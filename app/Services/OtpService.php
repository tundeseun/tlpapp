<?php
// app/Services/OtpService.php
namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService {
  public function generateCode(): string {
    return (string) random_int(100000, 999999);
  }

  public function createOtp(?User $user, string $email, string $purpose, int $ttlMinutes = 10): array {
    $code = $this->generateCode();

    OtpCode::create([
      'user_id' => $user?->id,
      'email' => strtolower($email),
      'purpose' => $purpose,
      'code_hash' => Hash::make($code),
      'expires_at' => Carbon::now()->addMinutes($ttlMinutes),
      'last_sent_at' => Carbon::now(),
    ]);

    return ['code' => $code];
  }

  public function verifyOtp(string $email, string $purpose, string $code): ?OtpCode {
    $row = OtpCode::query()
      ->where('email', strtolower($email))
      ->where('purpose', $purpose)
      ->whereNull('consumed_at')
      ->where('expires_at', '>', Carbon::now())
      ->orderByDesc('id')
      ->first();

    if (!$row) return null;

    $row->attempts += 1;
    $row->save();

    if ($row->attempts > 5) return null;

    if (!Hash::check($code, $row->code_hash)) return null;

    $row->consumed_at = Carbon::now();
    $row->save();

    return $row;
  }

  public function makeSessionToken(): string {
    return Str::random(48);
  }
}
