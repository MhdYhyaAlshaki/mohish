<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    protected function success(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    protected function error(string $code, string $message, int $status = 400, ?int $retryAfter = null): JsonResponse
    {
        $payload = [
            'code' => $code,
            'message' => $message,
        ];

        if ($retryAfter !== null) {
            $payload['retry_after'] = $retryAfter;
        }

        return response()->json($payload, $status);
    }

    protected function fromApiException(ApiException $exception): JsonResponse
    {
        return $this->error(
            $exception->apiCode(),
            $exception->getMessage(),
            $exception->status(),
            $exception->retryAfter(),
        );
    }
}
