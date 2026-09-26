<?php
 
namespace App\Auth;
 
use Illuminate\Contracts\Auth\Authenticatable;
 
/**
 * Section 3: represents a logged-in staff member. Deliberately NOT backed
 * by a local Laravel database table -- StaffUser identity lives entirely
 * in the Compliance Engine (Java). This class just carries whatever the
 * Java API returned, for the duration of the session.
 *
 * No password is ever stored here, locally or otherwise -- see
 * StaffApiUserProvider, which verifies credentials against the Java API
 * directly rather than checking a local hash.
 *
 * /auth/me (2026-08-19) now covers both StaffUser and Client logins in
 * one response, discriminated by a "type" field -- staffRole (renamed
 * from the old "role") is only populated for a STAFF match. See
 * ClientPrincipal for the equivalent client-side class.
 */
class StaffPrincipal implements Authenticatable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $username,
        public readonly string $role,
    ) {
    }
 
    public static function fromApiResponse(array $data): self
    {
        // Two different response shapes can reach here: /auth/me (used
        // at login) has a "staffRole" field, but GET /staff/{id} (used
        // for session re-hydration on every subsequent request -- see
        // retrieveById()) returns the raw StaffUser database record
        // instead, which has "role", not "staffRole". Missing that
        // silently logged every staff member back out on the very next
        // request after a successful login (found live, 2026-08-19,
        // introduced when /auth/me's field was renamed to disambiguate
        // staff vs. client responses) -- deriving it here handles both
        // shapes correctly rather than assuming the caller always sends
        // the same one. Same fix, same reasoning, as ClientPrincipal's
        // equivalent bug found earlier tonight.
        return new self(
            id: $data['id'],
            name: $data['name'],
            username: $data['username'],
            role: $data['staffRole'] ?? $data['role'],
        );
    }
 
    public function isComplianceAdministrator(): bool
    {
        return $this->role === 'COMPLIANCE_ADMINISTRATOR';
    }
 
    // --- Authenticatable contract ---
 
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }
 
    public function getAuthIdentifier(): int
    {
        return $this->id;
    }
 
    public function getAuthPasswordName(): ?string
    {
        return null;
    }
 
    public function getAuthPassword(): ?string
    {
        return null; // no local password, ever -- see class docblock
    }
 
    public function getRememberToken(): ?string
    {
        return null; // remember-me not supported for staff logins
    }
 
    public function setRememberToken($value): void
    {
        // intentionally a no-op
    }
 
    public function getRememberTokenName(): ?string
    {
        return null;
    }
}