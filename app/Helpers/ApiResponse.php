<?php
namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = [], $message = 'Success', $status = 200): JsonResponse
{
    return response()->json([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $status);
}

    public static function error($message = 'Something went wrong', $errors = [], $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function paginate($collection, $message = 'Success', $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $collection,
        ], $status);
    }
}