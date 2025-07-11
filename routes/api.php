<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    Route::middleware('auth:sanctum')->post('/update-password', [AuthController::class, 'updatePassword']);
});

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/', [UserController::class, 'update']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::get('/', [UserController::class, 'index']);
});
