<?php

namespace App\Http\Controllers;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\View\View;

/**
 * Section 4d: the public session detail page (e.g.
 * /smoke-schools/ky/louisville-08-13-2026-8460). Public sessions only --
 * Private/Semi-Private never render here, they aren't listed publicly.
 *
 * Page includes, per the reference site: location block with a Map link
 * built from grid coordinates (not the text address), a self-service
 * "Directions from" widget, two-part pricing (Field Certification +
 * Self-Paced Lecture), an external-registration override when set, and
 * public-facing session notes.
 */
class SessionDetailController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function show(int $sessionId): View
    {
        $session = $this->engine->getSession($sessionId);

        abort_unless(($session['schoolType'] ?? null) === 'PUBLIC', 404);
        abort_unless($session['published'] ?? false, 404);

        return view('public.session-detail', ['session' => $session]);
    }
}
