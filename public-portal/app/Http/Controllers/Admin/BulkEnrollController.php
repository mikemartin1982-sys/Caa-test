<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineConflictException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-08-31/09-01 -- QBO Per-Student Invoicing, Stage 3A.
 * Confirmed with Michael directly: a genuinely separate, new flow --
 * NOT a replacement or extension of the existing Manual Enroll
 * (EnrollController, admin.enroll.*), which keeps working exactly as
 * it does today. This one replaces the Session Roster's own
 * "+ Enroll Student" link specifically -- the real, confirmed pain
 * point was that once a client is picked, only ONE employee could be
 * enrolled at a time, meaning a Field Manager enrolling several
 * employees from the same client on-site had to repeat the entire
 * flow once per person. Session is already known here (coming from
 * the Roster page itself), so this flow starts at client search, not
 * a session lookup step.
 *
 * Deliberately no new bulk-create endpoint on the Java side --
 * EnrollmentController.create() is already atomic per call (including
 * its own "Both" handling), so this simply calls it once per selected
 * student, confirmed with Michael as wanting each one independent --
 * one failure (duplicate, VR ineligibility, etc.) doesn't block or
 * roll back the others. Gives that behavior for free, with zero new
 * Java code and zero risk of a bulk path's validation drifting out of
 * sync with the already-real, single-enrollment one.
 */
