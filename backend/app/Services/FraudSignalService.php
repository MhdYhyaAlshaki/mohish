<?php

namespace App\Services;

use App\Models\AdsLog;
use Illuminate\Support\Carbon;

class FraudSignalService
{
    public function riskScore(?string $deviceFingerprint, bool $vpnFlag): int
    {
        $score = 0;

        if ($vpnFlag) {
            $score += 40;
        }

        if ($deviceFingerprint) {
            $sharedUsers = AdsLog::query()
                ->where('device_fingerprint', $deviceFingerprint)
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->distinct('user_id')
                ->count('user_id');

            if ($sharedUsers > 1) {
                $score += min(50, $sharedUsers * 10);
            }
        }

        return min(100, $score);
    }
}
