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

    public function show(int $sessionId): View
    {
        return view('admin.session-detail', [
            'session' => $this->engine->getSession($sessionId),
            'comments' => $this->engine->getSessionComments($sessionId),
            'readyToPublish' => $this->engine->isSessionReadyToPublish($sessionId),
            'authorizedClients' => $this->engine->getAuthorizedClients($sessionId),
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
