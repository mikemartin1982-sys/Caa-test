<?php

namespace App\Services\ComplianceEngine;

use RuntimeException;

/**
 * Thrown when the Compliance Engine returns 403 -- e.g. a staff account
 * without the Compliance Administrator role attempting to create or
 * modify another staff account (2026-08-18, role enforcement added to
 * StaffUserController after a real gap was found via external code
 * review: the role existed in the data model but was never actually
 * checked anywhere).
 */
class ComplianceEngineForbiddenException extends RuntimeException
{
    protected array $payload;

    public function __construct(string $message, array $payload = [])
    {
        parent::__construct($message);
        $this->payload = $payload;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
