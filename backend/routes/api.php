<?php

use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AdPlacementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ── Public ─────────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::prefix('v1')->group(function (): void {
    Route::get('/config',      [ConfigController::class, 'index']);
    Route::get('/ad',          [AdPlacementController::class, 'resolve']);
    Route::post('/ad/click',   [AdPlacementController::class, 'recordClick']);
});

// ── Authenticated ──────────────────────────────────────────────────────────────
Route::middleware('token.auth')->group(function (): void {

    // User
    Route::get('/profile',   [UserController::class, 'profile']);
    Route::get('/dashboard', [UserController::class, 'dashboard']);

    // Legacy rewarded-ad flow (start / complete)
    Route::post('/ad/start',    [AdController::class, 'start']);
    Route::post('/ad/complete', [AdController::class, 'complete']);

    // Rewards & wallet
    Route::post('/claim-reward', [RewardController::class, 'claimReward']);
    Route::post('/withdraw',     [RewardController::class, 'withdraw']);

    // Referrals
    Route::get('/referrals',    [ReferralController::class, 'index']);
    Route::post('/apply-code',  [ReferralController::class, 'applyCode']);

    // ── Ad Placement Engine (v1) ────────────────────────────────────────────
    Route::prefix('v1')->group(function (): void {
        Route::get('/ad/stats',       [AdPlacementController::class, 'platformStats']);
        Route::post('/ad/impression', [AdPlacementController::class, 'recordImpression']);
        Route::post('/ad/reward',     [AdPlacementController::class, 'recordReward']);
    });
});