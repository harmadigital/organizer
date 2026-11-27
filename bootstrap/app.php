<?php

use App\Http\Middleware\AssignCorrelationId;
use App\Http\Middleware\EnsureEventAccess;
use App\Http\Middleware\EnsureOrganizationConfigured;
use App\Http\Middleware\EnsureUserCanSignIn;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignCorrelationId::class);
        $middleware->alias([
            'organization.configured' => EnsureOrganizationConfigured::class,
            'event.access' => EnsureEventAccess::class,
        ]);
        $middleware->web(append: [EnsureUserCanSignIn::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rule violations found by actions answer AJAX in the same envelope as Form Requests.
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => false,
                'data' => $exception->validator->errors()->first(),
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], $exception->status);
        });
    })->create();
