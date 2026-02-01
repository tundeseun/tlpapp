<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Admin registration (protected by secret)
     */
    public function register(Request $req)
    {
        $data = $req->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'secret'   => 'required|string',
        ]);

        if ($data['secret'] !== env('ADMIN_REGISTER_SECRET')) {
            return response()->json(['message' => 'Invalid admin secret'], 403);
        }

        $user = User::create([
            'name'              => $data['name'],
            'email'             => strtolower($data['email']),
            'password'          => Hash::make($data['password']),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('admin')->plainTextToken;

        return response()->json([
            'status' => true,
            'token'  => $token,
            'user'   => $user,
        ], 201);
    }

    /**
     * Admin login
     */
    public function login(Request $req)
    {
        $data = $req->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower($data['email']))
            ->where('role', 'admin')
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid admin credentials.'],
            ]);
        }

        // Optional: revoke old tokens (recommended for admin)
        $user->tokens()->delete();

        $token = $user->createToken('admin')->plainTextToken;

        return response()->json([
            'status' => true,
            'token'  => $token,
            'user'   => $user,
        ]);
    }

    /**
     * Admin logout
     */
    public function logout(Request $req)
    {
        $req->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
