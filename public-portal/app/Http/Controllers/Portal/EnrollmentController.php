<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineConflictException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Section 4e: client-initiated enrollment, with three distinct paths:
 *  - Private/Semi-Private: only visible if the client is on the session's
 *    authorized-client list AND the session is published.
 *  - Public: any published Public session.
 *  - VR: the Public VR Session, gated by the client's vrClient flag
 *    (Section 4b) -- not implemented in this scaffold pass, same pattern
 *    as the other two once the VR-specific endpoints are needed.
 */
class EnrollmentController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'session_id' => ['required', 'integer'],
        ]);

        $clientId = $request->user()->client_id;

        $this->engine->createEnrollment(
            studentId: $validated['student_id'],
            clientId: $clientId,
            sessionId: $validated['session_id'],
        );

        return redirect()
            ->route('portal.dashboard')
            ->with('status', 'Enrollment submitted.');
    }

    /**
     * Section 4: host client self-service authorization of an outside
     * organization on a Semi-Private session. Surfaces a friendly message
     * on the 409 a Private session would return, rather than a raw error.
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
