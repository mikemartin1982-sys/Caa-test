<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Digital-Testing Admin -- deliberately pulled OUT of Session Details
 * into its own standalone page, matching the real DIBs layout (a
 * "Choose a Session" picker up top, everything else scoped to that one
 * session below it). Confirmed with Michael, 2026-08-16: everything
 * about Digital Testing runs through this page, not Session Details --
 * an earlier version of this had it folded into the Session Details
 * combined form, which was the wrong call and got reverted.
 */
class DigitalTestingAdminController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function index(Request $request): View
    {
        $sessions = $this->engine->listSessions();
        $selectedId = $request->query('session_id');
        $selectedSession = $selectedId ? $this->engine->getSession((int) $selectedId) : null;
        $roster = $selectedId ? $this->engine->getRoster((int) $selectedId) : [];
        $liveTestStatus = $selectedId ? $this->engine->getLiveTestStatus((int) $selectedId) : null;
        $splitRunCandidates = $selectedId ? $this->engine->getSplitRunCandidates((int) $selectedId) : [];

        return view('admin.digital-testing', [
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'roster' => $roster,
            'liveTestStatus' => $liveTestStatus,
            'splitRunCandidates' => $splitRunCandidates,
        ]);
    }

    /**
     * JSON polling target for the Live Test panel's JS (Michael,
     * 2026-08-17, found live during testing: points now auto-advance
     * in the background from student submissions, but this page never
     * self-updated to reflect that -- same auto-refresh pattern the
     * student testing page already uses, reusing the exact same
     * getLiveTestStatus() call the page's own initial render already
     * makes.
     */
    public function liveTestStatusJson(int $sessionId): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->engine->getLiveTestStatus($sessionId));
    }

    /**
     * Accepts a single stage selection (radio buttons in the view) and
     * translates it server-side into the underlying two booleans --
     * CLOSED maps to both=true, which is otherwise an unused
     * combination, chosen specifically so marking testing done here
     * never touches the session's real Closed Out/billing-lock flag.
     * See Session.getOnsiteStage()'s Javadoc for the full reasoning.
     */
    public function update(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'in:SIGN_IN,PRACTICE,TESTING,CLOSED'],
        ]);

        [$signIn, $testing] = match ($validated['stage']) {
            'SIGN_IN' => [true, false],
            'PRACTICE' => [false, false],
            'TESTING' => [false, true],
            'CLOSED' => [true, true],
        };

        $this->engine->updateSession($sessionId, [
            'onsiteSignInEnabled' => $signIn,
            'onsiteTestingEnabled' => $testing,
        ]);

        return redirect()->route('admin.digital-testing.index', ['session_id' => $sessionId])
            ->with('status', 'Digital Testing stage updated.');
    }

    /**
     * Batch Field Stat changes from the Digital-Testing Admin roster --
     * staff can change several students' status, then submit them all
     * at once (Michael, 2026-08-16: no per-row autosave here, since
     * that means a page reload per change on a table that could have
     * many rows -- unlike the single, rarely-changed stage control
     * above, which stays autosave).
     */
    public function updateRosterStatuses(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'roster_status' => ['array'],
            'roster_status.*' => ['nullable', 'in:ARR,CERTIFIED,DNC,DNA'],
        ]);

        foreach ($validated['roster_status'] ?? [] as $enrollmentId => $status) {
            if ($status === null || $status === '') {
                continue; // "none" left untouched -- not a real selection, skip it
            }
            $this->engine->updateRosterStatus((int) $enrollmentId, $status);
        }

        return redirect()->route('admin.digital-testing.index', ['session_id' => $sessionId])
            ->with('status', 'Roster changes saved.');
    }

    // -----------------------------------------------------------------
    // Live Digital Testing -- Operator controls. Each of these is a
    // real, distinct backend action with its own conflict conditions
    // (e.g. "not everyone's submitted yet"), so each stays a separate
    // form/redirect rather than autosave, and each surfaces the API's
    // real error message on conflict rather than a generic failure.
    // -----------------------------------------------------------------

    private function backToSession(int $sessionId, string $status): RedirectResponse
    {
        return redirect()->route('admin.digital-testing.index', ['session_id' => $sessionId])
            ->with('status', $status);
    }

    private function backWithConflict(int $sessionId, \App\Services\ComplianceEngine\ComplianceEngineConflictException $e): RedirectResponse
    {
        return redirect()->route('admin.digital-testing.index', ['session_id' => $sessionId])
            ->withErrors(['live_test' => $e->getMessage()]);
    }

    public function startLiveTest(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'split_run_enrollment_ids' => ['array'],
            'split_run_enrollment_ids.*' => ['integer'],
        ]);

        try {
            $ids = array_map('intval', $validated['split_run_enrollment_ids'] ?? []);
            $this->engine->startLiveTest($sessionId, $ids ?: null);
            return $this->backToSession($sessionId, 'Live test started.');
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return $this->backWithConflict($sessionId, $e);
        }
    }

    public function recordTrueValue(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'true_opacity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $this->engine->recordLiveTestTrueValue($sessionId, (int) $validated['true_opacity']);
            return $this->backToSession($sessionId, 'True value recorded.');
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return $this->backWithConflict($sessionId, $e);
        }
    }

    public function advanceLiveTest(int $sessionId): RedirectResponse
    {
        try {
            $this->engine->advanceLiveTest($sessionId);
            return $this->backToSession($sessionId, 'Advanced to the next point.');
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return $this->backWithConflict($sessionId, $e);
        }
    }

    /** Grades whoever's currently ready -- repeatable, not a one-time end-of-test action (Michael, 2026-08-17). */
    public function gradeLiveTest(int $sessionId): RedirectResponse
    {
        try {
            $result = $this->engine->gradeLiveTest($sessionId);
            $graded = $result['graded'] ?? 0;
            return $this->backToSession($sessionId, "Graded {$graded} student(s).");
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return $this->backWithConflict($sessionId, $e);
        }
    }

    public function revisitPoint(Request $request, int $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'point_number' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            $this->engine->revisitLiveTestPoint($sessionId, (int) $validated['point_number']);
            return $this->backToSession($sessionId, 'Revisiting point ' . $validated['point_number'] . '.');
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return $this->backWithConflict($sessionId, $e);
        }
    }

    public function endRevisit(int $sessionId): RedirectResponse
    {
        try {
            $this->engine->endLiveTestRevisit($sessionId);
            return $this->backToSession($sessionId, 'Revisit ended.');
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return $this->backWithConflict($sessionId, $e);
        }
    }
}
