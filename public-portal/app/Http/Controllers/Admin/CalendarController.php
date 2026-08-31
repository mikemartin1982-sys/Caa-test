<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff-facing calendar -- unlike the public calendar (Public+published
 * only), this shows every school type, since staff need visibility into
 * Private/Semi-Private/VR sessions too.
 */
class CalendarController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function index(Request $request): View
    {
        $schoolType = $request->query('schoolType');

        return view('admin.calendar', [
            'sessions' => $this->engine->getSessionCalendar($schoolType ?: null),
            'selectedSchoolType' => $schoolType,
        ]);
    }
}
