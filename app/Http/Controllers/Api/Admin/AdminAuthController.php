<?php

// app/Http/Controllers/Api/Admin/AdminAuthController.php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function register(Request $req)
    {
        $data = $req->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'secret' => 'required|string',
        ]);

        if ($data['secret'] !== env('ADMIN_REGISTER_SECRET')) {
            return response()->json(['message' => 'Invalid admin secret'], 403);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'email_verified_at' => now(), // admin can be auto verified
        ]);
dd([
  'class' => is_object($user) ? get_class($user) : gettype($user),
  'is_user_model' => $user instanceof \App\Models\User,
  'has_createToken' => is_object($user) ? method_exists($user, 'createToken') : false,
  'value' => $user,
]);

        $token = $user->createToken('admin')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }
}

