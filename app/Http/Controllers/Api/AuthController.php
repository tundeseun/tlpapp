<?php

// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller {
  public function __construct(private OtpService $otp) {}

  public function register(Request $req) {
    $data = $req->validate([
      'name' => 'required|string|max:120',
      'email' => 'required|email|unique:users,email',
      'password' => 'required|string|min:6|confirmed'
    ]);

    $user = User::create([
      'name' => $data['name'],
      'email' => strtolower($data['email']),
      'password' => Hash::make($data['password']),
      'role' => 'student',
      'email_verified_at' => null,
    ]);

    $otp = $this->otp->createOtp($user, $user->email, 'email_verify');
    Mail::to($user->email)->send(new OtpMail($otp['code'], 'email_verify'));

    return response()->json(['message' => 'Registered. OTP sent to email.']);
  }

  public function login(Request $req) {
    $data = $req->validate([
      'email' => 'required|email',
      'password' => 'required|string'
    ]);

    $user = User::where('email', strtolower($data['email']))->first();
    if (!$user || !Hash::check($data['password'], $user->password)) {
      throw ValidationException::withMessages(['email' => ['Invalid credentials']]);
    }
    if (!$user->email_verified_at) {
      return response()->json(['message' => 'Email not verified'], 403);
    }

    $token = $user->createToken('mobile')->plainTextToken;
    return response()->json(['token' => $token, 'user' => $user]);
  }

  public function logout(Request $req) {
    $req->user()->currentAccessToken()?->delete();
    return response()->json(['message' => 'Logged out']);
  }
}

