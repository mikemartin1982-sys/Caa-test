<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\View\View;

/**
 * Minimal staff landing page after login -- a session list with links
 * into Session Details/Roster. Not tied to any specific architecture
 * doc section; just the practical "where do you land after logging in."
 */
class AdminDashboardController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function index(): View
    {
        // Michael, 2026-08-29 -- staff calendar, Phase 1: the backend
        // endpoint this calls now requires an explicit date range
        // (previously unscoped, since it loaded every session ever
        // created -- exactly the performance issue Phase 1 fixed), and
        // its response shape changed from a plain array of sessions to
        // {entries: [...], availability: [...]}. This dashboard only
        // ever needs today/tomorrow, so that's the range requested
        // directly rather than fetching anything wider.
        $today = now('America/Chicago')->toDateString();
        $tomorrow = now('America/Chicago')->addDay()->toDateString();

        $calendar = $this->engine->getSessionCalendar($today, $tomorrow);
        $entries = $calendar['entries'] ?? [];

        return view('admin.dashboard', [
            'newMailboxCount' => config('mailbox.enabled') ? \App\Models\MailboxMessage::status('new')->count() : 0,
            'todaySessions' => array_values(array_filter($entries, fn ($s) => ($s['date'] ?? null) === $today)),
            'tomorrowSessions' => array_values(array_filter($entries, fn ($s) => ($s['date'] ?? null) === $tomorrow)),
        ]);
    }
}
