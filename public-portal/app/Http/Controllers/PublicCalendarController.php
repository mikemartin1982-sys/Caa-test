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

    /** Click-a-state map view (training-map.php equivalent). Uses grid coordinates, Section 4d. */
    public function map(Request $request): View
    {
        $sessions = $this->engine->listSessions([
            'schoolType' => 'PUBLIC',
            'published' => true,
            'region' => $request->query('region'),
        ]);

        return view('public.map', ['sessions' => $sessions]);
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
