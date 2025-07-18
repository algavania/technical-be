<?php

use App\Helpers\ResponseHelper;
use App\Http\Middleware\GlobalErrorHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            GlobalErrorHandler::class,
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            // if path not api, return default error page
            

            $httpCode = $e->getCode() ?: 500;
            // check if http code is valid
            if ($httpCode < 100 || $httpCode >= 600) {
                $httpCode = 500;
            }

            return ResponseHelper::createResponse(
                false,
                $e->getMessage() ?? 'An unexpected error occurred',
                null,
                null,
                $httpCode
            );
        });
    })->create();
