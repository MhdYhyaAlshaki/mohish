<?php

return [
    'daily_cap' => (int) env('REWARD_DAILY_CAP', 12),
    'cooldown_seconds' => (int) env('REWARD_COOLDOWN_SECONDS', 30),
    'points_per_ad' => (int) env('REWARD_POINTS_PER_AD', 10),
    'referral_percent' => (int) env('REWARD_REFERRAL_PERCENT', 10),
    'session_expiry_minutes' => (int) env('REWARD_SESSION_EXPIRY_MINUTES', 10),
    'daily_claim_points' => (int) env('REWARD_DAILY_CLAIM_POINTS', 5),
    'min_withdraw_points' => (int) env('REWARD_MIN_WITHDRAW_POINTS', 1000),
];
