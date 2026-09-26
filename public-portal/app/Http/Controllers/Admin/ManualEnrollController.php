<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineConflictException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-08-23 -- the actual UI for creating an Enrollment.
 * Nothing previously called POST /enrollments except raw API calls
 * during testing -- this is the first real front-end for it.
 *
 * Flow: pick a session (direct ID + lookup, matching DIBs' own "Edit
 * Sess" quick-jump convention -- staff typically already know the
 * session ID from the calendar/dashboard, so a full search flyout
 * wasn't built for this first pass), pick a client (reuses the
 * existing Client Page search flyout), then pick from that client's
 * existing student roster or add a new one inline without leaving
 * the page. Supports a ?client= query param so a future "Enroll
 * Employee" link from the Client Page can pre-select the client,
 * matching how DIBs' own manual-enroll-f.php?id= worked.
 */
class ManualEnrollController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    public function create(Request $request): View
    {
        $preselectedClientId = $request->query('client');
        $preselectedClient = null;
        $preselectedStudents = [];
        // Michael, 2026-08-23 -- lets the Employees roster page's own
        // "Enroll" link (per row, only shown when that student isn't
        // already enrolled) jump straight here with both the client
        // AND the specific student pre-picked, not just the client.
        $preselectedStudentId = $request->query('student');
        // Michael, 2026-08-23 -- lets a Session Roster page's own "+
        // Enroll Student" link jump straight here with the session
        // already known -- the view auto-triggers the Lookup step for
        // this one on page load (still shows the confirmation box for
        // verification, just without an extra manual click), since
        // we're coming from that exact session's own roster already.
        $preselectedSessionId = $request->query('session');

        if ($preselectedClientId) {
            $preselectedClient = $this->engine->getClient((int) $preselectedClientId);
            $preselectedStudents = $this->engine->listStudentsForClient((int) $preselectedClientId);
        }

        return view('admin.enrollment.create', [
            'preselectedClient' => $preselectedClient,
            'preselectedStudentId' => $preselectedStudentId ? (int) $preselectedStudentId : null,
            'preselectedSessionId' => $preselectedSessionId ? (int) $preselectedSessionId : null,
            'preselectedStudents' => $preselectedStudents,
        ]);
    }

    /** AJAX -- populates the student dropdown once a client is picked. */
    public function studentsForClient(int $clientId): JsonResponse
    {
        return response()->json($this->engine->listStudentsForClient($clientId));
    }

    /** AJAX -- the "Lookup" step confirming which session staff actually typed in before they commit to enrolling into it. */
    public function lookupSession(Request $request): JsonResponse
    {
        $validated = $request->validate(['session_id' => ['required', 'integer']]);

        try {
            $session = $this->engine->getSession((int) $validated['session_id']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'Session not found.'], 404);
        }

        return response()->json([
            'id' => $session['id'] ?? null,
            'locationName' => $session['locationName'] ?? null,
            'schoolType' => $session['schoolType'] ?? null,
            'vrSession' => $session['vrSession'] ?? false,
            'addressCity' => $session['addressCity'] ?? null,
            'addressState' => $session['addressState'] ?? null,
        ]);
    }

    /** AJAX -- add a new employee inline, without leaving the page, matching DIBs' separate "Add Employee" action folded into this one flow instead. */
    public function storeStudent(Request $request, int $clientId): JsonResponse
    {
        // Michael, 2026-08-23 -- email is required, not optional --
        // certificates are emailed to the student on successful
        // certification, matching the same requirement now enforced
        // on the Java side (StudentController).
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
            'email' => ['required', 'email'],
        ]);

        $student = $this->engine->createStudent($clientId, $validated);

        return response()->json($student, 201);
    }

    /**
     * Michael, 2026-09-03 -- found live: this created the Enrollment
     * and redirected, with NO invoice-generation logic at all --
     * meaning anyone enrolled here was never billed, and never got
     * added to a real Payment row, so never showed up on the invoice-
     * checker or received a Brevo notification either. Confirmed with
     * Michael: auto-generate immediately, no extra confirmation step
     * (unlike Bulk Enroll's own, deliberate two-step flow -- this is a
     * genuinely different, single-student action).
     *
     * PUBLIC uses the real, per-student invoicing path (safe to call
     * every time -- each call only ever bills the one, just-created
     * enrollment). PRIVATE uses the real, session-level flat-fee path
     * -- but that's a whole-session invoice, not per-student, so a
     * second student manually enrolled into the same session would
     * hit the invoice service's own "already generated" 409 (a real,
     * separate fix made alongside this one) -- caught here and treated
     * as an expected success, not an error, since the session's own
     * flat fee already covers this new student too. SEMI_PRIVATE/
     * PROPOSED/VTCA are skipped entirely -- Semi-Private's own billing
     * system doesn't exist yet (separate, still-open backlog item),
     * PROPOSED shouldn't have real enrollments at all, and VTCA
     * handles its own billing independently of this system.
     *
     * If invoice generation genuinely fails for a real, different
     * reason (missing pricing data, session not published, etc.), the
     * Enrollment itself is NOT rolled back -- it already, genuinely
     * succeeded, as its own separate, real Java-side operation -- but
     * a clear warning is shown so staff know to generate the invoice
     * manually afterward, rather than that gap going unnoticed.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'client_id' => ['required', 'integer'],
            'session_id' => ['required', 'integer'],
            // Michael, 2026-08-25 -- Client Portal Enroll rebuild
            // carried over to admin's own Manual Enroll: the same
            // three-way choice, now required here too rather than
            // silently defaulting to FIELD_ONLY as it did before this
            // form was updated.
            'components' => ['required', 'string', 'in:LECTURE_ONLY,FIELD_ONLY,BOTH'],
        ]);

        try {
            $enrollment = $this->engine->createEnrollment(
                (int) $validated['student_id'],
                (int) $validated['client_id'],
                (int) $validated['session_id'],
                $validated['components'],
            );
        } catch (ComplianceEngineConflictException $e) {
            // Michael, 2026-08-23 -- surfaces the VR-eligibility and
            // client-authorization gates just built. Sent back to the
            // form (not a generic error page) with everything they'd
            // picked preserved via withInput(), so staff can see why
            // it failed and correct it rather than starting over.
            return back()->withInput()->with('status', 'Could not enroll: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('status', 'Could not create enrollment -- check the server log for the actual cause.');
        }

        $invoiceWarning = null;
        try {
            $session = $this->engine->getSession((int) $validated['session_id']);
            $schoolType = $session['schoolType'] ?? null;

            if ($schoolType === 'PUBLIC') {
                // Michael, 2026-09-03 -- "BOTH" returns TWO separate
                // enrollment objects (Lecture + Field), not one --
                // every real ID needs invoicing together, not just the
                // first one in the array, or the second component
                // would be silently left un-invoiced.
                $enrollmentIds = array_values(array_filter(array_map(
                    fn ($e) => is_array($e) ? ($e['id'] ?? null) : null,
                    array_is_list($enrollment) ? $enrollment : [$enrollment]
                )));
                if (!empty($enrollmentIds)) {
                    $this->engine->generatePublicSessionInvoice($enrollmentIds);
                }
            } elseif ($schoolType === 'PRIVATE') {
                try {
                    $this->engine->generatePrivateSessionInvoice((int) $validated['session_id']);
                } catch (ComplianceEngineConflictException $e) {
                    // Already generated for this session -- expected,
                    // not an error, for the 2nd+ student manually
                    // enrolled here. Silently treated as success.
                }
            }
            // SEMI_PRIVATE/PROPOSED/VTCA -- deliberately no invoice call at all.
        } catch (\Throwable $e) {
            $invoiceWarning = 'Enrolled, but could not auto-generate the QBO invoice -- ' . $e->getMessage() . '. Generate it manually.';
        }

        if ($invoiceWarning !== null) {
            return redirect()
                ->route('admin.sessions.roster', ['session' => $validated['session_id']])
                ->with('status', $invoiceWarning);
        }

        return redirect()
            ->route('admin.sessions.roster', ['session' => $validated['session_id']])
            ->with('success', 'Enrolled and invoiced successfully.');
    }
}
