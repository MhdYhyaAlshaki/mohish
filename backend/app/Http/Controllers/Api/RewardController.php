<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Services\RewardService;
use Illuminate\Http\Request;

class RewardController extends BaseApiController
{
    public function __construct(private readonly RewardService $rewardService)
    {
    }

    public function claimReward(Request $request)
    {
        try {
            return $this->success($this->rewardService->claimReward($request->user()));
        } catch (ApiException $exception) {
            return $this->fromApiException($exception);
        }
    }

    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
        ]);

        try {
            return $this->success($this->rewardService->requestWithdraw($request->user(), $validated['points']));
        } catch (ApiException $exception) {
            return $this->fromApiException($exception);
        }
    }
}
