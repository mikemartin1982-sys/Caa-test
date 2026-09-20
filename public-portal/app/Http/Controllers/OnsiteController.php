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

    public function index(): View
    {
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

    public function show(int $sessionId): View|RedirectResponse
    {
        try {
            $session = $this->engine->getSession($sessionId);
            $roster = $this->engine->getRoster($sessionId);
        } catch (\Throwable $e) {
            return redirect()->route('onsite.index')
                ->withErrors(['session_id' => 'Session not found. Double-check the ID with your instructor.']);
        }

        return view('onsite.roster', [
            'session' => $session,
            'roster' => $roster,
            'signInOpen' => ($session['onsiteStage'] ?? null) === 'SIGN_IN',
        ]);
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

        return view('onsite.waiting', [
            'sessionId' => $sessionId,
            'enrollmentId' => (int) $validated['enrollment_id'],
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
