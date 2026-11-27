<?php

use App\Http\Middleware\AssignCorrelationId;
use App\Http\Middleware\EnsureOrganizationConfigured;
use App\Http\Middleware\EnsureUserCanSignIn;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignCorrelationId::class);
        $middleware->alias(['organization.configured' => EnsureOrganizationConfigured::class]);
        $middleware->web(append: [EnsureUserCanSignIn::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
