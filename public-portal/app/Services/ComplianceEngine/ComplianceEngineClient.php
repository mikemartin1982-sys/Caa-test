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
    /**
     * GET/POST/DELETE /integrations/qbo/connection -- Michael,
     * 2026-08-25, QBO integration layer 3. The actual OAuth exchange
     * with Intuit happens in QboController (this file's caller), not
     * here -- these three methods just persist/read/clear the RESULT
     * of that exchange on the Java side, where QboConnection (the
     * encrypted token storage) actually lives.
     */
    public function getQboConnectionStatus(): array
    {
        return $this->unwrap($this->http()->get('/integrations/qbo/connection'));
    }

    public function storeQboConnection(array $payload): array
    {
        return $this->unwrap($this->http()->post('/integrations/qbo/connection', $payload));
    }

    public function disconnectQbo(): void
    {
        $this->unwrap($this->http()->delete('/integrations/qbo/connection'));
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl(config('services.compliance_engine.base_url'))
            ->timeout(config('services.compliance_engine.timeout'))
            ->withBasicAuth(
                config('services.compliance_engine.username'),
                config('services.compliance_engine.password'),
            )
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

    /**
     * GET /clients/{clientId}/current-enrollments -- Michael, 2026-08-24,
     * client-facing portal's own "Current Enrollments" page.
     */
    public function listCurrentEnrollments(int $clientId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}/current-enrollments"));
    }

    /**
     * GET /sessions/calendar -- staff-facing calendar, ALL school types
     * (unlike the public calendar, which is Public+published only).
     *
     * Michael, 2026-08-29 -- staff calendar, Phase 1: startDate/endDate
     * are now required on the Java side (previously unscoped, loading
     * every session ever created -- the exact performance issue Phase 1
     * fixed). schoolType stays optional, same as before. Response shape
     * also changed -- no longer a plain array of sessions, now
     * {entries: [...], availability: [...]}, one entry per actual
     * scheduled day (a multi-day session shows once per day it spans),
     * not one per session.
     */
    public function getSessionCalendar(string $startDate, string $endDate, ?string $schoolType = null): array
    {
        $params = ['startDate' => $startDate, 'endDate' => $endDate];
        if ($schoolType) {
            $params['schoolType'] = $schoolType;
        }
        return $this->unwrap($this->http()->get('/sessions/calendar', $params));
    }

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop).
     * $changes is a plain array of ['sessionId' => ..., 'slot' => ...,
     * 'newValueId' => ...] -- newValueId is genuinely nullable (means
     * "unassign this slot," not "leave unchanged"), so this is passed
     * straight through as an array rather than typed parameters here.
     */
    public function saveCalendarAssignments(array $changes): array
    {
        return $this->unwrap($this->http()->post('/sessions/calendar/assignments', $changes));
    }

    /** GET /sessions/{id} -- session detail page (Section 4d). */
    public function getSession(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}"));
    }

    /**
     * Michael, 2026-09-03 -- found live: no Laravel-side method for
     * this existed at all -- only the Java endpoint itself
     * (POST /sessions/{sessionId}/generate-qbo-invoice) was ever
     * built, never actually wired to any real UI. PRIVATE only --
     * SchoolType.SEMI_PRIVATE genuinely isn't supported by this same
     * endpoint at all yet (its own billing system is a separate,
     * still-open backlog item).
     */
    public function generatePrivateSessionInvoice(int $sessionId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/generate-qbo-invoice"));
    }

    /**
     * Michael, 2026-09-04 -- session close-out billing redesign. The
     * real, second, genuinely separate step from
     * generatePrivateSessionInvoice() above -- actually sends the
     * already-generated invoice to the client.
     */
    public function sendInvoice(int $sessionId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/send-invoice"));
    }

    /**
     * Michael, 2026-09-04 -- Public Certificate Lookup, matching the
     * real, existing DIBs feature. Calls the genuinely public Java
     * endpoint directly -- safe even though http() always attaches a
     * real, staff-level auth header, since the Java side's own
     * permitAll() rule doesn't require its absence, only doesn't
     * require its presence either.
     */
    public function publicCertLookup(string $studentNumber, string $lastName): array
    {
        return $this->unwrap($this->http()->get('/public/certs', ['studentNumber' => $studentNumber, 'lastName' => $lastName]));
    }

    public function publicStudentNumberLookup(string $email, string $lastName): array
    {
        return $this->unwrap($this->http()->get('/public/certs/lookup-student-number', ['email' => $email, 'lastName' => $lastName]));
    }

    /**
     * Michael, 2026-09-03 -- real, existing Java endpoint
     * (POST /sessions/{sessionId}/sync-qbo-class) only ever wired for
     * automatic use during publish() -- no Laravel-side method existed
     * to call it directly at all. Manual retry for when the automatic
     * sync failed silently (e.g. missing city/state/date at publish
     * time) -- the whole reason a visible warning + retry button on
     * the Session Details page is worth having, rather than requiring
     * someone to know to check the server console.
     */
    public function syncQboClass(int $sessionId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/sync-qbo-class"));
    }

    /**
     * Michael, 2026-09-03 -- Bulk Enroll, Private/Semi-Private support.
     * Real, current field headcount + the session's own negotiated
     * threshold/overage rate, for the "warn before proceeding"
     * confirmation screen -- not a per-student price breakdown, which
     * doesn't apply to Private's own flat, session-level billing.
     */
    public function fieldHeadcount(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/field-headcount"));
    }

    /**
     * Michael, 2026-08-25 -- Section 4c reporting extension ("session
     * linking"): a lightweight summary of the session this one was
     * copied forward from, if any -- deliberately NOT the full,
     * unwrap()-style GET /sessions/{id} above, since Session's own
     * copiedFromSession relationship has no serialization safeguard
     * at all and would return an increasingly deep, fully-nested chain
     * for a session copied forward repeatedly over years.
     */
    public function getCopiedFromSummary(int $sessionId): ?array
    {
        $result = $this->unwrap($this->http()->get("/sessions/{$sessionId}/copied-from"));
        return $result !== [] ? $result : null;
    }

    /**
     * Michael, 2026-08-25 -- Section 4a extension, piece 2/3B: the
     * Brevo retention-gap target list, for real Excel export -- not
     * a UI page (confirmed with Michael, outputs via Excel, consumed
     * that way rather than viewed live in the app).
     */
    public function getBrevoTargetList(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/reporting/brevo-target-list/{$sessionId}"));
    }

    /** GET /sessions/{id}/days -- a session's scheduled days, in order. Used to gate the "Send Confirmation Email" button on at least one being present. */
    public function getSessionDays(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/days"));
    }

    /**
     * POST /sessions/{id}/days -- add a scheduled day (Michael, 2026-08-19,
     * found live during testing: the "Send Confirmation Email" gate
     * correctly said a date was missing, but there was genuinely no way
     * to add one anywhere on this page). dayNumber/startTime/endTime
     * are optional -- the Java side defaults dayNumber to 1 and the
     * times to 8:00 AM-5:00 PM if omitted.
     */
    public function addSessionDay(int $sessionId, string $sessionDate, ?int $dayNumber = null, ?string $startTime = null, ?string $endTime = null): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/days", [
            'sessionDate' => $sessionDate,
            'dayNumber' => $dayNumber,
            'startTime' => $startTime,
            'endTime' => $endTime,
        ]));
    }

    /**
     * DELETE /sessions/{id}/days/{dayId} -- Michael, 2026-08-19, found
     * live during testing: days could be added but never removed.
     */
    public function deleteSessionDay(int $sessionId, int $dayId): array
    {
        return $this->unwrap($this->http()->delete("/sessions/{$sessionId}/days/{$dayId}"));
    }

    /**
     * PATCH /sessions/{id} -- general field edits across every Session
     * Details section (GENERAL/FIELD/TEAM/SCHOOL INFO/PRIVATE SCHOOL BID
     * AND INVOICE). $fields is sent as-is; only non-null server-side
     * fields get applied (see SessionController.update in the API).
     */
    public function updateSession(int $sessionId, array $fields): array
    {
        return $this->unwrap($this->http()->patch("/sessions/{$sessionId}", $fields));
    }

    /** POST /sessions/{id}/verify-info -- atomically sets verified + who + when (Section 4c). */
    public function verifySessionInfo(int $sessionId, int $verifiedByStaffId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/verify-info", [
            'verifiedByStaffId' => $verifiedByStaffId,
        ]));
    }

    /** GET /sessions/{id}/notified-clients -- "Clients to be Notified" (Section 4c). */
    public function getNotifiedClients(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/notified-clients"));
    }

    public function addNotifiedClient(int $sessionId, int $clientId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/notified-clients", [
            'clientId' => $clientId,
        ]));
    }

    public function removeNotifiedClient(int $sessionId, int $clientId): void
    {
        $this->http()->delete("/sessions/{$sessionId}/notified-clients/{$clientId}");
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
     * PUT /sessions/{id}/host -- sets/REPLACES the host client on a
     * Private/Semi-Private/VTCA/Proposed session. This is the actual
     * way to set a Client for a Private-type session, matching DIBs'
     * "Company Name (Client)" field in GENERAL.
     */
    public function setSessionHost(int $sessionId, int $clientId, ?int $addedByStaffId = null): array
    {
        return $this->unwrap($this->http()->put("/sessions/{$sessionId}/host", [
            'clientId' => $clientId,
            'addedByStaffId' => $addedByStaffId,
        ]));
    }

    /**
     * POST /sessions/{id}/send-confirmation-email -- staff-triggered,
     * exact wording confirmed with Michael, 2026-08-19. Requires a host
     * client already set (see setSessionHost() above) with an email on
     * file, and at least one scheduled SessionDay.
     */
    /**
     * POST /sessions/{id}/send-confirmation-email -- staff-triggered,
     * exact wording confirmed with Michael, 2026-08-19. Requires a host
     * client already set (see setSessionHost() above) with an email on
     * file, and at least one scheduled SessionDay. sendingStaffId
     * (same day, real correction from Michael) -- the confirmation's
     * signature and reply-to must reflect whoever's actually sending
     * it, not a hardcoded person; the caller must pass the CURRENTLY
     * logged-in staff member's own id.
     */
    public function sendSessionConfirmationEmail(int $sessionId, int $sendingStaffId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/send-confirmation-email", [
            'sendingStaffId' => $sendingStaffId,
        ]));
    }

    /**
     * POST /sessions/{id}/generate-bid -- matches a real bid template
     * Michael provided, 2026-08-19. Requires a host client, a scheduled
     * day, and privateCost/fieldTest/selfPacedLecturePrice all set.
     * sendingStaffId -- same reasoning as sendSessionConfirmationEmail()
     * above: the bid's signature/contact info must reflect whoever's
     * actually generating it.
     */
    public function generateBid(int $sessionId, int $sendingStaffId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/generate-bid", [
            'sendingStaffId' => $sendingStaffId,
        ]));
    }

    /**
     * GET /sessions/{id}/bid-pdf -- returns the raw PDF bytes (not
     * JSON, so this bypasses unwrap()) for Laravel to proxy back to the
     * browser. The Java API requires the shared service-account basic
     * auth, which a browser can't provide directly -- this is why the
     * browser can never link straight to the Java endpoint itself.
     */
    public function getBidPdf(int $sessionId): ?string
    {
        $response = $this->http()->get("/sessions/{$sessionId}/bid-pdf");
        if ($response->status() === 404) {
            return null;
        }
        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch bid PDF: ' . $response->status());
        }
        return $response->body();
    }

    /**
     * GET /sessions/{id}/resolved-po -- shows which PO would actually
     * apply to this session, WITHOUT generating an invoice (Michael,
     * 2026-08-23) -- there's no real QuickBooks connection yet, so
     * actual invoice generation is deliberately not built. This is
     * just the resolution logic and pathing, ready for whenever
     * invoicing is.
     */
    public function getResolvedPo(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/resolved-po"));
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

    /** POST /enrollments -- QuickBooks invoice creation (Section 7) is NOT implemented server-side yet; see EnrollmentController's own comment. */
    /**
     * Michael, 2026-08-25 -- Client Portal Enroll rebuild. components
     * defaults to 'FIELD_ONLY' so the existing admin caller
     * (ManualEnrollController) doesn't need to change yet -- that's
     * tomorrow's own piece of this rebuild. Accepts 'LECTURE_ONLY',
     * 'FIELD_ONLY', or 'BOTH' -- see EnrollmentController.create()'s
     * own docblock (Java side) for what each does.
     */
    public function createEnrollment(int $studentId, int $clientId, int $sessionId, string $components = 'FIELD_ONLY'): array
    {
        return $this->unwrap($this->http()->post('/enrollments', [
            'studentId' => $studentId,
            'clientId' => $clientId,
            'sessionId' => $sessionId,
            'components' => $components,
        ]));
    }

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B. Real
     * IDs behind the "Invoice ALL Un-Invoiced Registrations" button.
     */
    public function unInvoicedEnrollments(int $clientId, int $sessionId): array
    {
        return $this->unwrap($this->http()->get('/enrollments/un-invoiced', [
            'clientId' => $clientId,
            'sessionId' => $sessionId,
        ]));
    }

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B.
     * Read-only, never mutates anything -- what the confirmation
     * screen calls to show real prices before any of the three
     * invoice buttons are clicked.
     */
    public function pricingPreview(array $enrollmentIds): array
    {
        return $this->unwrap($this->http()->post('/enrollments/pricing-preview', [
            'enrollmentIds' => $enrollmentIds,
        ]));
    }

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B. Powers
     * both "invoice just-now registered" and "invoice all un-invoiced"
     * equally -- same real endpoint, just a different list of
     * enrollment IDs from the caller.
     *
     * @throws ComplianceEngineConflictException on a real conflict (already invoiced, mixed clients/sessions, outside attendee) -- caller must catch this.
     */
    public function generatePublicSessionInvoice(array $enrollmentIds): array
    {
        return $this->unwrap($this->http()->post('/enrollments/generate-public-invoice', [
            'enrollmentIds' => $enrollmentIds,
        ]));
    }

    /**
     * Michael, 2026-09-01 -- Client Auto-Notify feature, invoice-checker
     * page. Read-only, no real QBO check triggered -- just our own,
     * already-known state.
     */
    public function knownInvoices(): array
    {
        return $this->unwrap($this->http()->get('/payments/known-invoices'));
    }

    /**
     * Michael, 2026-09-01 -- "Refresh" -- Option B, confirmed with
     * Michael: this genuinely re-checks real balances and acts (marks
     * paid, fires Brevo) right now, not just re-displays.
     */
    public function refreshInvoices(): array
    {
        return $this->unwrap($this->http()->post('/payments/refresh-invoices'));
    }

    /**
     * Michael, 2026-09-02 -- Client Auto-Notify feature. Real, manual
     * retry for an invoice stuck at "Not yet" -- safe to call more
     * than once for the same invoice, confirmed via
     * QboPaymentNotificationService's own isNotifiable() check.
     */
    public function retryNotification(string $qbInvoiceId): array
    {
        return $this->unwrap($this->http()->post('/payments/retry-notification', [
            'qbInvoiceId' => $qbInvoiceId,
        ]));
    }

    /**
     * Michael, 2026-09-03 -- Client Auto-Notify feature, invoice-
     * checker readout for the Private/Semi-Private path -- a genuinely
     * separate table from the Public one (retryNotification() above),
     * since "QBO Invoice" doesn't apply here at all.
     */
    public function privateNotifications(): array
    {
        return $this->unwrap($this->http()->get('/enrollments/private-notifications'));
    }

    public function retryPrivateNotification(int $enrollmentId): array
    {
        return $this->unwrap($this->http()->post("/enrollments/{$enrollmentId}/retry-notification"));
    }

    /** GET /clients/{clientId}/students -- a client's own employee roster. */
    public function listStudentsForClient(int $clientId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}/students"));
    }

    /**
     * GET /clients/{clientId}/students/roster -- Michael, 2026-08-23,
     * Client Page roster (Layer 2). The rich, derived version of the
     * employee list (Last Field with recency status, current
     * Enrolled session) -- separate from listStudentsForClient()
     * above, which stays the lightweight version Manual Enroll's
     * dropdown actually needs.
     */
    public function getStudentRoster(int $clientId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}/students/roster"));
    }

    /**
     * Michael, 2026-08-25 -- Section 4a extension, piece 3A: certification
     * history for the new Student detail page. Only actual CERTIFIED
     * outcomes -- an enrollment that never certified isn't part of a
     * student's certification history, confirmed with Michael.
     */
    public function getStudentCertificationHistory(int $clientId, int $studentId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}/students/{$studentId}/certification-history"));
    }

    /**
     * Michael, 2026-08-30 -- Lecture Certificate Upload feature. The
     * new, global, client-agnostic student lookup -- built for
     * Employee Search results with no employer client on file at all
     * (a real, non-rare case in legacy DIBs data, confirmed with
     * Michael), where the existing client-scoped student endpoints
     * have no client id to work from in the first place.
     */
    public function getStudent(int $studentId): array
    {
        return $this->unwrap($this->http()->get("/students/{$studentId}"));
    }

    public function getLectureCertificateHistory(int $studentId): array
    {
        return $this->unwrap($this->http()->get("/students/{$studentId}/lecture-certificates"));
    }

    /**
     * Michael, 2026-08-30 -- multipart, not JSON, since this sends an
     * actual file alongside its metadata -- ->attach() rather than the
     * plain ->post($payload) every other method here uses. $file is a
     * Laravel UploadedFile, straight from $request->file('...').
     */
    public function uploadLectureCertificate(int $studentId, array $data, \Illuminate\Http\UploadedFile $file): array
    {
        $response = $this->http()
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("/students/{$studentId}/lecture-certificate", $data);
        return $this->unwrap($response);
    }

    public function listProviders(bool $activeOnly = true): array
    {
        return $this->unwrap($this->http()->get('/providers', ['activeOnly' => $activeOnly]));
    }

    /** Compliance-Administrator-only, enforced server-side (LectureCertificateController) -- staffUserId is who's asking, not who's added. */
    public function createProvider(string $name, int $staffUserId): array
    {
        return $this->unwrap($this->http()->post('/providers', ['name' => $name, 'staffUserId' => $staffUserId]));
    }

    /**
     * Michael, 2026-08-30 -- raw response, not unwrap()'d -- this
     * returns actual file bytes, not JSON, so the normal unwrap()
     * (which calls ->json()) would be the wrong shape entirely.
     * Confirmed visible to clients, students, and staff.
     */
    public function downloadLectureCertificate(int $certificateId): \Illuminate\Http\Client\Response
    {
        return $this->http()->get("/lecture-certificates/{$certificateId}/download");
    }

    /** POST /clients/{clientId}/students -- Michael, 2026-08-23, Manual Enroll's "add new employee" inline action. */
    public function createStudent(int $clientId, array $payload): array
    {
        return $this->unwrap($this->http()->post("/clients/{$clientId}/students", $payload));
    }

    /** PATCH /clients/{clientId}/students/{studentId} -- Michael, 2026-08-23, Client Page roster (Layer 2): the inline Active/Inactive toggle and other employee edits. Partial update, same pattern as createStudent()'s sibling calls elsewhere. */
    public function updateStudent(int $clientId, int $studentId, array $payload): array
    {
        return $this->unwrap($this->http()->patch("/clients/{$clientId}/students/{$studentId}", $payload));
    }

    /**
     * PATCH /clients/{clientId}/students/{studentId}/reassign --
     * Michael, 2026-08-24, "Reassign Employee": moves a student to a
     * different employer client. A dedicated method, not folded into
     * updateStudent() above -- matches the dedicated Java endpoint,
     * which is its own distinct action rather than a routine field
     * edit (see StudentSearchController/StudentController's own
     * Javadoc for the full reasoning).
     */
    public function reassignStudent(int $currentClientId, int $studentId, int $newClientId): array
    {
        return $this->unwrap($this->http()->patch("/clients/{$currentClientId}/students/{$studentId}/reassign", [
            'newClientId' => $newClientId,
        ]));
    }

    /**
     * POST /clients/{clientId}/students/{studentId}/combine --
     * Michael, 2026-08-24, "Combine Employee": merges a duplicate
     * student record into $studentId (the survivor). Not a delete --
     * see StudentController's own Javadoc for the full reasoning
     * (the duplicate is marked inactive with a pointer to the
     * survivor, not removed).
     */
    public function combineStudents(int $clientId, int $studentId, int $duplicateStudentId): array
    {
        return $this->unwrap($this->http()->post("/clients/{$clientId}/students/{$studentId}/combine", [
            'duplicateStudentId' => $duplicateStudentId,
        ]));
    }

    /** PATCH /enrollments/{id}/roster-status -- primarily for staff setting DNC/DNA (Section 4f). */
    public function updateRosterStatus(int $enrollmentId, string $rosterStatus): array
    {
        return $this->unwrap($this->http()->patch("/enrollments/{$enrollmentId}/roster-status", [
            'rosterStatus' => $rosterStatus,
        ]));
    }

    /** DELETE /enrollments/{id} -- Michael, 2026-08-23, "unenroll cleanly" from the Session Roster view. Blocked server-side once a student is CERTIFIED (see EnrollmentController's own Javadoc); not re-checked here, since the view itself already hides the button in that case. */
    /**
     * Michael, 2026-09-03 -- returns the real response now (was void)
     * -- an unenroll against an already-paid enrollment now returns
     * {"unenrolled": true, "affectedQboInvoiceIds": [...]} so the
     * caller can surface which real QBO invoice(s) need a manual
     * void/credit, rather than just discarding that information.
     */
    public function deleteEnrollment(int $enrollmentId): array
    {
        return $this->unwrap($this->http()->delete("/enrollments/{$enrollmentId}"));
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

    /**
     * GET /clients/{id}/students/roster -- Section 4e "Manage their
     * employees" / view certifications. Michael, 2026-08-24: upgraded
     * from the plain /clients/{id}/students endpoint (which this
     * originally called) to the roster one built for the admin side --
     * gives the client real last-lecture/last-field certification data
     * instead of nothing, at no extra backend cost, since that logic
     * already exists and is proven.
     */
    public function listClientEmployees(int $clientId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}/students/roster"));
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
     * GET /students?q= -- Michael, 2026-08-24, "Find Employee": a
     * global, cross-client search, distinct from listStudentsForClient()
     * elsewhere in this file, which is always scoped to one already-
     * known client.
     */
    public function searchStudents(string $query): array
    {
        return $this->unwrap($this->http()->get('/students', ['q' => $query]));
    }

    // -----------------------------------------------------------------
    // Staff (Section 3) -- used by StaffApiUserProvider for session
    // re-hydration, and directly by admin screens managing staff users.
    // -----------------------------------------------------------------

    /** GET /staff/{id} -- fetched using the service account, not the staff member's own credentials. */
    public function getStaffUser(int $staffId): array
    {
        return $this->unwrap($this->http()->get("/staff/{$staffId}"));
    }

    /** GET /clients/{id} -- fetched using the service account, e.g. for session re-hydration after a client login (Michael, 2026-08-19). */
    public function getClient(int $clientId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}"));
    }

    /**
     * Michael, 2026-08-31 -- Password Reset feature. Both request
     * methods return the server's own generic message directly --
     * neither side should be second-guessed or reworded here, since
     * the exact wording (generic vs. Staff's distinct "no email on
     * file" case) was deliberately decided server-side.
     */
    public function requestStaffPasswordReset(string $username): array
    {
        return $this->unwrap($this->http()->post('/staff/forgot-password', ['username' => $username]));
    }

    /**
     * @throws ComplianceEngineConflictException on an invalid/expired token (409) -- caller must catch this.
     */
    public function resetStaffPassword(string $token, string $password): array
    {
        return $this->unwrap($this->http()->post('/staff/reset-password', ['token' => $token, 'password' => $password]));
    }

    public function requestClientPasswordReset(string $email): array
    {
        return $this->unwrap($this->http()->post('/clients/forgot-password', ['email' => $email]));
    }

    /**
     * @throws ComplianceEngineConflictException on an invalid/expired token (409) -- caller must catch this.
     */
    public function resetClientPassword(string $token, string $password): array
    {
        return $this->unwrap($this->http()->post('/clients/reset-password', ['token' => $token, 'password' => $password]));
    }

    /**
     * PATCH /clients/{id} -- Michael, 2026-08-22, Client Page rebuild.
     * Partial update -- only fields actually sent are touched.
     */
    public function updateClient(int $clientId, array $payload): array
    {
        return $this->unwrap($this->http()->patch("/clients/{$clientId}", $payload));
    }

    /**
     * Purchase Orders (Michael, 2026-08-22) -- clients often run one PO
     * across a full year or multiple seasons, not per-session.
     */
    public function listPurchaseOrders(int $clientId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}/purchase-orders"));
    }

    public function createPurchaseOrder(int $clientId, array $payload): array
    {
        return $this->unwrap($this->http()->post("/clients/{$clientId}/purchase-orders", $payload));
    }

    public function updatePurchaseOrder(int $clientId, int $poId, array $payload): array
    {
        return $this->unwrap($this->http()->patch("/clients/{$clientId}/purchase-orders/{$poId}", $payload));
    }

    /** Which sessions a given PO covers -- replaces DIBs' comma-separated PO_assoc_sessions text field with a real join table. */
    public function listPurchaseOrderSessions(int $clientId, int $poId): array
    {
        return $this->unwrap($this->http()->get("/clients/{$clientId}/purchase-orders/{$poId}/sessions"));
    }

    public function addPurchaseOrderSession(int $clientId, int $poId, int $sessionId): array
    {
        return $this->unwrap($this->http()->post("/clients/{$clientId}/purchase-orders/{$poId}/sessions/{$sessionId}"));
    }

    public function removePurchaseOrderSession(int $clientId, int $poId, int $sessionId): void
    {
        $this->unwrap($this->http()->delete("/clients/{$clientId}/purchase-orders/{$poId}/sessions/{$sessionId}"));
    }

    /**
     * POST /clients/register -- public, self-serve account creation
     * (Michael, 2026-08-19), general prospective-client registration,
     * independent of VR vs. traditional testing.
     */
    public function registerClient(array $payload): array
    {
        return $this->unwrap($this->http()->post('/clients/register', $payload));
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

    // -----------------------------------------------------------------
    // Live Digital Testing (the student's actual test-taking webapp)
    // -----------------------------------------------------------------

    /** GET .../live-test/my-status -- everything the testing webapp needs: run length, current point/color, and this student's own submission history. */
    public function getMyLiveTestStatus(int $sessionId, int $enrollmentId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/live-test/my-status", [
            'enrollmentId' => $enrollmentId,
        ]));
    }

    /** POST .../live-test/submit-guess -- a student's own opacity guess for the current live point. */
    public function submitLiveTestGuess(int $sessionId, int $enrollmentId, int $guess): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/live-test/submit-guess", [
            'enrollmentId' => $enrollmentId,
            'guess' => $guess,
        ]));
    }

    /** POST .../live-test/confirm-final-answers -- student's attestation once they've answered every required point. enrollmentId is a query param on the Java side, not a body field. */
    public function confirmLiveTestFinalAnswers(int $sessionId, int $enrollmentId): array
    {
        return $this->unwrap($this->http()->post(
            "/sessions/{$sessionId}/live-test/confirm-final-answers?enrollmentId={$enrollmentId}"
        ));
    }

    /** POST .../live-test/submit-signature -- a passing student's drawn signature (canvas data URL). Never called for a failing result. */
    public function submitLiveTestSignature(int $sessionId, int $enrollmentId, string $signatureDataUrl): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/live-test/submit-signature", [
            'enrollmentId' => $enrollmentId,
            'signatureDataUrl' => $signatureDataUrl,
        ]));
    }

    // -----------------------------------------------------------------
    // Live Digital Testing -- Operator/Field-Manager controls
    // -----------------------------------------------------------------

    /** GET .../live-test/status -- Operator's full view: every participant's submission state for the current (or revisited) point. */
    public function getLiveTestStatus(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/live-test/status"));
    }

    /** GET .../live-test/split-run-candidates -- real candidates for the Start Test form, using the unchanged eligibility rules. */
    public function getSplitRunCandidates(int $sessionId): array
    {
        return $this->unwrap($this->http()->get("/sessions/{$sessionId}/live-test/split-run-candidates"));
    }

    /** POST .../live-test/start -- $splitRunEnrollmentIds is null/empty for a normal full-run-only session. */
    public function startLiveTest(int $sessionId, ?array $splitRunEnrollmentIds = null): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/live-test/start", [
            'splitRunEnrollmentIds' => $splitRunEnrollmentIds,
        ]));
    }

    /** POST .../live-test/record-true-value -- Operator's tablet entry for the current point. */
    public function recordLiveTestTrueValue(int $sessionId, int $trueOpacity): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/live-test/record-true-value", [
            'trueOpacity' => $trueOpacity,
        ]));
    }

    /** POST .../live-test/advance -- manual fallback only; auto-advance (server-side) handles the normal case now. Never grades anything -- gradeLiveTest() below does that, separately, once students have confirmed. */
    public function advanceLiveTest(int $sessionId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/live-test/advance", []));
    }

    /** POST .../live-test/grade-test -- Operator/Field-Manager action, repeatable: grades whoever's currently ready (finished + confirmed), not gated on the whole class finishing together. */
    public function gradeLiveTest(int $sessionId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/live-test/grade-test", []));
    }

    /** POST .../live-test/revisit -- reopens a past point for correction, reusing its already-recorded true value. */
    public function revisitLiveTestPoint(int $sessionId, int $pointNumber): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/live-test/revisit", [
            'pointNumber' => $pointNumber,
        ]));
    }

    /** POST .../live-test/end-revisit -- returns to the real forward progress underneath. */
    public function endLiveTestRevisit(int $sessionId): array
    {
        return $this->unwrap($this->http()->post("/sessions/{$sessionId}/live-test/end-revisit", []));
    }

    // -----------------------------------------------------------------
    // Staff / Equipment (dropdowns for Session Details -- TEAM section)
    // -----------------------------------------------------------------

    /** GET /staff -- populates Proctor 1-3 / Field Manager / Operator / Session Info Owner dropdowns. */
    public function listStaff(): array
    {
        return $this->unwrap($this->http()->get('/staff'));
    }

    /**
     * POST /staff -- Compliance-Administrator-only on the Java side.
     * role defaults to STAFF there if omitted, but this UI always sends
     * an explicit choice since letting a new account silently default
     * without the creator seeing it chosen isn't the right UX for
     * something this sensitive.
     */
    public function createStaff(array $payload): array
    {
        return $this->unwrap($this->http()->post('/staff', $payload));
    }

    /**
     * PATCH /staff/{id} -- Compliance-Administrator-only on the Java
     * side. Does NOT support username or password changes -- those
     * aren't in the Java update() contract at all (password changes
     * deliberately go through a separate, not-yet-built authenticated
     * flow, not this general-purpose edit).
     */
    public function updateStaff(int $staffId, array $payload): array
    {
        return $this->unwrap($this->http()->patch("/staff/{$staffId}", $payload));
    }

    /** GET /trucks (Section 4 TEAM). */
    public function listTrucks(): array
    {
        return $this->unwrap($this->http()->get('/trucks'));
    }

    /**
     * Michael, 2026-08-31 -- Truck/Trailer Equipment feature. The
     * backend controllers/entities for this already existed (found
     * live tonight, after an unintentional rebuild) -- only the
     * Laravel-side UI was ever actually missing, confirmed with
     * Michael directly.
     */
    public function createTruck(array $data): array
    {
        return $this->unwrap($this->http()->post('/trucks', $data));
    }

    /** GET /trailers (Section 4h). */
    public function listTrailers(): array
    {
        return $this->unwrap($this->http()->get('/trailers'));
    }

    public function createTrailer(array $data): array
    {
        return $this->unwrap($this->http()->post('/trailers', $data));
    }

    public function getTrailerPanes(int $trailerId): array
    {
        return $this->unwrap($this->http()->get("/trailers/{$trailerId}/panes"));
    }

    public function createCalibrationPane(int $trailerId, array $data): array
    {
        return $this->unwrap($this->http()->post("/trailers/{$trailerId}/panes", $data));
    }

    /**
     * Michael, 2026-08-31 -- panes get re-certified annually; direct
     * editing, not a supersede-and-keep-history pattern -- confirmed
     * with Michael the real paper trail already lives outside this
     * system (physical NIST documentation).
     */
    public function updateCalibrationPane(int $paneId, array $data): array
    {
        return $this->unwrap($this->http()->patch("/trailers/panes/{$paneId}", $data));
    }

    public function getTestingSystems(int $trailerId): array
    {
        return $this->unwrap($this->http()->get('/testing-systems', ['trailerId' => $trailerId]));
    }

    public function createTestingSystem(array $data): array
    {
        return $this->unwrap($this->http()->post('/testing-systems', $data));
    }

    /**
     * Michael, 2026-08-31 -- 5-Filter import. Confirming/syncing a
     * TestingSystem's real component IDs against what was parsed from
     * a Chart Recorder export, offered as an explicit, opt-in step on
     * the import review page -- not automatic.
     */
    public function updateTestingSystem(int $testingSystemId, array $data): array
    {
        return $this->unwrap($this->http()->patch("/testing-systems/{$testingSystemId}", $data));
    }

    public function getCalibrationValidity(int $testingSystemId): bool
    {
        return $this->unwrap($this->http()->get("/testing-systems/{$testingSystemId}/calibration-validity"))['valid'] ?? false;
    }

    public function getMaintenanceEvents(int $testingSystemId): array
    {
        return $this->unwrap($this->http()->get("/testing-systems/{$testingSystemId}/maintenance-events"));
    }

    public function createMaintenanceEvent(int $testingSystemId, array $data): array
    {
        return $this->unwrap($this->http()->post("/testing-systems/{$testingSystemId}/maintenance-events", $data));
    }

    /**
     * Michael, 2026-08-31 -- 5-Filter import, parse-and-preview only --
     * nothing persisted server-side by this call. Multipart, like
     * uploadLectureCertificate() above -- ->attach(), not a plain
     * ->post($payload).
     */
    public function previewCalibrationImport(int $testingSystemId, \Illuminate\Http\UploadedFile $zipFile): array
    {
        $response = $this->http()
            ->attach('file', file_get_contents($zipFile->getRealPath()), $zipFile->getClientOriginalName())
            ->post("/testing-systems/{$testingSystemId}/calibration-records/import-preview");
        return $this->unwrap($response);
    }

    /**
     * Michael, 2026-08-31 -- the actual submit, after a reviewer has
     * confirmed the pane matches from previewCalibrationImport() and
     * entered their own six pass/fail judgments -- this is the only
     * place those six booleans get supplied; nothing upstream computes
     * or guesses at them.
     */
    public function submitCalibrationRecord(int $testingSystemId, array $data): array
    {
        return $this->unwrap($this->http()->post("/testing-systems/{$testingSystemId}/calibration-records", $data));
    }

    /**
     * @throws ComplianceEngineConflictException on 409 (e.g. Private
     *         session outside-authorization attempt, Section 4)
     * @throws RuntimeException on any other non-2xx response
     */
    /**
     * Michael, 2026-09-06 -- Self-Paced Lecture course shell (migration
     * 044). Real, new Java endpoints -- not yet built on that side, spec'd
     * here to match this file's own established 1:1-with-OpenAPI
     * convention.
     *
     * GET /lecture/student-lookup -- the real sign-in check. Confirmed
     * with Michael: student_number + last name against the real, existing
     * students table (no new identity system), plus
     * self_paced_lecture_allowed = true and at least one real enrollments
     * row with enrollment_components = 'LECTURE_ONLY' for that student.
     * Expected to 403 (ComplianceEngineForbiddenException) if the student
     * exists but isn't allowed/enrolled, matching this file's own existing
     * unwrap() convention -- LectureController below catches that
     * specifically to show the real "must be enrolled" message rather
     * than a generic error.
     */
    public function lectureStudentLookup(string $studentNumber, string $lastName): array
    {
        return $this->unwrap($this->http()->get('/lecture/student-lookup', ['studentNumber' => $studentNumber, 'lastName' => $lastName]));
    }

    /**
     * GET /lecture/students/{studentId}/sections -- the real, dynamic nav
     * sidebar data: every lecture_sections row, its lecture_pages (empty
     * for sections Michael hasn't captured yet), and this student's own
     * real progress (pages read, quiz attempts) so the sidebar can render
     * genuine locked/unlocked/checkmark state rather than a static shell.
     */
    public function getLectureSections(int $studentId): array
    {
        return $this->unwrap($this->http()->get("/lecture/students/{$studentId}/sections"));
    }

    /**
     * POST /lecture/students/{studentId}/pages/{pageId}/read -- marks a
     * real lecture_student_page_progress row, the "don't lose progress if
     * they step away" write described in migration 044's own comments.
     * Not called by anything in this shell yet (no real content pages
     * exist), but real content pages will need it as soon as Michael
     * starts feeding sections in.
     */
    public function markLecturePageRead(int $studentId, int $pageId): array
    {
        return $this->unwrap($this->http()->post("/lecture/students/{$studentId}/pages/{$pageId}/read"));
    }

    /**
     * GET /lecture/students/{studentId}/pages/{pageId} -- a single real
     * page's own title/content/next-page data, gated by this student's own
     * unlock state (Java side 403s if the page is genuinely locked for
     * this student -- sequential unlock, migration 044's own design).
     */
    public function getLecturePage(int $studentId, int $pageId): array
    {
        return $this->unwrap($this->http()->get("/lecture/students/{$studentId}/pages/{$pageId}"));
    }

    /**
     * Michael, 2026-09-07 -- Self-Paced Lecture Resources (migration 045).
     * GET /lecture/resources -- all real Resources items (Glossary, FAQs,
     * Abbreviations, VEO Resources, Bibliography), for the sidebar's own
     * Resources listing. No student context at all -- Resources content
     * isn't gated, unlocked, or tracked per-student, unlike the numbered
     * sections.
     */
    public function getLectureResources(): array
    {
        return $this->unwrap($this->http()->get('/lecture/resources'));
    }

    /**
     * GET /lecture/resources/{slug} -- a single real Resources page by
     * slug (not id), matching the real, live course's own URL structure
     * and the fact that these pages are linked to by name from other
     * pages' own content.
     */
    public function getLectureResource(string $slug): array
    {
        return $this->unwrap($this->http()->get("/lecture/resources/{$slug}"));
    }

    /**
     * Michael, 2026-09-07 -- real quiz-taking flow. GET
     * /lecture/students/{studentId}/quizzes/{quizId} -- the real quiz
     * structure (questions/choices) plus this student's own, current
     * in-progress answers, if any. Never includes which choice is
     * correct -- that's only ever revealed via submitLectureQuizAnswer()
     * below. A 403 here (ComplianceEngineForbiddenException) means the
     * section itself isn't unlocked yet.
     */
    public function getLectureQuiz(int $studentId, int $quizId): array
    {
        return $this->unwrap($this->http()->get("/lecture/students/{$studentId}/quizzes/{$quizId}"));
    }

    /**
     * POST /lecture/students/{studentId}/questions/{questionId}/answer --
     * the real, immediate per-question save, matching the live course's
     * own confirmed behavior (each answer submits right away, not a
     * single "submit all" at the end). Returns whether this specific
     * answer was correct, and once every question in the quiz has been
     * answered, the real final score/pass-fail too.
     */
    public function submitLectureQuizAnswer(int $studentId, int $questionId, int $choiceId): array
    {
        return $this->unwrap($this->http()->post("/lecture/students/{$studentId}/questions/{$questionId}/answer", ['choiceId' => $choiceId]));
    }

    /**
     * GET /lecture/students/{studentId}/quiz-summary -- the real Quiz
     * Summary Page data (lecture-quiz.php with no real section id in the
     * live course) -- every section at once, its total question count,
     * and the student's own most recent completed attempt for it, if
     * any.
     */
    public function getLectureQuizSummary(int $studentId): array
    {
        return $this->unwrap($this->http()->get("/lecture/students/{$studentId}/quiz-summary"));
    }

    protected function unwrap(Response $response): array
    {
        if ($response->status() === 409) {
            throw new ComplianceEngineConflictException($response->json('error') ?? 'Conflict', $response->json());
        }

        if ($response->status() === 403) {
            throw new ComplianceEngineForbiddenException($response->json('error') ?? 'Forbidden', $response->json());
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "Compliance Engine call failed [{$response->status()}]: {$response->body()}"
            );
        }

        return $response->json() ?? [];
    }
}