class BulkEnrollController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    public function show(int $session): View
    {
        return view('admin.enrollment.bulk', ['sessionId' => $session]);
    }

    /**
     * Michael, 2026-08-31 -- AJAX-fetched once a client is picked from
     * the search flyout (matching the same, already-established
     * search-flyout pattern used throughout admin tonight), not a full
     * page reload. Reuses listClientEmployees() directly -- the exact
     * same, already-proven data source admin.clients.employees itself
     * uses -- filtered to active=true only here, confirmed with
     * Michael as "every active employee."
     *
     * Michael, 2026-09-01 -- found live: the route has two segments
     * ({session}, {client}), but this method originally only declared
     * one parameter ($client) -- Laravel bound the FIRST route segment
     * positionally to the only declared parameter, regardless of name,
     * so $client was actually receiving $session's value the whole
     * time. Both are declared now, matching the route's real parameter
     * order exactly, even though $session itself is genuinely unused
     * in the method body.
     *
     * Michael, 2026-09-01 -- also now fetches this session's real
     * roster (getRoster(), the same already-proven endpoint the admin
     * Roster page itself uses) and merges each employee's real,
     * already-enrolled components for THIS specific session -- option
     * A over greying out, confirmed with Michael as preferred: show
     * what a student is already enrolled in, rather than just hiding
     * the option. Matched by the real studentId (RosterEntry didn't
     * carry this until now -- added specifically for this, rather than
     * matching by email, a fragile proxy for identity).
     */
    public function employeesForClient(int $session, int $client): JsonResponse
    {
        $employees = collect($this->engine->listClientEmployees($client))
            ->filter(fn ($e) => $e['active'] ?? false)
            ->values();

        $roster = collect($this->engine->getRoster($session));
        $enrolledByStudentId = $roster->groupBy('studentId');

        $employees = $employees->map(function ($emp) use ($enrolledByStudentId) {
            $entries = $enrolledByStudentId->get($emp['id'], collect());
            $emp['alreadyLecture'] = $entries->contains(fn ($e) => ($e['enrollmentComponents'] ?? null) === 'LECTURE_ONLY');
            $emp['alreadyField'] = $entries->contains(fn ($e) => ($e['enrollmentComponents'] ?? null) === 'FIELD_ONLY');
            return $emp;
        })->values();

        return response()->json(['employees' => $employees]);
    }

    /**
     * Michael, 2026-08-31 -- the actual submit. Each selected student
     * gets its own, independent createEnrollment() call -- a real
     * failure for one (duplicate, VR ineligibility, no valid email,
     * client not authorized on this session, etc.) is caught and
     * recorded per-student, never aborting the rest of the batch.
     * Real results only, never fabricated -- this is what Stage 3B's
     * confirmation screen will read from session('bulkEnrollResults').
     */
    public function store(Request $request, int $session): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'integer'],
            'students.*.lecture' => ['nullable', 'boolean'],
            'students.*.field' => ['nullable', 'boolean'],
        ]);

        $results = [];

        foreach ($validated['students'] as $row) {
            $lecture = $row['lecture'] ?? false;
            $field = $row['field'] ?? false;

            if (!$lecture && !$field) {
                // Michael, 2026-08-31 -- a row with neither box checked
                // was included in the submission but genuinely opted
                // into nothing -- skipped silently rather than sent to
                // the API at all, since there's no real components
                // value for "neither."
                continue;
            }

            $components = $lecture && $field ? 'BOTH' : ($lecture ? 'LECTURE_ONLY' : 'FIELD_ONLY');

            try {
                $enrollment = $this->engine->createEnrollment(
                    $row['student_id'], $validated['client_id'], $session, $components
                );

                // Michael, 2026-09-01 -- found live: create() on the
                // Java side returns a single, flat Enrollment object
                // for LECTURE_ONLY/FIELD_ONLY, but a LIST of two real,
                // separate Enrollment rows for "Both" -- confirmed with
                // Michael weeks ago as intentional (two real rows, two
                // real emails, not one combined thing). This previously
                // stored that variable-shaped response as-is, and every
                // downstream consumer (the pricing-match on the
                // confirmation screen) assumed a single flat object
                // with its own 'id' -- silently failing to match
                // anything for every "Both" enrollment. Normalized here
                // into one $results entry PER REAL ENROLLMENT ROW, so
                // "Both" always produces two separate, correctly-priced
                // lines, matching what the Java side actually created.
                $enrollmentRows = array_is_list($enrollment) ? $enrollment : [$enrollment];
                foreach ($enrollmentRows as $row2) {
                    $results[] = [
                        'studentId' => $row['student_id'],
                        'success' => true,
                        'components' => $row2['enrollmentComponents'] ?? $components,
                        'enrollment' => $row2,
                    ];
                }
            } catch (ComplianceEngineConflictException $e) {
                $results[] = [
                    'studentId' => $row['student_id'],
                    'success' => false,
                    'components' => $components,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Michael, 2026-09-02 -- found live: this used ->with(), which
        // is Laravel's own FLASH mechanism -- deliberately valid for
        // exactly ONE subsequent request, then automatically cleared.
        // confirm() read it fine (that was the one valid request), but
        // by the time a button was actually clicked afterward, it was
        // already gone -- explaining a real $1,650 invoice existing in
        // QBO with no matching Payment row on our side at all, and no
        // error shown anywhere. session()->put() is a real, permanent
        // write instead, correct for data that needs to survive until
        // whichever of the three buttons eventually gets clicked.
        session()->put('bulkEnrollResults', $results);
        session()->put('bulkEnrollClientId', $validated['client_id']);

        return redirect()->route('admin.enroll.bulk.confirm', ['session' => $session]);
    }

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B. Now
     * also fetches a real pricing preview for every successfully-
     * enrolled student, so the confirmation screen can show real
     * dollar amounts before any of the three invoice buttons are
     * clicked -- read-only, nothing is mutated by loading this page.
     *
     * Michael, 2026-09-03 -- Bulk Enroll, Private/Semi-Private support.
     * Confirmed with Michael: PUBLIC keeps the existing per-student
     * price breakdown; PRIVATE/SEMI_PRIVATE show real headcount and
     * warn on overage instead -- Private billing is flat, session-
     * level, so a per-student price genuinely doesn't apply at all
     * (pricingPreview() itself explicitly throws for anything that
     * isn't PUBLIC, confirmed against EnrollmentPricingService's own
     * real, existing guard).
     */
    public function confirm(int $session): View|\Illuminate\Http\RedirectResponse
    {
        $results = session('bulkEnrollResults');
        $clientId = session('bulkEnrollClientId');
        if ($results === null) {
            return redirect()->route('admin.sessions.roster', ['session' => $session]);
        }

        $succeededIds = collect($results)->where('success', true)->pluck('enrollment.id')->filter()->values()->all();
        $schoolType = $this->engine->getSession($session)['schoolType'] ?? null;

        $pricing = null;
        $headcount = null;
        if ($schoolType === 'PUBLIC') {
            if (!empty($succeededIds)) {
                $pricing = $this->engine->pricingPreview($succeededIds);
            }
        } elseif (in_array($schoolType, ['PRIVATE', 'SEMI_PRIVATE'], true)) {
            // Michael, 2026-09-04 -- found live: fieldHeadcount() is
            // called here in confirm(), which always runs AFTER
            // store() already, genuinely created this batch's new
            // Enrollment rows -- meaning currentFieldCount returned by
            // the Java side already, correctly includes this batch,
            // not just what existed "before" it. The original code
            // then ALSO added newFieldCount on top of that same,
            // already-inclusive number -- double-counting the same,
            // identical students (confirmed live: 3 real enrollments,
            // shown as "6 total" -- 3 + 3, the same 3 counted twice).
            // Fixed: currentFieldCount IS the real, final, resulting
            // total already; "already enrolled before this batch" is
            // derived by subtracting newFieldCount back out, not the
            // other way around.
            $headcount = $this->engine->fieldHeadcount($session);
            // Michael, 2026-09-03 -- how many of THIS batch are real,
            // successful FIELD_ONLY enrollments -- same counting rule
            // as the Java side's own overage logic (lecture doesn't
            // count toward the field headcount).
            $newFieldCount = collect($results)->where('success', true)->where('components', 'FIELD_ONLY')->count();
            $resultingTotal = $headcount['currentFieldCount'] ?? 0;
            $headcount['newFieldCount'] = $newFieldCount;
            $headcount['currentFieldCount'] = max(0, $resultingTotal - $newFieldCount);
            $headcount['resultingTotal'] = $resultingTotal;
            $headcount['overCapacity'] = $headcount['includedFieldHeadcount'] !== null
                && $headcount['resultingTotal'] > $headcount['includedFieldHeadcount'];
        }

        return view('admin.enrollment.bulk-confirm', [
            'sessionId' => $session,
            'clientId' => $clientId,
            'schoolType' => $schoolType,
            'results' => $results,
            'succeededIds' => $succeededIds,
            'pricing' => $pricing,
            'headcount' => $headcount,
        ]);
    }

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B. "Generate
     * QBO Invoice for JUST-NOW Registered" -- the specific enrollment IDs
     * from the batch that was just submitted, nothing broader.
     */
    public function invoiceJustNow(int $session): \Illuminate\Http\RedirectResponse
    {
        $results = session('bulkEnrollResults');
        if ($results === null) {
            return redirect()->route('admin.sessions.roster', ['session' => $session]);
        }
        $succeededIds = collect($results)->where('success', true)->pluck('enrollment.id')->filter()->values()->all();

        return $this->generateInvoiceAndRedirect($session, $succeededIds);
    }

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B.
     * "Generate QBO Invoice for ALL Un-Invoiced Registrations" -- looks
     * up the real, CURRENT list of un-invoiced enrollments for this
     * client+session fresh (not the stale, just-submitted list from
     * session()), since real time may have passed and other
     * enrollments could have accumulated since.
     */
    public function invoiceAllUnInvoiced(Request $request, int $session): \Illuminate\Http\RedirectResponse
    {
        $clientId = $request->input('client_id') ?? session('bulkEnrollClientId');
        if (!$clientId) {
            return redirect()->route('admin.sessions.roster', ['session' => $session]);
        }

        $unInvoiced = collect($this->engine->unInvoicedEnrollments((int) $clientId, $session))->pluck('id')->all();
        if (empty($unInvoiced)) {
            return redirect()->route('admin.sessions.roster', ['session' => $session])
                ->with('status', 'Nothing to invoice -- no un-invoiced registrations for this client on this session.');
        }

        return $this->generateInvoiceAndRedirect($session, $unInvoiced);
    }

    // Michael, 2026-09-04 -- generatePrivateInvoice() removed entirely.
    // Private/Semi-Private billing moved to session close-out (see
    // SessionController.update()'s own, new closedOut-transition logic)
    // -- confirmed with Michael: no invoice exists at all until then,
    // so there's no real, legitimate reason for a manual "generate now"
    // action from this screen anymore.

    private function generateInvoiceAndRedirect(int $session, array $enrollmentIds): \Illuminate\Http\RedirectResponse
    {
        if (empty($enrollmentIds)) {
            \Illuminate\Support\Facades\Log::warning('generateInvoiceAndRedirect called with an EMPTY enrollmentIds array -- nothing to invoice, but redirecting silently.');
        }

        try {
            $this->engine->generatePublicSessionInvoice($enrollmentIds);
        } catch (ComplianceEngineConflictException $e) {
            return redirect()->route('admin.sessions.roster', ['session' => $session])
                ->with('status', 'Could not generate invoice: ' . $e->getMessage());
        } catch (\Throwable $e) {
            // Michael, 2026-09-02 -- found live: only the specific 409
            // case was ever caught -- any OTHER real failure (a genuine
            // 500, a timeout, etc.) would have propagated uncaught.
            // Logged and shown explicitly now, rather than risking it
            // going unnoticed.
            \Illuminate\Support\Facades\Log::error('generatePublicSessionInvoice threw an UNEXPECTED exception', ['class' => get_class($e), 'message' => $e->getMessage()]);
            return redirect()->route('admin.sessions.roster', ['session' => $session])
                ->with('status', 'Could not generate invoice (unexpected error): ' . $e->getMessage());
        }

        session()->forget(['bulkEnrollResults', 'bulkEnrollClientId']);

        return redirect()->route('admin.sessions.roster', ['session' => $session])
            ->with('success', 'Invoice generated successfully.');
    }
}
