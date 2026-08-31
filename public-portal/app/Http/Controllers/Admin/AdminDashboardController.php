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
        $calendar = $this->engine->getSessionCalendar();
        $today = now('America/Chicago')->toDateString();
        $tomorrow = now('America/Chicago')->addDay()->toDateString();

        return view('admin.dashboard', [
            'todaySessions' => array_values(array_filter($calendar, fn ($s) => ($s['date'] ?? null) === $today)),
            'tomorrowSessions' => array_values(array_filter($calendar, fn ($s) => ($s['date'] ?? null) === $tomorrow)),
        ]);
    }
}
