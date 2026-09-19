@extends('layouts.admin')

@section('title', 'Digital-Testing Admin - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    /* Consolidated status strip (Michael, 2026-08-18) -- matches a real
       DIBs reference screenshot: stage/run/point/advance and live
       signed-in/recorded/matched stats all visible at a glance. */
    .dt-status-strip {
        background-color: #f4f8fb; border: 2px solid #005da0; border-radius: 0.5rem;
        padding: 0.75rem 1rem; margin-bottom: 1rem;
    }
    .dt-status-strip-row { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
    .dt-status-strip-row + .dt-status-strip-row { margin-top: 0.5rem; }
    .dt-status-item { display: flex; align-items: baseline; gap: 0.4rem; }
    .dt-status-label { font-size: 0.75rem; color: #888888; text-transform: uppercase; letter-spacing: 0.03em; }
    .dt-status-value { font-size: 1.3rem; font-weight: 700; color: #005da0; }
    .dt-status-value small { font-size: 0.75rem; font-weight: 600; color: #888888; }
    .dt-advance-btn {
        margin-left: auto; background-color: #005da0; color: #ffffff; border: none;
        border-radius: 0.375rem; padding: 0.5rem 1rem; font-size: 0.9rem; font-weight: 600; cursor: pointer;
    }
    .dt-advance-btn:hover { background-color: #004a80; }
    .dt-status-stats { font-size: 0.9rem; color: #444444; }
    .dt-status-pct { color: #888888; font-size: 0.82rem; }
</style>
@endpush

@section('content')
    <h1>Digital-Testing Admin</h1>

    <form method="GET" action="{{ route('admin.digital-testing.index') }}" style="margin-bottom: 1.5rem;">
        <label for="session_id" style="font-weight:600; margin-right:0.5rem;">Choose a Session</label>
        <select id="session_id" name="session_id" onchange="this.form.submit()"
                style="padding:0.4rem 0.6rem; border:1px solid #d1d5db; border-radius:0.25rem; min-width:280px;">
            <option value="">Select session_id ({{ count($sessions) }} items)</option>
            @foreach ($sessions as $s)
                <option value="{{ $s['id'] }}" @selected(($selectedSession['id'] ?? null) == $s['id'])>
                    {{ $s['id'] }}: {{ $s['locationName'] ?? 'Unnamed' }} ({{ $s['schoolType'] ?? '' }})
                </option>
            @endforeach
        </select>
    </form>

    @if (!$selectedSession)
        <p style="color:#888;">Choose a session above to manage its Digital Testing stage.</p>
    @else
        <div class="sd-section">
            <div class="caa-box-header-blue">Session #{{ $selectedSession['id'] }}: {{ $selectedSession['locationName'] ?? '' }}</div>
            <div style="padding: 1rem 1.25rem;">
                @php $currentStage = $selectedSession['onsiteStage'] ?? 'PRACTICE'; @endphp
                <div class="sd-row">
                    <div class="sd-label">Current Stage</div>
                    <div class="sd-input"><strong>{{ $currentStage }}</strong></div>
                </div>

                <form method="POST" action="{{ route('admin.digital-testing.update', ['session' => $selectedSession['id']]) }}">
                    @csrf
                    @method('PATCH')
                    <div class="sd-row">
                        <div class="sd-label">Set Stage</div>
                        <div class="sd-input">
                            @foreach ([
                                'SIGN_IN' => 'Sign-In',
                                'PRACTICE' => 'Practice',
                                'TESTING' => 'Testing',
                                'CLOSED' => 'Closed',
                            ] as $value => $label)
                                <label class="sd-checkbox-row">
                                    <input type="radio" name="stage" value="{{ $value }}" @checked($currentStage === $value) onchange="this.form.submit()">
                                    {{ $label }}
                                </label>
                            @endforeach
                            <div class="sd-hint">
                                Sign-In: students can look up this session and sign in at /onsite.
                                Closed here only means Digital Testing is done for the day &mdash; it does NOT lock the session for billing (that's Session Details &rarr; Closed Out, a separate step).
                                Saves automatically when you pick a stage.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <p><a href="{{ route('admin.sessions.show', ['session' => $selectedSession['id']]) }}">View full Session Details &raquo;</a></p>

        @if ($errors->has('live_test'))
            <p class="text-note" style="font-weight:600;">{{ $errors->first('live_test') }}</p>
        @endif

        {{--
            Live Test operator panel (2026-08-17) -- Start/Record True
            Value/Advance were only ever driven via raw API calls until
            now; this is the actual UI. colorCode thresholds match
            Method9ScoringService's own documented scale exactly (not a
            new one): GREEN under 15% deviation, ORANGE 15-19%, RED 20%+
            (the real failedReading threshold) -- a RED row gets a
            "Revisit Point" button (Michael, 2026-08-17), which reopens
            that point using its ALREADY-recorded true value, no
            re-entry needed.
        --}}
        <div class="sd-section">
            <div class="caa-box-header-blue">Live Test</div>
            <div style="padding: 1rem 1.25rem;">
                @if (!($liveTestStatus['active'] ?? false))
                    <p style="color:#888;">No live test currently active for this session.</p>
                    <form method="POST" action="{{ route('admin.digital-testing.live-test.start', ['session' => $selectedSession['id']]) }}">
                        @csrf
                        @if (!empty($splitRunCandidates))
                            <div class="sd-hint" style="margin-bottom:0.5rem;">Split-Run candidates (already have a failed-White/passed-Black prior run) &mdash; check any who should get a 25-point retake instead of a fresh 50-point run:</div>
                            @foreach ($splitRunCandidates as $candidate)
                                <label class="sd-checkbox-row">
                                    <input type="checkbox" name="split_run_enrollment_ids[]" value="{{ $candidate['enrollmentId'] }}">
                                    {{ $candidate['studentName'] }}
                                </label><br>
                            @endforeach
                        @endif
                        <button type="submit" class="btn-primary" style="margin-top:0.75rem;">Start Test</button>
                    </form>
                @else
                    @php
                        $revisitPoint = $liveTestStatus['revisitPointNumber'] ?? null;
                        $displayPoint = $revisitPoint ?? ($liveTestStatus['pointNumber'] ?? null);
                        $displayColor = $revisitPoint ? ($revisitPoint <= 25 ? 'WHITE' : 'BLACK') : ($liveTestStatus['color'] ?? '');
                        $liveStudents = $liveTestStatus['students'] ?? [];
                        $signedInCount = count($liveStudents);
                        $recordedCount = collect($liveStudents)->where('submitted', true)->count();
                        $recordedPercent = $signedInCount > 0 ? round(($recordedCount / $signedInCount) * 100) : 0;
                        // "Matched" = recorded AND not a failed reading (colorCode
                        // != RED) -- the exact same threshold Method9ScoringService
                        // already uses for a failed reading, not a new one.
                        $matchedCount = collect($liveStudents)->where('submitted', true)->where('colorCode', '!=', 'RED')->count();
                        $matchedPercent = $signedInCount > 0 ? round(($matchedCount / $signedInCount) * 100) : 0;
                    @endphp

                    {{--
                        Consolidated status strip (Michael, 2026-08-18,
                        matching a real DIBs reference screenshot) -- Run
                        number was never surfaced anywhere in the UI
                        before this. Advance is pulled out of the
                        collapsed "Advanced" details below and made a
                        first-class, prominent control here, since it's
                        explicitly what was asked for -- still calls the
                        exact same fallback endpoint; auto-advance still
                        handles the common case silently in the background.
                    --}}
                    <div class="dt-status-strip">
                        <div class="dt-status-strip-row">
                            <div class="dt-status-item"><span class="dt-status-label">Run</span><span class="dt-status-value">{{ $liveTestStatus['runNumber'] ?? '?' }}</span></div>
                            <div class="dt-status-item"><span class="dt-status-label">Point</span><span class="dt-status-value">{{ $displayPoint ?? '?' }} <small>({{ $displayColor }})</small></span></div>
                            @if (!$revisitPoint)
                                <form method="POST" action="{{ route('admin.digital-testing.live-test.advance', ['session' => $selectedSession['id']]) }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="dt-advance-btn" title="Force advance -- auto-advance normally handles this on its own">Advance &raquo;</button>
                                </form>
                            @endif
                        </div>
                        <div class="dt-status-strip-row dt-status-stats">
                            <div>Signed-in: <strong>{{ $signedInCount }}</strong></div>
                            <div>Recorded: <strong>{{ $recordedCount }}</strong> <span class="dt-status-pct">{{ $recordedPercent }}%</span></div>
                            <div>Rcrd &amp; Match: <strong>{{ $matchedCount }}</strong> <span class="dt-status-pct">{{ $matchedPercent }}%</span></div>
                        </div>
                    </div>

                    @if ($revisitPoint)
                        <div class="sd-row">
                            <div class="sd-label">Revisiting</div>
                            <div class="sd-input">
                                <strong>Point {{ $revisitPoint }} ({{ $displayColor }})</strong> &mdash; real progress is Point {{ $liveTestStatus['pointNumber'] ?? '?' }}, untouched underneath.
                                <form method="POST" action="{{ route('admin.digital-testing.live-test.end-revisit', ['session' => $selectedSession['id']]) }}" style="display:inline; margin-left:0.75rem;">
                                    @csrf
                                    <button type="submit" class="btn-secondary" style="padding:0.3rem 0.75rem; font-size:0.85rem;">End Revisit</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="sd-row">
                            <div class="sd-label">Current Point</div>
                            <div class="sd-input"><strong>Point {{ $displayPoint }} ({{ $displayColor }})</strong></div>
                        </div>
                        <div class="sd-row">
                            <div class="sd-label">True Value</div>
                            <div class="sd-input">
                                @if ($liveTestStatus['trueValueSet'] ?? false)
                                    <span>&#9989; Recorded</span>
                                @else
                                    <form method="POST" action="{{ route('admin.digital-testing.live-test.record-true-value', ['session' => $selectedSession['id']]) }}" style="display:inline;">
                                        @csrf
                                        <input type="number" name="true_opacity" min="0" max="100" step="5" placeholder="0-100" required style="width:100px;">
                                        <button type="submit" class="btn-primary" style="padding:0.3rem 0.75rem; font-size:0.85rem;">Record</button>
                                    </form>
                                @endif
                                <div class="sd-hint">Points auto-advance once everyone's submitted &mdash; no need to click anything else per point.</div>
                            </div>
                        </div>

                        @if (($liveTestStatus['readyToGradeCount'] ?? 0) > 0)
                            <form method="POST" action="{{ route('admin.digital-testing.live-test.grade-test', ['session' => $selectedSession['id']]) }}" style="margin:0.75rem 0;">
                                @csrf
                                <button type="submit" class="btn-primary">Grade Test ({{ $liveTestStatus['readyToGradeCount'] }} ready)</button>
                            </form>
                        @endif

                        <div class="sd-hint">Points auto-advance once everyone's submitted; the Advance button above is a manual fallback -- only needed if a student's device can't submit (e.g. it died) and everyone else is stuck waiting on them.</div>
                    @endif

                    <table style="width:100%; border-collapse:collapse; font-size:0.85rem; margin-top:0.75rem;">
                        <thead>
                            <tr style="text-align:left;">
                                <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Student</th>
                                <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Submitted</th>
                                <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Guess</th>
                                <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Deviation</th>
                                <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($liveTestStatus['students'] ?? [] as $s)
                                @php
                                    $rowColor = match ($s['colorCode'] ?? null) {
                                        'RED' => '#f8d7da', 'ORANGE' => '#ffe8cc', 'GREEN' => '#d4edda', default => 'transparent',
                                    };
                                @endphp
                                <tr style="border-bottom:1px solid #f3f4f6; background-color:{{ $rowColor }};">
                                    <td style="padding:0.4rem 0.6rem;">{{ $s['studentName'] }}</td>
                                    <td style="padding:0.4rem 0.6rem;">{{ $s['submitted'] ? 'Yes' : 'No' }}</td>
                                    <td style="padding:0.4rem 0.6rem;">{{ $s['estimatedOpacity'] ?? '' }}</td>
                                    <td style="padding:0.4rem 0.6rem;">{{ $s['deviation'] ?? '' }}</td>
                                    <td style="padding:0.4rem 0.6rem;">
                                        @if (($s['colorCode'] ?? null) === 'RED' && !$revisitPoint)
                                            <form method="POST" action="{{ route('admin.digital-testing.live-test.revisit', ['session' => $selectedSession['id']]) }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="point_number" value="{{ $displayPoint }}">
                                                <button type="submit" class="btn-secondary" style="padding:0.2rem 0.6rem; font-size:0.78rem;">Revisit Point</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" style="padding:0.6rem;">No participants for this point.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if (!$revisitPoint && !empty($liveTestStatus['failedCompletedPoints']))
                        <div class="sd-label" style="margin-top:1rem;">Completed Points Needing Review</div>
                        <table style="width:100%; border-collapse:collapse; font-size:0.85rem; margin-top:0.4rem;">
                            <thead>
                                <tr style="text-align:left;">
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Point</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Student</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Guess</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Deviation</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($liveTestStatus['failedCompletedPoints'] as $failedPoint)
                                    <tr style="border-bottom:1px solid #f3f4f6; background-color:#f8d7da;">
                                        <td style="padding:0.4rem 0.6rem;">{{ $failedPoint['pointNumber'] }}</td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $failedPoint['studentName'] }}</td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $failedPoint['estimatedOpacity'] }}</td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $failedPoint['deviation'] }}</td>
                                        <td style="padding:0.4rem 0.6rem;">
                                            <form method="POST" action="{{ route('admin.digital-testing.live-test.revisit', ['session' => $selectedSession['id']]) }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="point_number" value="{{ $failedPoint['pointNumber'] }}">
                                                <button type="submit" class="btn-secondary" style="padding:0.2rem 0.6rem; font-size:0.78rem;">Revisit Point</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endif
            </div>
        </div>

        @if ($liveTestStatus['active'] ?? false)
            <script>
            (function () {
                // Auto-refresh (Michael, 2026-08-17, found live during
                // testing) -- points now auto-advance from student
                // submissions in the background, but this page never
                // self-updated to reflect that. Same reload-on-change
                // pattern the student testing page already uses.
                const sessionId = {{ $selectedSession['id'] }};
                const knownState = {
                    pointNumber: {{ $liveTestStatus['pointNumber'] ?? 'null' }},
                    trueValueSet: {{ ($liveTestStatus['trueValueSet'] ?? false) ? 'true' : 'false' }},
                    revisitPointNumber: {{ $liveTestStatus['revisitPointNumber'] ?? 'null' }},
                    readyToGradeCount: {{ $liveTestStatus['readyToGradeCount'] ?? 0 }},
                    submittedCount: {{ collect($liveTestStatus['students'] ?? [])->where('submitted', true)->count() }},
                };

                function poll() {
                    fetch('{{ route('admin.digital-testing.live-test.status-json', ['session' => $selectedSession['id']]) }}')
                        .then(r => r.json())
                        .then(data => {
                            const submittedCount = (data.students || []).filter(s => s.submitted).length;
                            const changed = data.pointNumber !== knownState.pointNumber
                                || data.trueValueSet !== knownState.trueValueSet
                                || data.revisitPointNumber !== knownState.revisitPointNumber
                                || data.readyToGradeCount !== knownState.readyToGradeCount
                                || submittedCount !== knownState.submittedCount;
                            if (changed) {
                                window.location.reload();
                            }
                        })
                        .catch(() => {}); // a missed poll just tries again next interval
                }

                setInterval(poll, 5000);
            })();
            </script>
        @endif

        {{--
            Roster table (2026-08-16) -- matches the real DIBs Digital-
            Testing Admin layout Michael provided, scoped down per his
            own call: the Stacktest-driven columns (live TOP/CUR point
            tracking, the 50-point marked-readings grid, camera
            integration) are NOT built here, since that all depends on
            Stacktest access we don't have yet. This is the roster-level
            view only -- who's signed in, their run/certification status,
            and who administered it.
        --}}
        <div class="sd-section">
            <div class="caa-box-header-blue">Roster</div>
            <div style="padding: 1rem 1.25rem;">
                @php
                    $fmgrInitials = $selectedSession['fieldManager']['initials'] ?? '';
                    $operInitials = $selectedSession['operator']['initials'] ?? '';
                @endphp

                <label style="font-weight:600; display:block; margin-bottom:0.75rem;">
                    <input type="checkbox" id="cbShowOnlySignedIn" onchange="onsiteDtFilterRows()"> Show Only Signed-In
                </label>

                @if (empty($roster))
                    <p style="color:#888;">No students enrolled in this session.</p>
                @else
                    <form method="POST" action="{{ route('admin.digital-testing.roster.update', ['session' => $selectedSession['id']]) }}">
                        @csrf
                        @method('PATCH')
                        <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                            <thead>
                                <tr style="text-align:left;">
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">ID</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Student</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Company Name</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Field Stat</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Field Date</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;">Run</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;" title="Session's Field Manager (Session Details &rarr; Team)">FMgr</th>
                                    <th style="padding:0.4rem 0.6rem; background-color:#005da0; color:#ffffff;" title="Session's Operator (Session Details &rarr; Team)">Oper</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roster as $r)
                                    @php
                                        $statusLabels = ['ARR' => "Arr'd", 'CERTIFIED' => 'Comp', 'DNC' => 'DNC', 'DNA' => 'DNA'];
                                        $status = $r['rosterStatus'] ?? null;
                                        $signedIn = $status !== null;
                                    @endphp
                                    <tr data-signed-in="{{ $signedIn ? '1' : '0' }}" style="border-bottom:1px solid #f3f4f6;">
                                        <td style="padding:0.4rem 0.6rem;">{{ $r['enrollmentId'] ?? '' }}</td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $r['studentName'] ?? '' }}</td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $r['companyName'] ?? '' }}</td>
                                        <td style="padding:0.4rem 0.6rem;">
                                            <select name="roster_status[{{ $r['enrollmentId'] }}]" style="padding:0.15rem 0.3rem; font-size:0.85rem;">
                                                <option value="" @selected(!$status)>none</option>
                                                @foreach ($statusLabels as $val => $label)
                                                    <option value="{{ $val }}" @selected($status === $val)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $r['fieldDate'] ?? '' }}</td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $r['certificationRunNumber'] ?? '' }}</td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $fmgrInitials }}</td>
                                        <td style="padding:0.4rem 0.6rem;">{{ $operInitials }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="sd-save-bar" style="padding-left:0;">
                            <button type="submit" class="btn-primary">Submit Changes</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        @if (!$fmgrInitials && !$operInitials)
            <p class="text-note">No Field Manager/Operator set for this session yet &mdash; set them in Session Details &rarr; Team, then come back here.</p>
        @endif

        <script>
        function onsiteDtFilterRows() {
            const onlySignedIn = document.getElementById('cbShowOnlySignedIn').checked;
            document.querySelectorAll('tr[data-signed-in]').forEach(row => {
                row.style.display = (!onlySignedIn || row.dataset.signedIn === '1') ? '' : 'none';
            });
        }
        </script>
    @endif
@endsection
