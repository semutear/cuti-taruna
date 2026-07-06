<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register untuk taruna
    public function register(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'npm' => 'required|string|unique:users',
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'nama_ibu' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
        ]);

        $user = User::create([
            'nama_lengkap' => $request->nama_lengkap,
            'npm' => $request->npm,
            'password' => Hash::make($request->password),
            'role' => 'taruna',
            'nama_ibu' => $request->nama_ibu,
            'tanggal_lahir' => $request->tanggal_lahir,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 201);
    }

    // Login untuk taruna
    public function loginTaruna(Request $request)
    {
        $request->validate([
            'npm' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('npm', $request->npm)
                    ->where('role', 'taruna')
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'npm' => ['NPM atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    // Registrasi akun Orang Tua, terhubung ke taruna lewat NPM anak
    public function registerOrangTua(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'npm_anak' => 'required|string',
        ]);

        $anak = User::where('npm', $request->npm_anak)
                    ->where('role', 'taruna')
                    ->first();

        if (!$anak) {
            return response()->json([
                'status' => 'error',
                'message' => 'NPM taruna tidak ditemukan.'
            ], 404);
        }

        if (User::where('role', 'orang_tua')->where('child_id', $anak->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun orang tua untuk taruna ini sudah terdaftar.'
            ], 422);
        }

        $orangTua = User::create([
            'nama_lengkap' => $request->nama_lengkap,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'orang_tua',
            'child_id' => $anak->id,
        ]);

        $token = $orangTua->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $orangTua,
                'token' => $token,
                'anak' => $anak,
            ]
        ], 201);
    }

    // Login untuk orang tua (SR-12: username+password, akun tidak lagi auto-create)
    public function loginOrangTua(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $orangTua = User::where('username', $request->username)
                    ->where('role', 'orang_tua')
                    ->first();

        if (!$orangTua || !Hash::check($request->password, $orangTua->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        $token = $orangTua->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $orangTua,
                'token' => $token,
                'anak' => $orangTua->child,
            ]
        ]);
    }

    // Login untuk admin (SR-11: username+password, bukan password tunggal)
    public function loginAdmin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = User::where('username', $request->username)
                    ->where('role', 'admin')
                    ->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password admin salah'
            ], 401);
        }

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $admin,
                'token' => $token,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }
}
