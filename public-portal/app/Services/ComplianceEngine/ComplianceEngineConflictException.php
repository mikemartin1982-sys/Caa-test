<?php

namespace App\Services\ComplianceEngine;

use RuntimeException;

/**
 * Thrown when the Compliance Engine returns 409 -- e.g. attempting to
 * authorize an outside client on a Private session (Section 4: Private is
 * structurally locked to the host, this is the actual enforcement point
 * surfacing back to the UI).
 */
class ComplianceEngineConflictException extends RuntimeException
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
