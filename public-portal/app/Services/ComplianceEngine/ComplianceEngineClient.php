<?php

namespace App\Services\ComplianceEngine;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Thin wrapper around the Compliance Engine's internal API
 * (api-contract/openapi.yaml). This is the ONLY way the public
 * site/portal touches business domain data -- no direct database access
 * from PHP (Section 2).
 *
 * Method names and payload shapes mirror the OpenAPI paths 1:1 so the
 * contract stays the single source of truth; if the API changes, this is
 * the one place PHP needs to catch up.
 */
class ComplianceEngineClient
{
    protected function http(): PendingRequest
    {
        return Http::baseUrl(config('services.compliance_engine.base_url'))
            ->timeout(config('services.compliance_engine.timeout'))
            ->acceptJson();
    }

    // -----------------------------------------------------------------
    // Sessions (Section 3, 3a, 4c, 4d, 4e)
    // -----------------------------------------------------------------

    /** GET /sessions -- Public Calendar site listing (Section 4e), filterable. */
    public function listSessions(array $filters = []): array
    {
        return $this->unwrap($this->http()->get('/sessions', $filters));
    }

    /** GET /sessions/{id} -- session detail page (Section 4d). */
    public function getSession(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}"));
    }

    /** GET /sessions/{id}/publish-readiness -- Section 4c's Publish toggle gate. */
    public function isSessionReadyToPublish(int $sessionId): bool
    {
        $data = $this->unwrap($this->http()->get("/sessions/{$sessionId}/publish-readiness"));
        return (bool) ($data['readyToPublish'] ?? false);
    }

    /** POST /sessions/{id}/copy-forward -- "Copy Forward 6 Months" (Section 4c). */
    public function copySessionForward(int $sessionId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/copy-forward"));
    }

    /**
     * PATCH /sessions/{id}/publish -- Section 4c. Throws (via unwrap's
     * RuntimeException) on a 422 if required fields aren't complete yet;
     * callers should check isSessionReadyToPublish() first for a friendly
     * UI state rather than relying on catching the error.
     */
    public function publishSession(int $sessionId): array
    {
        return $this->unwrap($this->http()->patch("/sessions/{$sessionId}/publish"));
    }

    /** GET /sessions/{id}/authorized-clients (Section 3/4). */
    public function getAuthorizedClients(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/authorized-clients"));
    }

    /**
     * POST /sessions/{id}/authorized-clients -- authorize an outside
     * client on a Semi-Private session. The API returns 409 if the
     * session is Private (Section 4) -- callers should catch
     * ComplianceEngineConflictException and surface a friendly message
     * rather than a generic error.
     *
     * @param  int|null  $addedByStaffId  null when the host client is
     *                                    self-authorizing via the portal.
     */
    public function authorizeOutsideClient(int $sessionId, int $clientId, ?int $addedByStaffId = null): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/authorized-clients", [
            'clientId' => $clientId,
            'addedByStaffId' => $addedByStaffId,
        ]));
    }

    /** GET /sessions/{id}/comments (Section 4c). */
    public function getSessionComments(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/comments"));
    }

    /** POST /sessions/{id}/comments -- write-once, no update/delete exists on the API. */
    public function addSessionComment(int $sessionId, string $commentType, string $text, int $authorId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/comments", [
            'commentType' => $commentType,
            'text' => $text,
            'authorId' => $authorId,
        ]));
    }

    /** GET /sessions/{id}/roster -- Section 4f. Company/enrollment-date/payment-status only populate for Public/VR. */
    public function getRoster(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/roster"));
    }

    /** GET /sessions/{id}/summary-email-readiness -- gates the "Send Summary Email" button (Section 4g). */
    public function isSummaryEmailReady(int $sessionId): bool
    {
        $data = $this->unwrap($this->http()->get("/sessions/{$sessionId}/summary-email-readiness"));
        return (bool) ($data['ready'] ?? false);
    }

    /** POST /sessions/{id}/send-summary-email -- Section 4g. Callers should check isSummaryEmailReady() first. */
    public function sendSummaryEmail(int $sessionId): void
    {
        $this->unwrap($this->http()->post("/sessions/{$sessionId}/send-summary-email"));
    }

    // -----------------------------------------------------------------
    // Enrollments (Section 3, 4f, 4g, 7)
    // -----------------------------------------------------------------

    /** POST /enrollments -- also triggers QuickBooks invoice creation server-side (Section 7). */
    public function createEnrollment(int $studentId, int $clientId, int $sessionId): array
    {
        return $this->unwrap($this->http()->post('/enrollments', [
            'studentId' => $studentId,
            'clientId' => $clientId,
            'sessionId' => $sessionId,
        ]));
    }

    /** PATCH /enrollments/{id}/roster-status -- primarily for staff setting DNC/DNA (Section 4f). */
    public function updateRosterStatus(int $enrollmentId, string $rosterStatus): array
    {
        return $this->unwrap($this->http()->patch("/enrollments/{$enrollmentId}/roster-status", [
            'rosterStatus' => $rosterStatus,
        ]));
    }

    // -----------------------------------------------------------------
    // Certifications (Section 3b)
    // -----------------------------------------------------------------

    /** GET /certification-runs/{id}/split-run-eligibility (Section 3b, White-only). */
    public function getSplitRunEligibility(int $runId): string
    {
        $data = $this->unwrap($this->http()->get("/certification-runs/{$runId}/split-run-eligibility"));
        return $data['result'];
    }

    // -----------------------------------------------------------------
    // Clients & Inquiries (Section 3, 3c)
    // -----------------------------------------------------------------

    /** GET /clients/{id}/students -- Section 4e "Manage their employees." */
    public function listClientEmployees(int $clientId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}/students"));
    }

    /** POST /clients/{id}/students */
    public function addClientEmployee(int $clientId, string $name, string $phone, string $email): array
    {
        return $this->unwrap($this->http()->post("/clients/{$clientId}/students", [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]));
    }

    /** GET /clients?q= -- search, used by staff-facing admin screens. */
    public function searchClients(string $query): array
    {
        return $this->unwrap($this->http()->get('/clients', ['q' => $query]));
    }

    /**
     * POST /inquiries -- the "New Client Account" public form (Section 3c).
     * Deliberately does NOT create a Client -- lands as a pending Inquiry
     * for staff to review and convert manually (security decision against
     * bot/bad-actor abuse of a public form).
     */
    public function submitInquiry(array $inquiryData): array
    {
        return $this->unwrap($this->http()->post('/inquiries', $inquiryData));
    }

    // -----------------------------------------------------------------
    // VR (Section 3a, 4b)
    // -----------------------------------------------------------------

    /** POST /vr/token-blocks -- per-transaction pricing, override-aware server-side (Section 4b). */
    public function purchaseVrTokenBlock(int $clientId, int $tokensPurchased): array
    {
        return $this->unwrap($this->http()->post('/vr/token-blocks', [
            'clientId' => $clientId,
            'tokensPurchased' => $tokensPurchased,
        ]));
    }

    // -----------------------------------------------------------------

    /**
     * @throws ComplianceEngineConflictException on 409 (e.g. Private
     *         session outside-authorization attempt, Section 4)
     * @throws RuntimeException on any other non-2xx response
     */
    protected function unwrap(Response $response): array
    {
        if ($response->status() === 409) {
            throw new ComplianceEngineConflictException($response->json('error') ?? 'Conflict', $response->json());
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "Compliance Engine call failed [{$response->status()}]: {$response->body()}"
            );
        }

        return $response->json() ?? [];
    }
}
