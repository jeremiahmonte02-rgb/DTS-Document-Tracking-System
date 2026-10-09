<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'no-cache' => \App\Http\Middleware\PreventBackHistoryCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The /broadcasting/auth endpoint is called via XHR by the WebSocket
        // client, never by browser navigation. Render its denials as JSON so
        // a guest (or any denied subscriber) gets a clean 403 instead of the
        // HTML error page — whose layout assumes an authenticated user.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('broadcasting/*')) {
                return response()->json(['message' => 'This action is unauthorized.'], 403);
            }
        });
    })->create();
