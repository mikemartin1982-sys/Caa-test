<?php
 
namespace App\Http\Controllers\Account;
 
use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineConflictException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
 
/**
 * Michael, 2026-08-24 -- "Enroll in both Traditional and Public VR
 * Session(s)." Reconciled from the orphaned Portal\EnrollmentController
 * (a separate, never-finished auth system -- see
 * Account\ClientAuthController's own docblock) -- that controller's
 * submission logic was sound, only the auth access pattern was wrong
 * for this real guard, same class of fix as AccountDashboardController's
 * own reconciliation.
 *
 * The genuinely new piece here, not present in the orphaned code at
 * all: an actual "which sessions can I even see" list. The orphaned
 * controller's store() took a session_id directly with no browsing UI
 * ever built for it -- this uses the new clientId filter on
 * GET /sessions (SessionController, Java side), which reuses the exact
 * same two eligibility checks enrollment submission itself already
 * enforces, so nothing can appear in this list and then be rejected
 * on submit.
 */
class EnrollmentController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }
 
    public function index(Request $request): View
    {
        $client = $request->user();
 
        return view('account.enroll', [
            'client' => $client,
            'employees' => $this->engine->listClientEmployees($client->id),
            'eligibleSessions' => $this->engine->listSessions(['clientId' => $client->id]),
        ]);
    }
 
    /**
     * Michael, 2026-08-24 -- "Current Enrollments": same information a
     * staff member sees on a session's own Roster page, but scoped to
     * just this client's own employees, across every session -- not
     * one session's full roster. Read-only; no status editing or
     * unenroll here, those stay staff-only tools on the admin roster.
     */
    public function currentEnrollments(Request $request): View
    {
        $client = $request->user();
 
        return view('account.current-enrollments', [
            'client' => $client,
            'enrollments' => $this->engine->listCurrentEnrollments($client->id),
        ]);
    }
 
    /**
     * Michael, 2026-08-25 -- Client Portal Enroll rebuild: one row per
     * employee (matching the real source's own shape, per Michael),
     * each with its own session + Lecture/Field/Both/None choice,
     * submitted together. Each employee's submission is independent --
     * one failing (already enrolled, etc.) doesn't block the others,
     * matching the real source's own "only completed selections get
     * submitted" behavior. "Both" atomicity (both rows or neither) is
     * still guaranteed, but at the Java layer, within ONE employee's
     * own createEnrollment() call -- not something this loop needs to
     * coordinate itself.
     *
     * Same structural safeguard as every other client-facing write:
     * the client id passed to createEnrollment() is always
     * $request->user()->id, never taken from this form. Every
     * submitted student_id is re-validated against this client's own
     * roster before anything is sent -- the form only ever renders
     * this client's own employees as rows, but that's a UI convenience,
     * not a guarantee; a tampered student_id key is still checked
     * server-side, not just hidden by what the form shows. This check
     * lives here, not on the shared Java endpoint, because the Java
     * side deliberately allows a student's employer and an
     * enrollment's client to differ (contractor/third-party cases,
     * confirmed intentional) -- restricting it there would break that
     * legitimate admin-side case; this path specifically needs the
     * hard rule instead.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session' => ['nullable', 'array'],
            'session.*' => ['nullable', 'integer'],
            'components' => ['nullable', 'array'],
            'components.*' => ['nullable', 'string', 'in:LECTURE_ONLY,FIELD_ONLY,BOTH'],
        ]);
 
        $clientId = $request->user()->id;
        // Michael, 2026-08-25 -- found live during testing: a mixed
        // submission (some employees succeeding, some failing on real,
        // independent conflicts) produced correct per-employee results,
        // but the failure messages were generic -- "this student is
        // already enrolled..." repeated with no name, so which failure
        // belonged to which employee wasn't identifiable when several
        // failed in the same batch. Keeping the full employee list
        // (not just the id list) here so each failure can be attributed
        // by name.
        $employeesById = collect($this->engine->listClientEmployees($clientId))->keyBy('id');
 
        $succeeded = [];
        $failed = [];
 
        foreach (($validated['session'] ?? []) as $studentId => $sessionId) {
            $components = $validated['components'][$studentId] ?? null;
            // Michael, 2026-08-25 -- "None" is not a stored value, it's
            // simply "nothing selected for this row" -- an empty
            // session or components means this employee is skipped
            // entirely, not submitted with a blank/invalid value.
            if (empty($sessionId) || empty($components)) {
                continue;
            }
 
            $studentId = (int) $studentId;
            $employee = $employeesById->get($studentId);
            $employeeLabel = $employee['name'] ?? "Employee #{$studentId}";
 
            if (!$employee) {
                $failed[] = "{$employeeLabel}: not on your roster.";
                continue;
            }
 
            try {
                $this->engine->createEnrollment($studentId, $clientId, (int) $sessionId, $components);
                $succeeded[] = $employeeLabel;
            } catch (ComplianceEngineConflictException $e) {
                $failed[] = "{$employeeLabel}: " . $e->getMessage();
            }
        }
 
        if (empty($succeeded) && empty($failed)) {
            return back()->with('status', 'No employees had a completed selection to enroll.');
        }
 
        $status = '';
        if (!empty($succeeded)) {
            $status .= implode(', ', $succeeded) . ' enrolled successfully.';
        }
        if (!empty($failed)) {
            $status .= ($status ? ' ' : '') . implode(' ', $failed);
        }
 
        return redirect()->route('account.enroll')->with('status', $status);
    }
 
    /**
     * Section 4: host client self-service authorization of an outside
     * organization on a Semi-Private session. Reconciled from the
     * orphaned Portal\EnrollmentController -- logic unchanged, only
     * the auth access pattern fixed.
     */
    public function authorizeOutsideClient(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
            'client_id' => ['required', 'integer'],
        ]);
 
        try {
            $this->engine->authorizeOutsideClient($validated['session_id'], $validated['client_id'], addedByStaffId: null);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withErrors(['client_id' => 'This session does not allow outside attendance.']);
        }
 
        return back()->with('status', 'Organization authorized to enroll employees in this session.');
    }
}