<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Section 4c: staff-facing Session Details -- School Type selector,
 * Confirmed checkbox + comment, Team Comments (write-once), Publish
 * toggle (gated by completeness, sticky once on), and "Copy Forward
 * 6 Months." Linked both ways with the Roster (Section 4f).
 */
class SessionDetailController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    /**
     * JSON search proxy for the "Set Host Client" typeahead below --
     * the browser can't safely call the Compliance Engine directly
     * (it'd need the Basic Auth credentials client-side), so this
     * forwards through Laravel and returns just what the flyout needs.
     */
    public function searchClients(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = (string) $request->query('q', '');
        // Michael, 2026-08-23: was a flat 2-character minimum -- missed
        // when the frontend's own minimum was relaxed for numeric
        // input, so a single-digit client ID (e.g. "2") still hit this
        // server-side wall and came back empty even after the JS let
        // the request through. A single digit is still a meaningful,
        // narrowing client-ID query, unlike a single letter for name
        // matching.
        $minLength = ctype_digit($q) ? 1 : 2;
        if (strlen($q) < $minLength) {
            return response()->json([]);
        }

        $results = $this->engine->searchClients($q);

        return response()->json(array_map(fn ($c) => [
            'id' => $c['id'] ?? null,
            'label' => $c['recordName'] ?? $c['company'] ?? ('Client #' . ($c['id'] ?? '')),
            'city' => $c['city'] ?? null,
            'state' => $c['state'] ?? null,
        ], array_slice($results, 0, 10)));
    }

    public function show(int $sessionId): View
    {
        return view('admin.session-detail', [
            'session' => $this->engine->getSession($sessionId),
            'copiedFrom' => $this->engine->getCopiedFromSummary($sessionId),
            'comments' => $this->engine->getSessionComments($sessionId),
            'readyToPublish' => $this->engine->isSessionReadyToPublish($sessionId),
            'authorizedClients' => $this->engine->getAuthorizedClients($sessionId),
            'notifiedClients' => $this->engine->getNotifiedClients($sessionId),
            'staff' => $this->engine->listStaff(),
            'trucks' => $this->engine->listTrucks(),
            'trailers' => $this->engine->listTrailers(),
            'sessionDays' => $this->engine->getSessionDays($sessionId),
            'resolvedPo' => $this->engine->getResolvedPo($sessionId),
        ]);
    }

    /**
     * General field edits across every Session Details section (GENERAL/
     * FIELD/TEAM/SCHOOL INFO/PRIVATE SCHOOL BID AND INVOICE). Only
     * fields actually present in the submitted form get sent -- empty
     * text inputs are sent as null-ish and skipped server-side (see
     * SessionController.update in the API), so unrelated fields on
     * other sub-forms of this same page are never accidentally cleared.
     */
    public function update(Request $request, int $sessionId): RedirectResponse
    {
        // Booleans need explicit handling -- an unchecked HTML checkbox
        // sends NOTHING, which would otherwise look identical to "field
        // not part of this form" and get silently skipped rather than
        // actually being set to false.
        $checkboxFields = [
            'confirmed', 'closedOut', 'bidLost', 'notNeedCopy', 'vrSession',
            'advertiseSemiPrivateAsPublic', 'staggeredArrivalTimes', 'useClientInfo', 'canceled',
            'bidNeedPoUpfront', 'bidNoPublicAllowed', 'bidRequiresCertOfCompletion',
            'bidNoAddons', 'bidAddonsRequireChangeOrder',
        ];

        $fields = $request->except('_token');
        foreach ($checkboxFields as $cb) {
            $fields[$cb] = $request->boolean($cb);
        }

        // Michael, 2026-08-23, found live during testing (two rounds):
        // clearing a text field to blank and saving silently left the
        // old value in place. Round one removed a filter that dropped
        // empty strings here -- didn't fix it, because Laravel 11's
        // default ConvertEmptyStringsToNull middleware (confirmed via
        // bootstrap/app.php -- not explicitly removed there, so still
        // active) already converts "" to null BEFORE this method ever
        // runs. By the time $request->except() runs, a genuinely-
        // cleared field and a field that was never part of this form
        // at all are indistinguishable -- both null. Java's own
        // if (field != null) partial-patch check then correctly (from
        // its own perspective) treats both as "don't touch."
        //
        // Fix: for fields we KNOW are plain text on the Java side
        // (where an empty string is a valid, meaningful value, unlike
        // numeric/ID/BigDecimal fields where "" would fail to parse at
        // all), explicitly convert a remaining null back to "" --
        // safe to do here specifically because we only do it for keys
        // that were genuinely present in this form's submission
        // (checked via array_key_exists, not just isset, since isset()
        // itself returns false for a null value and would silently
        // skip exactly the fields this is meant to fix).
        $stringFields = [
            'locationName', 'addressStreet', 'addressCity', 'addressState', 'addressZip',
            'externalRegistrationName', 'externalRegistrationPhone', 'externalRegistrationNotes',
            'publicSessionNotes', 'poNumber', 'confirmedComment', 'bidLostReason', 'notNeedCopyWhy',
            'qboClassRefId', 'sessionLog', 'adminComments', 'fieldTimezone', 'fieldContact',
            'fieldContactPhone', 'stagedLocation', 'fieldFacility', 'fieldAddress', 'fieldCity',
            'fieldState', 'fieldZip', 'schoolUrl', 'schoolGeoArea', 'bidExtraDetailsLecture',
            'bidExtraDetailsField', 'bidCaaNotes', 'bidEmailMessage', 'poForInvoice', 'lastQboInvoiceSentNumber',
        ];
        foreach ($stringFields as $sf) {
            if (array_key_exists($sf, $fields) && $fields[$sf] === null) {
                $fields[$sf] = '';
            }
        }

        // Any OTHER remaining nulls are genuinely fields not part of
        // this particular form (e.g. poForInvoice when submitting the
        // General form) -- drop those so Java's partial-patch correctly
        // leaves them untouched, exactly as intended.
        $fields = array_filter($fields, fn ($v) => $v !== null);

        // Michael, 2026-09-04 -- session close-out billing redesign.
        // Java's update() now, sometimes, returns a real
        // closeOutInvoiceWarning if closedOut just genuinely flipped
        // true but the automatic invoice generation failed (e.g.
        // missing privateCost) -- previously this whole return value
        // was silently discarded entirely, so this warning would have
        // vanished into nothing without this change.
        $result = $this->engine->updateSession($sessionId, $fields);

        if (!empty($result['closeOutInvoiceWarning'])) {
            return back()->with('status', $result['closeOutInvoiceWarning']);
        }

        return back()->with('status', 'Session details updated.');
    }

    /** Section 4c: atomically sets verified=true + who + when, server-side. */
    public function verifyInfo(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'verified_by_staff_id' => ['required', 'integer'],
        ]);

        $this->engine->verifySessionInfo($sessionId, (int) $validated['verified_by_staff_id']);

        return back()->with('status', 'Session info marked verified.');
    }

    /** Section 4c "Clients to be Notified." */
    public function addNotifiedClient(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer'],
        ]);

        $this->engine->addNotifiedClient($sessionId, (int) $validated['client_id']);

        return back()->with('status', 'Client added to notify list.');
    }

    public function removeNotifiedClient(int $sessionId, int $clientId): RedirectResponse
    {
        $this->engine->removeNotifiedClient($sessionId, $clientId);

        return back()->with('status', 'Client removed from notify list.');
    }

    /**
     * Sets/replaces the host client on a Private/Semi-Private/VTCA/
     * Proposed session. Matches DIBs' "Company Name (Client)" field.
     */
    public function setHost(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer'],
        ]);

        try {
            $this->engine->setSessionHost($sessionId, (int) $validated['client_id']);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not set host: ' . $e->getMessage());
        }

        return back()->with('status', 'Host client set.');
    }

    /**
     * Michael, 2026-09-03 -- manual retry for the QBO Class sync,
     * surfaced from the new, visible warning on this same page --
     * confirmed with Michael as genuinely helpful, since the automatic
     * sync (during publish()) can fail silently, visible only in the
     * server console otherwise, with no indication anywhere in the UI
     * that anything went wrong at all.
     */
    public function syncQboClass(int $sessionId): RedirectResponse
    {
        try {
            $this->engine->syncQboClass($sessionId);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not sync QBO Class: ' . $e->getMessage());
        }

        return back()->with('success', 'QBO Class synced successfully.');
    }

    /**
     * Michael, 2026-09-04 -- session close-out billing redesign. A
     * real, manual action from the Bid & Invoice section itself,
     * confirmed with Michael: "Generate" and "Send" are two genuinely
     * separate steps -- this one creates the actual QBO invoice
     * (privateCost + any real overage) and posts it to the session.
     * closedOut itself is untouched by this -- it stays a plain,
     * manual toggle Chasity sets herself once payment is confirmed.
     * Safe to click more than once -- the underlying duplicate-invoice
     * guard already treats "already generated" as a real, expected
     * success, not an error.
     */
    public function generateInvoice(int $sessionId): RedirectResponse
    {
        try {
            $this->engine->generatePrivateSessionInvoice($sessionId);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not generate invoice: ' . $e->getMessage());
        }

        return back()->with('success', 'Invoice generated successfully.');
    }

    /**
     * Michael, 2026-09-04 -- session close-out billing redesign. The
     * real, second, separate step -- actually emails the already-
     * generated invoice to the client, via QBO's own real, dedicated
     * /invoice/{id}/send endpoint (verified directly, not assumed --
     * distinct from creating an invoice with EmailStatus set at
     * creation time, which is how the Public path works instead).
     * closedOut itself is untouched by this too -- confirmed with
     * Michael as a plain, manual toggle, not automatically flipped by
     * either this or generateInvoice() above.
     */
    public function sendInvoice(int $sessionId): RedirectResponse
    {
        try {
            $this->engine->sendInvoice($sessionId);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not send invoice: ' . $e->getMessage());
        }

        return back()->with('success', 'Invoice sent to client successfully.');
    }

    /**
     * Michael, 2026-08-19, found live during testing -- the "Send
     * Confirmation Email" gate correctly said a scheduled date was
     * missing, but there was genuinely no way to add one anywhere on
     * this page. sessionDate is a plain date input; dayNumber/times
     * are left to the Java side's own sensible defaults if omitted.
     */
    /**
     * Michael, 2026-08-19, found live during testing (two rounds) --
     * first the button existed with nothing to add a day with; then a
     * hardcoded 8 AM-5 PM was too rigid for clients who need an
     * earlier start. start_time/end_time are optional -- left blank,
     * Java's own sensible 8 AM-5 PM default still applies (see
     * SessionController.addDay() on the Java side), but a real
     * override is now genuinely possible from this form.
     */
    public function addDay(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'session_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        try {
            // Genuinely compute the next day number (existing count + 1)
            // rather than claiming to and always sending null -- found
            // and fixed live, 2026-08-19, before it ever shipped: Java
            // defaults a null dayNumber to 1 every time, which would
            // have silently given every added day the same number.
            $existingDays = $this->engine->getSessionDays($sessionId);
            $nextDayNumber = count($existingDays) + 1;
            $this->engine->addSessionDay(
                $sessionId,
                $validated['session_date'],
                $nextDayNumber,
                $validated['start_time'] ?? null,
                $validated['end_time'] ?? null,
            );
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not add day: ' . $e->getMessage());
        }

        return back()->with('status', 'Scheduled day added.');
    }

    /**
     * Michael, 2026-08-19, found live during testing -- days could be
     * added but never removed.
     */
    public function deleteDay(int $sessionId, int $dayId): RedirectResponse
    {
        try {
            $this->engine->deleteSessionDay($sessionId, $dayId);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not delete day: ' . $e->getMessage());
        }

        return back()->with('status', 'Scheduled day removed.');
    }

    /**
     * Michael, 2026-08-19 -- staff-triggered confirmation email to the
     * session's host client, sent from a button in the Bid area. NOT
     * automatic, unlike the certification email -- only fires on an
     * explicit click. Requires a host client already set (see setHost()
     * above) with an email address on file, and at least one scheduled
     * SessionDay -- see SessionController.sendConfirmationEmail()'s
     * own 409 cases on the Java side for exactly which is missing.
     */
    public function sendConfirmationEmail(Request $request, int $sessionId): RedirectResponse
    {
        try {
            $result = $this->engine->sendSessionConfirmationEmail($sessionId, $request->user('staff')->id);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not send confirmation email: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            // Deliberately NOT assuming a specific cause here (e.g. "mail
            // server unreachable") -- found live, 2026-08-19: a genuinely
            // unrelated bug (a Hibernate lazy-loading failure on the Java
            // side) surfaced through this exact same catch block, and an
            // over-specific message sent debugging in the wrong direction
            // entirely. Any 500-level failure lands here; the real cause
            // belongs in the Java server log, not guessed at here.
            return back()->with('status', 'Could not send confirmation email -- check the server log for the actual cause.');
        }

        return back()->with('status', 'Confirmation email sent to ' . ($result['to'] ?? 'the host client') . '.');
    }

    /**
     * Michael, 2026-08-19 -- matches a real bid template
     * (last_bid_sent.docx) he provided.
     */
    public function generateBid(Request $request, int $sessionId): RedirectResponse
    {
        try {
            $result = $this->engine->generateBid($sessionId, $request->user('staff')->id);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not generate bid: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            // Same reasoning as sendConfirmationEmail() above -- don't
            // guess at a specific cause here, the real one belongs in
            // the Java server log.
            return back()->with('status', 'Could not generate bid -- check the server log for the actual cause.');
        }

        return back()->with('status', 'Bid generated: ' . ($result['quoteNumber'] ?? 'see Quote Number above') . '.');
    }

    /**
     * Streams the PDF bytes Java returns straight to the browser --
     * the browser can't hit the Java endpoint directly (it requires the
     * shared service-account basic auth), so this is a genuine proxy,
     * not just a redirect.
     */
    public function downloadBidPdf(int $sessionId)
    {
        $pdf = $this->engine->getBidPdf($sessionId);
        if ($pdf === null) {
            return back()->with('status', 'No bid has been generated for this session yet.');
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bid-session-' . $sessionId . '.pdf"',
        ]);
    }

    /**
     * Michael, 2026-08-25 -- Section 4a extension, piece 2/3B: the
     * Brevo retention-gap target list, exported as a real, downloadable
     * .xlsx -- confirmed with Michael as an Excel output to be consumed
     * directly (imported into Brevo), not a UI page. Column order and
     * naming on the first sheet deliberately match the real
     * Augusta_GA.xlsx sample Michael provided exactly, so this drops
     * into the same workflow without adjustment. The second sheet is
     * the traceability answer to Michael's own direct question ("are
     * we still able to see where they went?") -- who got excluded, and
     * exactly where they're currently enrolled instead, not just a
     * silent drop from the send list.
     *
     * Michael, 2026-08-26 -- filename made location-specific
     * ("Augusta_GA_7_Evaluation.xlsx" rather than a generic
     * "brevo-target-list-session-7") -- the link text itself is fine
     * as-is, but the downloaded file needs to read clearly on its own
     * once it's sitting in a Downloads folder next to a dozen others.
     */
    public function downloadBrevoTargetList(int $sessionId)
    {
        $session = $this->engine->getSession($sessionId);
        $result = $this->engine->getBrevoTargetList($sessionId);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $targetSheet = $spreadsheet->getActiveSheet();
        $targetSheet->setTitle('Target List');
        $targetSheet->fromArray(['Client Name', 'Client First', 'Client Last', 'Email', 'Phone'], null, 'A1');
        $row = 2;
        foreach (($result['targetList'] ?? []) as $entry) {
            $targetSheet->fromArray([
                $entry['clientName'] ?? '',
                $entry['clientFirst'] ?? '',
                $entry['clientLast'] ?? '',
                $entry['email'] ?? '',
                $entry['phone'] ?? '',
            ], null, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'E') as $col) {
            $targetSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $excludedSheet = $spreadsheet->createSheet();
        $excludedSheet->setTitle('Excluded (Shifted Elsewhere)');
        $excludedSheet->fromArray(['Client Name', 'Currently Enrolled At', 'Session Date'], null, 'A1');
        $row = 2;
        foreach (($result['excludedShiftedElsewhere'] ?? []) as $entry) {
            $excludedSheet->fromArray([
                $entry['clientName'] ?? '',
                $entry['currentSessionLocation'] ?? '',
                !empty($entry['currentSessionDate']) ? \Illuminate\Support\Carbon::parse($entry['currentSessionDate'])->format('m/d/Y') : '',
            ], null, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'C') as $col) {
            $excludedSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $stream = fopen('php://temp', 'r+');
        $writer->save($stream);
        rewind($stream);
        $bytes = stream_get_contents($stream);
        fclose($stream);

        // Michael, 2026-08-26 -- "Augusta_GA_7_Evaluation.xlsx", not a
        // generic session-id-only name -- location + state, matching
        // the real Augusta_GA.xlsx sample's own naming convention.
        // Sanitized for filesystem safety: spaces to underscores,
        // anything not alphanumeric/underscore/hyphen stripped.
        $locationPart = trim(($session['locationName'] ?? 'Session') . '_' . ($session['addressState'] ?? ''));
        $locationPart = preg_replace('/[^A-Za-z0-9_\-]+/', '_', str_replace(' ', '_', $locationPart));
        $locationPart = trim($locationPart, '_');
        $filename = $locationPart . '_' . $sessionId . '_Evaluation.xlsx';

        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Section 4c: Confirmed and Team Comments are the same underlying
     * resource, distinguished by comment_type -- write-once, no edit or
     * delete route exists for this resource anywhere in the app.
     */
    public function storeComment(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'comment_type' => ['required', 'in:CONFIRMATION,TEAM_COMMENT'],
            'text' => ['required', 'string'],
        ]);

        $authorId = $request->user()->id ?? 1; // staff auth wiring TBD -- see admin auth note

        $this->engine->addSessionComment(
            $sessionId,
            $validated['comment_type'],
            $validated['text'],
            $authorId,
        );

        return back()->with('status', 'Comment added.');
    }

    /** Section 4c: only reachable once readyToPublish is true -- the button itself is disabled otherwise in the view. */
    public function publish(int $sessionId): RedirectResponse
    {
        $this->engine->publishSession($sessionId);

        return back()->with('status', 'Session published.');
    }

    /** Section 4c: available on all session types, not just Private/Semi-Private. */
    public function copyForward(int $sessionId): RedirectResponse
    {
        $newSession = $this->engine->copySessionForward($sessionId);

        return redirect()
            ->route('admin.sessions.show', ['session' => $newSession['id']])
            ->with('status', 'Session copied forward 6 months. Review and confirm the new date.');
    }
}
