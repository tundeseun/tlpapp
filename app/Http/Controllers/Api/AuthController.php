<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon; // ✅ FIXED

class AuthController extends Controller
{
    public function __construct(private OtpService $otp) {}

    // public function register(Request $req)
    // {
    //     $data = $req->validate([
    //         'name' => 'required|string|max:120',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:6|confirmed',
    //     ]);

    //     $user = User::create([
    //         'name' => $data['name'],
    //         'email' => strtolower($data['email']),
    //         'password' => Hash::make($data['password']),
    //         'role' => 'student',
    //         'email_verified_at' => Carbon::now(), // ✅ Auto verify immediately
    //     ]);

    //     return response()->json([
    //         'message' => 'Registration successful. You can now login.',
    //         'user' => [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'email' => $user->email,
    //         ],
    //     ], 201);
    // }

    public function register(Request $req)
{
    $data = $req->validate([
        'name' => 'required|string|max:120',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $user = User::create([
        'name' => $data['name'],
        'email' => strtolower($data['email']),
        'password' => Hash::make($data['password']),
        'role' => 'student',
        'email_verified_at' => Carbon::now(),
    ]);

    // ✅ Return token so mobile can become "authed" immediately
    $token = $user->createToken('mobile')->plainTextToken;

    return response()->json([
        'message' => 'Registration successful.',
        'token' => $token,
        'user' => $user,
    ], 201);
}


    public function login(Request $req)
    {
        $data = $req->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower($data['email']))->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        // Optional: keep or remove. It won't block now because you set email_verified_at on register.
        // if (!$user->email_verified_at) {
        //     return response()->json(['message' => 'Email not verified'], 403);
        // }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $req)
    {
        // ✅ Best fix: avoids Intelephense error and logs out properly
        $req->user()?->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
