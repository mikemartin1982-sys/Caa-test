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

    /**
     * Michael, 2026-08-29 -- staff calendar UI (the real, visual month
     * grid, not the flat list this used to be -- see this view's own
     * prior comment, explicitly documented as a deliberate first pass,
     * not the final format). getSessionCalendar() now requires an
     * explicit date range (previously unscoped, loading every session
     * ever created -- the exact performance issue Phase 1 fixed) and
     * returns {entries, availability} instead of a plain array.
     *
     * Grid-building (weeks/days, leading/trailing blank cells so the
     * month lines up correctly under Sun-Sat headers, grouping entries
     * and availability by date) happens here, not in the view --
     * matching this project's own established "controllers prepare
     * data, views render" pattern throughout. viewingMonth is the
     * single source of truth for "which month" -- the actual query
     * range (gridStart/gridEnd) and the prev/next navigation links are
     * always derived from it, never independently settable, so they
     * can't drift out of sync with each other.
     */
    public function index(Request $request): View
    {
        $schoolType = $request->query('schoolType');

        $monthParam = $request->query('month');
        $viewingMonth = $monthParam
            ? \Illuminate\Support\Carbon::parse($monthParam . '-01', 'America/Chicago')
            : now('America/Chicago')->startOfMonth();

        // Michael, 2026-08-29 -- found while building this: the grid
        // itself includes leading/trailing spillover days from
        // adjacent months (to fill out complete weeks under the
        // Sun-Sat headers), but the API call was only ever scoped to
        // the month's own start/end -- any real session falling on a
        // spillover date would show the correct DATE (computed
        // locally) but never actually have its data fetched at all.
        // Query the full grid range instead, still a bounded, explicit
        // range (never more than 6 extra days each side), not the
        // unscoped-growth pattern the performance audit fixed earlier.
        $gridStart = $viewingMonth->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $gridEnd = $viewingMonth->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SATURDAY);

        $calendar = $this->engine->getSessionCalendar($gridStart->toDateString(), $gridEnd->toDateString(), $schoolType ?: null);
        $entries = $calendar['entries'] ?? [];
        $availability = $calendar['availability'] ?? [];

        $entriesByDate = collect($entries)->groupBy('date');
        $availabilityByDate = collect($availability)->keyBy('date');

        $weeks = [];
        $week = [];
        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $dateString = $day->toDateString();
            $week[] = [
                'date' => $day->copy(),
                'dateString' => $dateString,
                'inCurrentMonth' => $day->month === $viewingMonth->month,
                'sessions' => $entriesByDate->get($dateString, collect())->all(),
                'availability' => $availabilityByDate->get($dateString),
            ];
            if ($day->dayOfWeek === \Carbon\Carbon::SATURDAY) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return view('admin.calendar', [
            'weeks' => $weeks,
            'viewingMonth' => $viewingMonth,
            'prevMonth' => $viewingMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $viewingMonth->copy()->addMonth()->format('Y-m'),
            // Michael, 2026-08-29 -- staff calendar UI, quick-jump nav
            // (matching DIBs' own <<12 <<6 <<3 ... 3>> 6>> 12>> row).
            // Same derive-from-viewingMonth pattern as prevMonth/nextMonth
            // above -- every nav link is computed from the one real
            // source of truth, never independently settable.
            'back3' => $viewingMonth->copy()->subMonths(3)->format('Y-m'),
            'back6' => $viewingMonth->copy()->subMonths(6)->format('Y-m'),
            'back12' => $viewingMonth->copy()->subMonths(12)->format('Y-m'),
            'fwd3' => $viewingMonth->copy()->addMonths(3)->format('Y-m'),
            'fwd6' => $viewingMonth->copy()->addMonths(6)->format('Y-m'),
            'fwd12' => $viewingMonth->copy()->addMonths(12)->format('Y-m'),
            'selectedSchoolType' => $schoolType,
        ]);
    }

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop).
     * This project's browser JS never talks to the Java API directly --
     * always through Laravel, which handles auth/session -- so the
     * frontend's "Save Changes" button calls this, not the Java
     * endpoint's URL directly. A thin, real proxy: takes the staged
     * changes as JSON, forwards them, returns the same per-item
     * success/failure results straight back for the frontend to render.
     */
    public function saveAssignments(Request $request): \Illuminate\Http\JsonResponse
    {
        $changes = $request->json('changes', []);
        $results = $this->engine->saveCalendarAssignments($changes);
        return response()->json($results);
    }
}
