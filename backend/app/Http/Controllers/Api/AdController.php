<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Services\RewardService;
use Illuminate\Http\Request;

class AdController extends BaseApiController
{
    public function __construct(private readonly RewardService $rewardService)
    {
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'ad_type' => ['nullable', 'string', 'max:24'],
            'device_fingerprint' => ['nullable', 'string', 'max:120'],
            'vpn_flag' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $this->rewardService->startAdSession(
                $request->user(),
                [
                    'ad_type' => $validated['ad_type'] ?? 'rewarded',
                    'device_fingerprint' => $validated['device_fingerprint'] ?? null,
                    'vpn_flag' => (bool) ($validated['vpn_flag'] ?? false),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            );

            return $this->success($result);
        } catch (ApiException $exception) {
            return $this->fromApiException($exception);
        }
    }

    public function complete(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
        ]);

        try {
            $result = $this->rewardService->completeAdSession($request->user(), $validated['session_id']);
            return $this->success($result);
        } catch (ApiException $exception) {
            return $this->fromApiException($exception);
        }
    }
}
