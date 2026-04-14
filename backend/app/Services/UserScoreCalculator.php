<?php

namespace App\Services;

class UserScoreCalculator
{
    public function score(float $netProfit, float $completionRate, int $riskScoreAverage): float
    {
        $profitWeight = $netProfit * 100;
        $completionWeight = $completionRate * 20;
        $riskPenalty = $riskScoreAverage * 0.8;

        return round($profitWeight + $completionWeight - $riskPenalty, 2);
    }
}
