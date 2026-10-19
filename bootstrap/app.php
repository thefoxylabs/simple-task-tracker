<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SecurityHeaders::class]);

        // Only answer to the domain in APP_URL, so emailed links can't be
        // pointed at another host (ignored when APP_ENV=local).
        $middleware->trustHosts();

        $middleware->alias([
            'installed' => EnsureInstalled::class,
            'active' => EnsureUserIsActive::class,
        ]);
        // Check for a fresh install before asking anyone to sign in.
        $middleware->prependToPriorityList(AuthenticatesRequests::class, EnsureInstalled::class);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
