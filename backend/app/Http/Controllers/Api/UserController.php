<?php

namespace App\Http\Controllers\Api;

use App\Models\AdsLog;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class UserController extends BaseApiController
{
    public function profile()
    {
        $user = request()->user();

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'points' => $user->points,
            'referral_code' => $user->referral_code,
            'referred_by' => $user->referred_by,
        ]);
    }

    public function dashboard()
    {
        $user = request()->user();
        $today = Carbon::today();
        $start = Carbon::today()->subDays(6)->startOfDay();
        $end = Carbon::today()->endOfDay();

        $todayPoints = (int) Transaction::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->where('points', '>', 0)
            ->sum('points');

        $adsWatchedToday = AdsLog::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->whereDate('completed_at', $today)
            ->count();

        $rows = Transaction::query()
            ->selectRaw('DATE(created_at) as day, SUM(points) as points_sum')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('points', '>', 0)
            ->groupBy('day')
            ->pluck('points_sum', 'day');

        $weeklyPoints = [];
        for ($offset = 6; $offset >= 0; $offset--) {
            $day = Carbon::today()->subDays($offset)->toDateString();
            $weeklyPoints[] = (int) ($rows[$day] ?? 0);
        }

        return $this->success([
            'total_points' => (int) $user->points,
            'today_points' => $todayPoints,
            'ads_watched_today' => $adsWatchedToday,
            'weekly_points' => $weeklyPoints,
        ]);
    }
}
