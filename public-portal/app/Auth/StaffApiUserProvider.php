<?php

namespace App\Auth;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Http;

/**
 * Section 3: a UserProvider with no local password table at all.
 *
 * Login (retrieveByCredentials): the entered username/password are used
 * DIRECTLY as HTTP Basic Auth against the Java API's GET /auth/me. If
 * Java accepts them (200), the credentials are valid and we get back
 * exactly who they are, in one round trip -- no password is ever stored
 * or hashed on the Laravel side.
 *
 * Session re-hydration (retrieveById): every subsequent request re-fetches
 * the staff record via ComplianceEngineClient, which authenticates as
 * Laravel's own SERVICE ACCOUNT (config('services.compliance_engine')) --
 * not the staff member's password, which we don't have anymore after
 * the login request completes, correctly.
 */
class StaffApiUserProvider implements UserProvider
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        try {
            $data = $this->engine->getStaffUser((int) $identifier);
            return StaffPrincipal::fromApiResponse($data);
        } catch (\Throwable $e) {
            return null; // staff user gone/unreachable -- treat as logged out
        }
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null; // remember-me not supported for staff logins
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // intentionally a no-op -- no remember-me support
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        if (!$username || !$password) {
            return null;
        }

        $baseUrl = config('services.compliance_engine.base_url');

        $response = Http::baseUrl($baseUrl)
            ->withBasicAuth($username, $password)
            ->timeout(config('services.compliance_engine.timeout'))
            ->get('/auth/me');

        if ($response->failed()) {
            return null; // invalid credentials, or Java unreachable -- either way, no login
        }

        // /auth/me (2026-08-19) now accepts BOTH StaffUser and Client
        // credentials -- valid credentials alone are no longer enough
        // here. Without this check, a Client's own email/password would
        // successfully authenticate through THIS provider too, letting
        // them into the staff-only /admin/* area. Only a "STAFF" match
        // is a valid staff login; a "CLIENT" match is rejected outright.
        if (($response->json('type') ?? null) !== 'STAFF') {
            return null;
        }

        return StaffPrincipal::fromApiResponse($response->json());
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        // retrieveByCredentials() above already proved the credentials
        // valid via a live call to Java -- nothing further to check here.
        return true;
    }

    /**
     * Present in newer Laravel UserProvider contracts for password
     * rehashing support. Always false here -- there is no local password
     * hash to ever rehash, by design.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // intentionally a no-op
    }
}
