<?php

// app/Http/Controllers/Api/OtpController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller {
  public function __construct(private OtpService $otp) {}

  public function resend(Request $req) {
    $data = $req->validate([
      'email' => 'required|email',
      'purpose' => 'required|in:email_verify,forgot_login',
    ]);

    $user = User::where('email', strtolower($data['email']))->first();
    $otp = $this->otp->createOtp($user, $data['email'], $data['purpose']);
    Mail::to($data['email'])->send(new OtpMail($otp['code'], $data['purpose']));

    return response()->json(['message' => 'OTP sent']);
  }

  public function verifyEmail(Request $req) {
    $data = $req->validate([
      'email' => 'required|email',
      'code' => 'required|string|min:4|max:8',
    ]);

    $row = $this->otp->verifyOtp($data['email'], 'email_verify', $data['code']);
    if (!$row) return response()->json(['message' => 'Invalid or expired OTP'], 422);

    $user = User::where('email', strtolower($data['email']))->first();
    if (!$user) return response()->json(['message' => 'User not found'], 404);

    $user->email_verified_at = now();
    $user->save();

    $token = $user->createToken('mobile')->plainTextToken;
    return response()->json(['message' => 'Email verified', 'token' => $token, 'user' => $user]);
  }

  public function forgotSendOtp(Request $req) {
    $data = $req->validate(['email' => 'required|email']);
    $user = User::where('email', strtolower($data['email']))->first();

    // always respond same (avoid user enumeration)
    $otp = $this->otp->createOtp($user, $data['email'], 'forgot_login');
    Mail::to($data['email'])->send(new OtpMail($otp['code'], 'forgot_login'));

    return response()->json(['message' => 'If the email exists, OTP was sent']);
  }

  public function loginWithOtp(Request $req) {
    $data = $req->validate([
      'email' => 'required|email',
      'code' => 'required|string|min:4|max:8',
    ]);

    $row = $this->otp->verifyOtp($data['email'], 'forgot_login', $data['code']);
    if (!$row) return response()->json(['message' => 'Invalid or expired OTP'], 422);

    $user = User::where('email', strtolower($data['email']))->first();
    if (!$user) return response()->json(['message' => 'User not found'], 404);
    if (!$user->email_verified_at) return response()->json(['message' => 'Email not verified'], 403);

    $token = $user->createToken('mobile')->plainTextToken;
    return response()->json(['message' => 'Logged in', 'token' => $token, 'user' => $user]);
  }
}

