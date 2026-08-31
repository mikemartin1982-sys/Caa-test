<?php

namespace App\Providers;

use App\Auth\ClientApiUserProvider;
use App\Auth\StaffApiUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the 'staff-api' and 'client-api' auth providers (see
 * App\Auth\StaffApiUserProvider / ClientApiUserProvider) so config/auth.php's
 * 'staff' and 'client' guards can use them. Add this class to
 * bootstrap/providers.php's provider list (Laravel 11+) or
 * config/app.php's providers array (older structure) -- whichever your
 * generated skeleton uses.
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('staff-api', function ($app, array $config) {
            return $app->make(StaffApiUserProvider::class);
        });

        Auth::provider('client-api', function ($app, array $config) {
            return $app->make(ClientApiUserProvider::class);
        });
    }
}
