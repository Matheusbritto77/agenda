<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

abstract class Controller
{
    protected function jsonSuccess(Request $request, string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge([
            'message' => $message,
        ], $data), $status);
    }

    protected function jsonValidationError(Request $request, ValidationException $exception, string $message = 'Os dados informados são inválidos.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $exception->errors(),
        ], 422);
    }

    protected function jsonError(Request $request, string $message, int $status = 500, array $data = []): JsonResponse
    {
        return response()->json(array_merge([
            'message' => $message,
        ], $data), $status);
    }

    protected function sanitizeText(mixed $value, bool $stripTags = true): ?string
    {
        $value = trim((string) $value);

        if ($stripTags) {
            $value = strip_tags($value);
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value === '' ? null : $value;
    }

    protected function sanitizeEmail(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }

    protected function reportThrowable(Throwable $throwable): void
    {
        report($throwable);
    }
}
