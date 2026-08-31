<?php
 
namespace App\Auth;
 
use Illuminate\Contracts\Auth\Authenticatable;
 
/**
 * Section 3 / Michael, 2026-08-19: represents a logged-in client
 * (Individual or Organization) -- general prospective-client account
 * creation, independent of VR vs. traditional testing. Deliberately NOT
 * backed by a local Laravel database table, mirroring StaffPrincipal --
 * Client identity lives entirely in the Compliance Engine (Java). This
 * class just carries whatever the Java API returned, for the duration
 * of the session.
 *
 * No password is ever stored here, locally or otherwise -- see
 * ClientApiUserProvider, which verifies credentials against the Java
 * API directly rather than checking a local hash.
 */
class ClientPrincipal implements Authenticatable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $clientType,
    ) {
    }
 
    public static function fromApiResponse(array $data): self
    {
        // Two different response shapes can reach here: /auth/me (used
        // at login) already has a computed "name" field, but GET
        // /clients/{id} (used for session re-hydration on every
        // subsequent request -- see retrieveById()) returns the raw
        // Client database record instead, which has firstName/lastName/
        // company but no single "name" key at all. Missing that
        // silently logged every client out on the very next request
        // after a successful login (found live, 2026-08-19) -- deriving
        // it here handles both shapes correctly rather than assuming
        // the caller always sends the same one.
        $name = $data['name'] ?? (
            ($data['clientType'] ?? null) === 'INDIVIDUAL'
                ? trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''))
                : ($data['company'] ?? '')
        );
 
        return new self(
            id: $data['id'],
            name: $name,
            email: $data['email'],
            clientType: $data['clientType'],
        );
    }
 
    public function isOrganization(): bool
    {
        return $this->clientType === 'ORGANIZATION';
    }
 
    public function isIndividual(): bool
    {
        return $this->clientType === 'INDIVIDUAL';
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
        return null; // remember-me not supported for client logins
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