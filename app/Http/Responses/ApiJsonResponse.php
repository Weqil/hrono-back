<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiJsonResponse
{
    /**
     * Успешный ответ API: status, code, message, data (единый формат для всех ресурсов).
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function success(
        string $message,
        int $httpStatus = 200,
        ?array $data = null,
    ): JsonResponse {
        return response()->json([
            'status' => 'success',
            'code' => $httpStatus,
            'message' => $message,
            'data' => $data,
        ], $httpStatus);
    }
}
