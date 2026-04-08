<?php

namespace App\Http\Controllers\Concerns;

trait ApiErrorResponse
{
    protected function errorResponse(int $status, string $code, string $message, array $errors = [])
    {
        $body = [
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
