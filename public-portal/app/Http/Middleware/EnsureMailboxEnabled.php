<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMailboxEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('mailbox.enabled'), 503, 'Client Mailbox has not been configured yet.');

        return $next($request);
    }
}
