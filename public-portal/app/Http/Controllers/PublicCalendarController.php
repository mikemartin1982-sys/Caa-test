<?php

namespace App\Http\Controllers;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Section 4e: the Public Calendar site -- calendar grid, state map, and
 * flat list views of Public sessions only. No login required. Matches the
 * reference site's three-view pattern (calendar.php / training-map.php /
 * a location-date list).
 */
class PublicCalendarController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    /** Month-grid view (calendar.php equivalent). */
    public function calendar(Request $request): View
    {
        $sessions = $this->engine->listSessions([
            'schoolType' => 'PUBLIC',
            'published' => true,
        ]);

        return view('public.calendar', ['sessions' => $sessions]);
    }

    /**
     * Click-a-state map view (training-map.php equivalent). Michael,
     * 2026-09-27 -- groups published public sessions by state for the
     * clickable US map; only the few fields the page shows are passed
     * through, never the full session records.
     */
    public function map(Request $request): View
    {
        $sessions = $this->engine->listSessions([
            'schoolType' => 'PUBLIC',
            'published' => true,
            'region' => $request->query('region'),
        ]);

        $schoolsByState = collect($sessions)
            ->filter(fn ($s) => !empty($s['addressState']))
            ->groupBy(fn ($s) => strtoupper($s['addressState']))
            ->map(fn ($group) => $group->map(fn ($s) => [
                'name' => $s['locationName'] ?? 'Smoke School',
                'city' => $s['addressCity'] ?? '',
                'url' => route('public.session-detail', ['state' => strtolower($s['addressState']), 'slug' => $s['id']]),
            ])->sortBy('city')->values())
            ->sortKeys();

        return view('public.map', ['schoolsByState' => $schoolsByState]);
    }

    /** Flat location/date list view. */
    public function list(Request $request): View
    {
        $sessions = $this->engine->listSessions([
            'schoolType' => 'PUBLIC',
            'published' => true,
        ]);

        return view('public.list', ['sessions' => $sessions]);
    }
}
