<?php

use App\Http\Controllers\Admin\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'ensure.admin.role'])->prefix('admin/analytics')->group(function (): void {
    Route::get('/overview', [AnalyticsController::class, 'overview']);
    Route::get('/top-users', [AnalyticsController::class, 'topUsers']);
    Route::get('/profit-trend', [AnalyticsController::class, 'profitTrend']);
});
