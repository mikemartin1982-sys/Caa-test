<?php

namespace App\Http\Controllers;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use Carbon\CarbonImmutable;
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
    /** The 50 states, USPS code => name (home page "Find Smoke Schools by State", training map ?state=). */
    public const US_STATES = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
        'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
        'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
        'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
        'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
        'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
        'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
        'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
    ];

    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    /**
     * Month-grid view (calendar.php equivalent). Michael, 2026-09-27 --
     * rebuilt as a real month calendar matching the live site: each
     * published public school appears once, on its first day, as
     * "City, ST"; federal holidays are highlighted; browsing starts at
     * the current month and can't go back before it (?month=YYYY-MM).
     * Dates come from the staff calendar endpoint (which is per session
     * DAY); city/state from the session list. Only those few fields
     * reach the page.
     */
    public function calendar(Request $request): View
    {
        $tz = 'America/Chicago';
        $currentMonth = CarbonImmutable::now($tz)->startOfMonth();
        $month = $currentMonth;
        if (preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month'))) {
            $month = CarbonImmutable::createFromFormat('!Y-m', $request->query('month'), $tz)->startOfMonth();
        }
        if ($month->lessThan($currentMonth)) {
            $month = $currentMonth;
        }
        $monthEnd = $month->endOfMonth();

        $cityState = collect($this->engine->listSessions(['schoolType' => 'PUBLIC', 'published' => true]))
            ->filter(fn ($s) => self::isListedSchool($s))
            ->keyBy('id');

        $calendar = $this->engine->getSessionCalendar($month->toDateString(), $monthEnd->toDateString(), 'PUBLIC');

        $schoolsByDate = collect($calendar['entries'] ?? [])
            ->filter(fn ($e) => ($e['published'] ?? false) && !($e['canceled'] ?? false)
                && ($e['isPrimaryDay'] ?? false) && $cityState->has($e['id']))
            ->map(function ($e) use ($cityState) {
                $s = $cityState->get($e['id']);
                $state = strtoupper($s['addressState']);
                return [
                    'date' => $e['date'],
                    'label' => trim(($s['addressCity'] ?? '') . ', ' . $state, ', '),
                    'url' => route('public.session-detail', ['state' => strtolower($state), 'slug' => $e['id']]),
                ];
            })
            ->sortBy('label')
            ->groupBy('date');

        $holidays = self::federalHolidays($month->year);
        $weeks = [];
        $day = $month->startOfWeek(CarbonImmutable::SUNDAY);
        $gridEnd = $monthEnd->endOfWeek(CarbonImmutable::SATURDAY);
        while ($day->lessThanOrEqualTo($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++, $day = $day->addDay()) {
                $inMonth = $day->month === $month->month;
                $date = $day->toDateString();
                $week[] = $inMonth ? [
                    'day' => $day->day,
                    'date' => $date,
                    'holiday' => $holidays[$date] ?? null,
                    'schools' => $schoolsByDate->get($date, collect())->all(),
                ] : null;
            }
            $weeks[] = $week;
        }

        return view('public.calendar', [
            'monthLabel' => $month->format('F Y'),
            'weeks' => $weeks,
            'hasSchools' => $schoolsByDate->isNotEmpty(),
            'prevMonth' => $month->greaterThan($currentMonth) ? $month->subMonth()->format('Y-m') : null,
            'nextMonth' => $month->addMonth()->format('Y-m'),
        ]);
    }

    /** US federal holidays for a year, keyed by Y-m-d (observed dates not shifted). */
    public static function federalHolidays(int $year): array
    {
        // PHP's relative formats need ordinal words ("third monday of january 2026").
        $on = fn (string $which) => CarbonImmutable::parse("$which $year")->toDateString();

        return [
            "$year-01-01" => "New Year's Day",
            $on('third monday of january') => 'Martin Luther King Jr. Day',
            $on('third monday of february') => "Presidents' Day",
            $on('last monday of may') => 'Memorial Day',
            "$year-06-19" => 'Juneteenth',
            "$year-07-04" => 'Independence Day',
            $on('first monday of september') => 'Labor Day',
            $on('second monday of october') => 'Columbus Day',
            "$year-11-11" => 'Veterans Day',
            $on('fourth thursday of november') => 'Thanksgiving Day',
            "$year-12-25" => 'Christmas Day',
        ];
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
            ->filter(fn ($s) => self::isListedSchool($s))
            ->groupBy(fn ($s) => strtoupper($s['addressState']))
            ->map(fn ($group) => $group->map(fn ($s) => [
                'name' => $s['locationName'] ?? 'Smoke School',
                'city' => $s['addressCity'] ?? '',
                'url' => route('public.session-detail', ['state' => strtolower($s['addressState']), 'slug' => $s['id']]),
            ])->sortBy('city')->values())
            ->sortKeys();

        $selectedState = strtoupper((string) $request->query('state'));

        return view('public.map', [
            'schoolsByState' => $schoolsByState,
            'selectedState' => array_key_exists($selectedState, self::US_STATES) ? $selectedState : null,
        ]);
    }

    /**
     * "By Location / Date" list (training-list.php equivalent). Michael,
     * 2026-09-27 -- upcoming published public schools with their dates,
     * sortable by date (default) or state. Accepts the live site's
     * ?orderby=field_start / school_state as well as date / state, so old
     * links keep their sort. Only the fields shown reach the page.
     */
    public function list(Request $request): View
    {
        $orderBy = match ($request->query('orderby')) {
            'state', 'school_state' => 'state',
            default => 'date',
        };
        $today = CarbonImmutable::now('America/Chicago')->toDateString();

        $sessions = collect($this->engine->listSessions(['schoolType' => 'PUBLIC', 'published' => true]))
            ->filter(fn ($s) => self::isListedSchool($s))
            ->keyBy('id');
        $ranges = collect($this->engine->getSessionDateRanges($sessions->keys()->all()))->keyBy('sessionId');

        $schools = $sessions
            ->filter(fn ($s) => $ranges->has($s['id']) && $ranges[$s['id']]['lastDate'] >= $today)
            ->map(function ($s) use ($ranges) {
                $state = strtoupper($s['addressState']);
                $range = $ranges[$s['id']];
                return [
                    'firstDate' => $range['firstDate'],
                    'dates' => self::dateRangeLabel($range['firstDate'], $range['lastDate']),
                    'city' => $s['addressCity'] ?? '',
                    'state' => $state,
                    'stateName' => self::US_STATES[$state] ?? $state,
                    'name' => $s['locationName'] ?? 'Smoke School',
                    'url' => route('public.session-detail', ['state' => strtolower($state), 'slug' => $s['id']]),
                ];
            })
            ->sortBy($orderBy === 'state'
                ? [['stateName', 'asc'], ['firstDate', 'asc'], ['city', 'asc']]
                : [['firstDate', 'asc'], ['stateName', 'asc'], ['city', 'asc']])
            ->values();

        return view('public.list', ['schools' => $schools, 'orderBy' => $orderBy]);
    }

    /**
     * Whether a published public session belongs on the map, list and
     * calendar: it needs a state, and isn't canceled. ONLINE-format sessions
     * are left out too -- a standing ONLINE session is how self-paced-lecture
     * enrollment is offered (the live site's is dated 2037), and it isn't a
     * school anyone travels to; the Online Self-Paced Lecture page covers it.
     */
    public static function isListedSchool(array $session): bool
    {
        return !empty($session['addressState'])
            && !($session['canceled'] ?? false)
            && ($session['format'] ?? null) !== 'ONLINE';
    }

    /** "Tue, Sep 1, 2026", "Sep 1–2, 2026", "Sep 30 – Oct 1, 2026", or across years in full. */
    public static function dateRangeLabel(string $first, string $last): string
    {
        $a = CarbonImmutable::parse($first);
        $b = CarbonImmutable::parse($last);

        return match (true) {
            $a->equalTo($b) => $a->format('D, M j, Y'),
            $a->year !== $b->year => $a->format('M j, Y') . ' – ' . $b->format('M j, Y'),
            $a->month === $b->month => $a->format('M j') . '–' . $b->format('j, Y'),
            default => $a->format('M j') . ' – ' . $b->format('M j, Y'),
        };
    }
}
