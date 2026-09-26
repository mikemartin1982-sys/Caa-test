<?php

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
        // An unauthenticated visitor to a protected route now gets sent
        // to the RIGHT login page based on which area they were trying
        // to reach -- /admin/* -> staff login, everything else -> the
        // new client login (Michael, 2026-08-19). Without this, Laravel's
        // default behavior sends everyone to a route literally named
        // "login", which was hardcoded to always forward to admin.login
        // -- fine when staff was the only guard, wrong now that 'client'
        // is a second, separate one.
       $middleware->redirectGuestsTo(function ($request) {
    // Laravel's is('admin/*') requires a slash *and* something after it,
    // so it never matched the bare /admin path itself -- found live,
    // 2026-08-22. is() accepts multiple patterns and matches if any of
    // them fit, so this covers both.
    return $request->is('admin', 'admin/*') ? route('admin.login') : route('account.login');
});
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
