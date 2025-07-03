<?php
namespace App\Helpers;

class ResponseHelper
{
    /**
     * Standardize the response structure.
     *
     * @param bool $status
     * @param string $message
     * @param mixed $data
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public static function createResponse($status = true, $message = '', $data = null, $errors = null, $code = null)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $code ?? ($status ? 200 : 500));
    }
}
