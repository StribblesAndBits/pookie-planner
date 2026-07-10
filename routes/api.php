<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JulesDayController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UtilityController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::get('/color-preferences', [AuthController::class, 'colorPreferences']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Events
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);

    // Jules days
    Route::get('/jules-days', [JulesDayController::class, 'index']);
    Route::post('/jules-days', [JulesDayController::class, 'store']);
    Route::put('/jules-days/{id}', [JulesDayController::class, 'update']);
    Route::delete('/jules-days/{id}', [JulesDayController::class, 'destroy']);

    // Utilities
    Route::get('/utilities', [UtilityController::class, 'index']);
    Route::post('/utilities', [UtilityController::class, 'store']);
    Route::put('/utilities/{id}', [UtilityController::class, 'update']);
});
