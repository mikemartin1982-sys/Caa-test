<?php

namespace App\Http\Controllers;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Digital Testing student sign-in -- matches stacktest.net's real URL
 * structure ("resolves to compliance-assurance.com/onsite/", per
 * Michael, 2026-08-16). No staff auth on any of these routes; students
 * aren't staff. The roster lookup itself DOES need authenticated API
 * access, but that call happens here, server-side, using the same
 * ComplianceEngineClient/service-account credentials the rest of this
 * app already uses -- the student's browser never touches the API or
 * its credentials directly.
 *
 * The Sign-In -> Practice -> Testing -> Closed stage is now REAL,
 * driven by Session's onsiteSignInEnabled/onsiteTestingEnabled fields
 * (a Field Manager control, set via the general Session update
 * endpoint -- no dedicated UI for it yet, that's the next piece). The
 * waiting screen polls stage() below and actually reacts once the
 * Field Manager moves off Sign-In, rather than sitting static forever.
 */
class OnsiteController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    /**
     * Session key holding the student's check-in for a given onsite
     * Session ID, e.g. "onsite.checkin.42" -> ['enrollmentId' => 7,
     * 'studentName' => 'Jane Doe']. Keyed per sessionId (not a single
     * flat key) so a device that gets reused across multiple onsite
     * sessions -- a shared classroom tablet, say -- doesn't leak one
     * student's check-in into a different Session ID.
     */
    protected function checkInSessionKey(int $sessionId): string
    {
        return "onsite.checkin.{$sessionId}";
    }

    public function index(Request $request): View|RedirectResponse
    {
        // If this browser already checked in to a session that's still
        // running, skip straight back in instead of asking for the
        // Session ID again -- this is what actually survives the tab
        // closing/reopening, since checkIn() below now persists it here
        // rather than only in that one page's render. put('onsite.checkin.<id>', ...)
        // nests into a real array, so this reads back as [sessionId => checkIn, ...].
        foreach ((array) $request->session()->get('onsite.checkin', []) as $sessionId => $checkIn) {
            $sessionId = (int) $sessionId;
            try {
                $session = $this->engine->getSession($sessionId);
            } catch (\Throwable $e) {
                continue;
            }
            $resume = $this->resumeRedirectFor($sessionId, $session, $checkIn);
            if ($resume !== null) {
                return $resume;
            }
            // Stage moved to something we can't resume into (e.g. back to
            // SIGN_IN, or CLOSED) -- stop carrying stale state forward.
            $request->session()->forget($this->checkInSessionKey($sessionId));
        }

        return view('onsite.index');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
        ]);

        try {
            $this->engine->getSession((int) $validated['session_id']);
        } catch (\Throwable $e) {
            return back()->withErrors(['session_id' => 'Session not found. Double-check the ID with your instructor.']);
        }

        return redirect()->route('onsite.show', ['sessionId' => $validated['session_id']]);
    }

    public function show(Request $request, int $sessionId): View|RedirectResponse
    {
        try {
            $session = $this->engine->getSession($sessionId);
            $roster = $this->engine->getRoster($sessionId);
        } catch (\Throwable $e) {
            return redirect()->route('onsite.index')
                ->withErrors(['session_id' => 'Session not found. Double-check the ID with your instructor.']);
        }

        // Already checked in to this session (e.g. reopened the tab
        // while waiting or testing) -- resume rather than showing the
        // roster/sign-in form again.
        $checkIn = $request->session()->get($this->checkInSessionKey($sessionId));
        if ($checkIn !== null) {
            $resume = $this->resumeRedirectFor($sessionId, $session, $checkIn);
            if ($resume !== null) {
                return $resume;
            }
            $request->session()->forget($this->checkInSessionKey($sessionId));
        }

        return view('onsite.roster', [
            'session' => $session,
            'roster' => $roster,
            'signInOpen' => ($session['onsiteStage'] ?? null) === 'SIGN_IN',
        ]);
    }

    /**
     * Where a stored check-in should resume to, given the session's
     * current stage -- null if the stage has moved past what a stored
     * check-in can resume into (back to SIGN_IN, or CLOSED), in which
     * case the caller drops the stored state and falls through to the
     * normal flow.
     */
    protected function resumeRedirectFor(int $sessionId, array $session, array $checkIn): ?RedirectResponse
    {
        return match ($session['onsiteStage'] ?? null) {
            'SIGN_IN', 'PRACTICE' => redirect()->route('onsite.waiting', [
                'sessionId' => $sessionId,
                'enrollmentId' => $checkIn['enrollmentId'],
            ]),
            'TESTING' => redirect()->route('onsite.test', [
                'sessionId' => $sessionId,
                'enrollmentId' => $checkIn['enrollmentId'],
            ]),
            default => null,
        };
    }

    public function checkIn(Request $request, int $sessionId): View|RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'integer'],
        ]);

        $session = $this->engine->getSession($sessionId);
        if (($session['onsiteStage'] ?? null) !== 'SIGN_IN') {
            return redirect()->route('onsite.show', ['sessionId' => $sessionId])
                ->withErrors(['enrollment_id' => 'Sign-in has closed for this session. See your instructor.']);
        }

        $roster = $this->engine->getRoster($sessionId);
        $entry = collect($roster)->firstWhere('enrollmentId', (int) $validated['enrollment_id']);

        if ($entry === null) {
            return redirect()->route('onsite.show', ['sessionId' => $sessionId])
                ->withErrors(['enrollment_id' => 'That student is not enrolled in this session.']);
        }

        $rosterStatus = $entry['rosterStatus'] ?? null;
        if ($rosterStatus !== null && $rosterStatus !== 'ARR') {
            return redirect()->route('onsite.show', ['sessionId' => $sessionId])
                ->withErrors(['enrollment_id' => 'This enrollment is not eligible to sign in. See your instructor.']);
        }

        // A successful student check-in is the source of truth for ARR.
        // Do this before rendering the waiting screen so Start Test sees
        // every signed-in student when it creates the class's runs.
        if ($rosterStatus !== 'ARR') {
            $this->engine->updateRosterStatus((int) $validated['enrollment_id'], 'ARR');
        }

        // Persist the check-in server-side so the student can close the
        // tab (lock the screen, browser crash, whatever) and come back
        // in -- previously this only ever lived in this one page's
        // render, so closing the tab genuinely lost it. Mirrors the
        // Chart Recorder's lecture course, which does the same with
        // request.session()->put('lecture_student_id', ...).
        $request->session()->put($this->checkInSessionKey($sessionId), [
            'enrollmentId' => (int) $validated['enrollment_id'],
            'studentName' => $entry['studentName'] ?? 'Student',
        ]);

        // Redirect into the GET waiting screen rather than rendering it
        // here directly, so there's one code path for that screen --
        // the same one a resumed check-in (index()/show() above) lands
        // on -- instead of two places building the same view.
        return redirect()->route('onsite.waiting', [
            'sessionId' => $sessionId,
            'enrollmentId' => (int) $validated['enrollment_id'],
        ]);
    }

    /**
     * GET waiting screen -- reached either fresh, right after checkIn()
     * redirects here, or later when a stored check-in (index()/show()
     * above) sends a returning student straight back to it.
     */
    public function waiting(Request $request, int $sessionId): View|RedirectResponse
    {
        $validated = $request->validate([
            'enrollmentId' => ['required', 'integer'],
        ]);
        $enrollmentId = (int) $validated['enrollmentId'];

        try {
            $session = $this->engine->getSession($sessionId);
        } catch (\Throwable $e) {
            return redirect()->route('onsite.index')
                ->withErrors(['session_id' => 'Session not found. Double-check the ID with your instructor.']);
        }

        // Stage already moved on (e.g. this tab was closed through the
        // whole Practice stage and reopened once Testing had started) --
        // send it straight to the test rather than stranding it here.
        if (($session['onsiteStage'] ?? null) === 'TESTING') {
            return redirect()->route('onsite.test', [
                'sessionId' => $sessionId,
                'enrollmentId' => $enrollmentId,
            ]);
        }

        $roster = $this->engine->getRoster($sessionId);
        $entry = collect($roster)->firstWhere('enrollmentId', $enrollmentId);

        return view('onsite.waiting', [
            'sessionId' => $sessionId,
            'enrollmentId' => $enrollmentId,
            'studentName' => $entry['studentName'] ?? 'Student',
        ]);
    }

    /**
     * JSON polling target for the waiting screen's JS -- public, no
     * staff auth, since students poll this from their own browsers.
     * Returns just the inferred stage, nothing else about the session.
     */
    public function stage(int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = $this->engine->getSession($sessionId);
        return response()->json(['stage' => $session['onsiteStage'] ?? null]);
    }

    /**
     * The actual test-taking webapp (Michael, 2026-08-17) -- the
     * waiting screen redirects here once Testing stage begins. Renders
     * once server-side with the student's current status for the
     * initial page load; the page's own JS polls testStatus() below
     * for live updates rather than reloading the whole page.
     */
    public function test(Request $request, int $sessionId): View
    {
        $validated = $request->validate([
            'enrollmentId' => ['required', 'integer'],
        ]);
        $enrollmentId = (int) $validated['enrollmentId'];

        $status = $this->engine->getMyLiveTestStatus($sessionId, $enrollmentId);

        // Student name isn't part of my-status -- look it up once here
        // for the top bar, same roster lookup checkIn() already uses.
        $roster = $this->engine->getRoster($sessionId);
        $entry = collect($roster)->firstWhere('enrollmentId', $enrollmentId);

        return view('onsite.test', [
            'sessionId' => $sessionId,
            'enrollmentId' => $enrollmentId,
            'studentName' => $entry['studentName'] ?? 'Student',
            'status' => $status,
        ]);
    }

    /** JSON polling target for the testing page's JS -- refreshes point/status without a full reload. */
    public function testStatus(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['enrollmentId' => ['required', 'integer']]);
        return response()->json($this->engine->getMyLiveTestStatus($sessionId, (int) $validated['enrollmentId']));
    }

    /** AJAX target for the testing page's Record buttons -- one point at a time, no page reload. */
    public function submitTestGuess(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'enrollmentId' => ['required', 'integer'],
            'guess' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $this->engine->submitLiveTestGuess($sessionId, (int) $validated['enrollmentId'], (int) $validated['guess']);
            return response()->json(['submitted' => true]);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            // e.g. true value not recorded yet, already submitted, or test isn't active --
            // the page's own polling will reflect the real state regardless.
            return response()->json(['submitted' => false, 'error' => $e->getMessage()], 409);
        }
    }

    /** AJAX target for the "these answers are your own" confirmation dialog -- Michael, 2026-08-17. */
    public function confirmTestFinalAnswers(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['enrollmentId' => ['required', 'integer']]);

        try {
            $this->engine->confirmLiveTestFinalAnswers($sessionId, (int) $validated['enrollmentId']);
            return response()->json(['confirmed' => true]);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return response()->json(['confirmed' => false, 'error' => $e->getMessage()], 409);
        }
    }

    /** AJAX target for the signature canvas -- only ever called after a passing grade; a failing student never reaches this. */
    public function submitTestSignature(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'enrollmentId' => ['required', 'integer'],
            'signatureDataUrl' => ['required', 'string'],
        ]);

        try {
            $this->engine->submitLiveTestSignature($sessionId, (int) $validated['enrollmentId'], $validated['signatureDataUrl']);
            return response()->json(['submitted' => true]);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return response()->json(['submitted' => false, 'error' => $e->getMessage()], 409);
        }
    }
}
