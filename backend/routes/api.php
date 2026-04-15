<?php

use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AdPlacementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ── Public ─────────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

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
    //
    // GET  /api/v1/ad?placement=home_banner&platform=android&country=AE
    //   → Returns the best ad for that placement + platform.
    //   → Backend scores campaigns by (priority × 1000) + (network_cpm × 500).
    //   → iOS campaigns naturally score higher because ios_cpm_estimate > android_cpm_estimate.
    //
    // POST /api/v1/ad/click  { impression_id }
    //   → Records a click against a previously served impression.
    //
    // GET  /api/v1/ad/stats?days=7  (admin)
    //   → Revenue + CTR breakdown by platform.
    Route::prefix('v1')->group(function (): void {
        Route::get('/ad',          [AdPlacementController::class, 'resolve']);
        Route::post('/ad/click',   [AdPlacementController::class, 'recordClick']);
        Route::get('/ad/stats',    [AdPlacementController::class, 'platformStats']);
    });
});