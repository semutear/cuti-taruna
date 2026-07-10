<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CutiController;
use App\Http\Controllers\Api\AdminController;

// Route publik (tanpa autentikasi) dengan rate limiter
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/orangtua', [AuthController::class, 'registerOrangTua']);
    Route::post('/login/taruna', [AuthController::class, 'loginTaruna']);
    Route::post('/login/orangtua', [AuthController::class, 'loginOrangTua']);
    Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
});

// Route yang memerlukan token Sanctum (autentikasi)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Cuti (resource controller)
    Route::apiResource('cuti', CutiController::class)->except(['create', 'edit']);

    // Route download tiket secara aman
    Route::get('/cuti/{id}/tiket', [CutiController::class, 'downloadTiket']);

    // Route khusus untuk approval dua tahap: orang tua (approve/reject), pengasuh (finalize/reject)
    Route::post('/cuti/{id}/approve', [CutiController::class, 'approve']);
    Route::post('/cuti/{id}/finalize', [CutiController::class, 'finalize']);
    Route::post('/cuti/{id}/reject', [CutiController::class, 'reject']);

    // Route admin
    Route::get('/admin/cuti', [AdminController::class, 'index']);
    Route::get('/admin/statistics', [AdminController::class, 'statistics']);
});
