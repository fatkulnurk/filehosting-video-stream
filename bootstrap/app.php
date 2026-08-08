<?php

declare(strict_types=1);
use App\Exceptions\Directories\DirectoryNotFoundException;
use App\Exceptions\Files\FileNotFoundException;
use App\Http\Middleware\ApiKeyRateLimiter;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\UserActionRateLimiter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'api.key' => ApiKeyRateLimiter::class,
            'user.throttle' => UserActionRateLimiter::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (HttpException $e, $request) {
            if ($request->expectsJson()) {
                return null; // let Laravel render JSON for API requests
            }

            if ($e->getStatusCode() >= 400 && $e->getStatusCode() < 500) {
                return Inertia::render('Error', [
                    'status' => $e->getStatusCode(),
                    'message' => $e->getMessage(),
                ])->toResponse($request)->setStatusCode($e->getStatusCode());
            }

            return null;
        });

        // Expected not-found errors should not spam the logs.
        $exceptions->dontReport([
            FileNotFoundException::class,
            DirectoryNotFoundException::class,
        ]);
    })->create();
