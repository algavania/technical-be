<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GlobalErrorHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            Log::error('Error occurred: ' . $e->getMessage(), ['exception' => $e, 'line' => $e->getLine(), 'file' => $e->getFile()]);
            $httpCode = $e->getCode() ?: 500;
            // check if http code is valid
            if ($httpCode < 100 || $httpCode >= 600) {
                $httpCode = 500;
            }

            return ResponseHelper::createResponse(false, $e->getMessage() ?? 'An unexpected error occurred', null, null, $httpCode);
        }
    }
}
