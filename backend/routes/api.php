<?php

use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('token.auth')->group(function (): void {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::get('/dashboard', [UserController::class, 'dashboard']);

    Route::post('/ad/start', [AdController::class, 'start']);
    Route::post('/ad/complete', [AdController::class, 'complete']);

    Route::post('/claim-reward', [RewardController::class, 'claimReward']);
    Route::post('/withdraw', [RewardController::class, 'withdraw']);

    Route::get('/referrals', [ReferralController::class, 'index']);
    Route::post('/apply-code', [ReferralController::class, 'applyCode']);
});
