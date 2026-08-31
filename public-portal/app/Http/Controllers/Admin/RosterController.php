<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Section 4f/4g: staff-facing Roster -- per-student Status (Arr/Certified/
 * DNC/DNA), Lecture, Lecture Complete, Certification Run, Practice Run,
 * plus Company/enrollment-date/payment-status for Public/VR sessions.
 * Linked both ways with Session Details (Section 4c).
 */
class RosterController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function show(int $sessionId): View
    {
        return view('admin.roster', [
            'session' => $this->engine->getSession($sessionId),
            'roster' => $this->engine->getRoster($sessionId),
            'summaryEmailReady' => $this->engine->isSummaryEmailReady($sessionId),
        ]);
    }

    /**
     * Section 4g: Arr and Certified are normally system-set (Digital
     * Testing sign-in, passing certification) -- this action is primarily
     * for staff setting DNC/DNA based on what actually happened.
     */
    public function updateStatus(Request $request, int $enrollmentId): RedirectResponse
    {
        $validated = $request->validate([
            'roster_status' => ['required', 'in:ARR,CERTIFIED,DNC,DNA'],
            'session_id' => ['required', 'integer'],
        ]);

        $this->engine->updateRosterStatus($enrollmentId, $validated['roster_status']);

        return redirect()
            ->route('admin.sessions.roster', ['session' => $validated['session_id']])
            ->with('status', 'Roster status updated.');
    }

    /** Section 4g: only reachable once summaryEmailReady is true -- the button is disabled otherwise in the view. */
    public function sendSummaryEmail(int $sessionId): RedirectResponse
    {
        $this->engine->sendSummaryEmail($sessionId);

        return redirect()
            ->route('admin.sessions.roster', ['session' => $sessionId])
            ->with('status', 'Client summary email sent.');
    }
}
