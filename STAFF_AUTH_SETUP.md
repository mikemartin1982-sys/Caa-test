# Wiring the Staff Guard into config/auth.php

The skeleton's `config/auth.php` ships with defaults for the client-facing
`web` guard, which you should keep untouched. Add the following alongside
what's already there — don't replace the whole file.

## 1. In the `'guards'` array, add:

```php
'staff' => [
    'driver' => 'session',
    'provider' => 'staff-api',
],
```

So it reads something like:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'staff' => [
        'driver' => 'session',
        'provider' => 'staff-api',
    ],
],
```

## 2. In the `'providers'` array, add:

```php
'staff-api' => [
    'driver' => 'staff-api',
],
```

So it reads something like:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'staff-api' => [
        'driver' => 'staff-api',
    ],
],
```

Note: `'driver' => 'staff-api'` here is just a config-level label. The
actual `staff-api` provider driver is registered in code by
`App\Providers\AuthServiceProvider` (see below), which is what makes this
label resolve to `App\Auth\StaffApiUserProvider`.

## 3. Register `AuthServiceProvider`

Laravel 11+ uses `bootstrap/providers.php` instead of the old
`config/app.php` providers array. Check which one your generated skeleton
has (`php artisan --version` will tell you the Laravel version if you're
unsure which structure applies), and add the class there:

**If `bootstrap/providers.php` exists** (Laravel 11+):
```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class, // add this line
];
```

**If it's the older `config/app.php` structure:**
```php
'providers' => [
    // ... existing providers ...
    App\Providers\AuthServiceProvider::class,
],
```

## Why this shape, not a simpler approach

Laravel's `Auth::provider()` registration (inside `AuthServiceProvider`)
is what actually maps the `'staff-api'` driver name to
`StaffApiUserProvider`. The `config/auth.php` entries just tell Laravel
"the `staff` guard uses whichever provider is registered under the name
`staff-api`" — the two files work together, and both are needed. This
mirrors how Laravel's own Eloquent-backed `users` provider is wired,
just pointed at a custom provider instead of a database table.
