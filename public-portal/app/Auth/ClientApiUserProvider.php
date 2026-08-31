<?php

namespace App\Auth;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Http;

/**
 * Section 3 / Michael, 2026-08-19: a UserProvider with no local password
 * table at all -- mirrors StaffApiUserProvider exactly, for Client
 * logins (Individual or Organization).
 *
 * Login (retrieveByCredentials): the entered email/password are used
 * DIRECTLY as HTTP Basic Auth against the Java API's GET /auth/me. If
 * Java accepts them (200) AND the match is specifically a "CLIENT" (not
 * a "STAFF"), the credentials are valid and we get back exactly who
 * they are, in one round trip -- no password is ever stored or hashed
 * on the Laravel side. The type check matters: /auth/me now accepts
 * BOTH staff and client credentials, so without it, a staff member's
 * own username/password would also authenticate successfully through
 * THIS provider -- which would be harmless on its own, but is still
 * the wrong identity type to accept here, so it's rejected on principle
 * the same way a client is rejected on the staff side.
 *
 * Session re-hydration (retrieveById): every subsequent request re-fetches
 * the client record via ComplianceEngineClient, which authenticates as
 * Laravel's own SERVICE ACCOUNT (config('services.compliance_engine')) --
 * not the client's own password, which we don't have anymore after the
 * login request completes, correctly.
 */
class ClientApiUserProvider implements UserProvider
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        try {
            $data = $this->engine->getClient((int) $identifier);
            return ClientPrincipal::fromApiResponse($data);
        } catch (\Throwable $e) {
            return null; // client gone/unreachable -- treat as logged out
        }
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null; // remember-me not supported for client logins
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // intentionally a no-op -- no remember-me support
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;
        if (!$email || !$password) {
            return null;
        }

        $baseUrl = config('services.compliance_engine.base_url');

        $response = Http::baseUrl($baseUrl)
            ->withBasicAuth($email, $password)
            ->timeout(config('services.compliance_engine.timeout'))
            ->get('/auth/me');

        if ($response->failed()) {
            return null; // invalid credentials, or Java unreachable -- either way, no login
        }

        // See class docblock -- only a "CLIENT" match is a valid client
        // login here; a "STAFF" match is rejected outright.
        if (($response->json('type') ?? null) !== 'CLIENT') {
            return null;
        }

        return ClientPrincipal::fromApiResponse($response->json());
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
