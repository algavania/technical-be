<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class GlobalExceptionHandler extends ExceptionHandler
{
    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        // Log the exception for debugging
        parent::report($exception);  // Make sure to call the parent report to keep the default behavior
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        // If path is not api, return default error page
        if (!$request->is('api/*')) {
            return parent::render($request, $exception);
        }
        // Handling ModelNotFoundException
        if ($exception instanceof ModelNotFoundException) {
            // Log and return a JSON response for ModelNotFoundException
            Log::error('Model not found: ' . $exception->getMessage(), ['exception' => $exception]);

            return response()->json([
                'status' => false,
                'message' => 'Resource not found.',
                'data' => null,
                'errors' => [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ]
            ], Response::HTTP_NOT_FOUND);
        }

        // For other exceptions
        if ($request->expectsJson()) {
            Log::error('Error occurred: ' . $exception->getMessage(), ['exception' => $exception]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred.',
                'data' => null,
                'errors' => [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Fallback to default handling for other types (e.g., HTML error pages)
        return parent::render($request, $exception);
    }
}
