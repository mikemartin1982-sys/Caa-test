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

    /**
     * Michael, 2026-09-27 -- fixed: this took (int $sessionId), but Laravel
     * passes route parameters in order, so it received {state} ("tx") and
     * every session page errored. The session id is the trailing number of
     * {slug}, which accepts both our links (/smoke-schools/tx/8577) and the
     * live site's (/smoke-schools/tx/new-braunfels-09-22-2026-8577), so old
     * links and search results keep working.
     */
    public function show(string $state, string $slug): View
    {
        abort_unless(preg_match('/(\d+)$/', $slug, $m), 404);

        try {
            $session = $this->engine->getSession((int) $m[1]);
        } catch (\RuntimeException $e) {
            abort_if(str_contains($e->getMessage(), '[404]'), 404);
            throw $e;
        }

        abort_unless(($session['schoolType'] ?? null) === 'PUBLIC', 404);
        abort_unless($session['published'] ?? false, 404);

        return view('public.session-detail', ['session' => $session]);
    }
}
