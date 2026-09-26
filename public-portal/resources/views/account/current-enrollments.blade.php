@extends('layouts.app')

@section('title', 'Current Enrollments - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .enroll-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .enroll-table th, .enroll-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
    .enroll-table th { background: #f9fafb; font-weight: 600; }
    .hint { font-size: 0.82rem; color: #888888; }
    .vr-tag { display: inline-block; padding: 0.1rem 0.5rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; background: #eef2ff; color: #4338ca; margin-left: 0.4rem; }
    .status-badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600; background: #f3f4f6; color: #444444; }
    .components-badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600; }
    .components-lecture { background: #eef2ff; color: #4338ca; }
    .components-field { background: #e6f4ea; color: #16803C; }
</style>
@endpush

@section('content')
    @include('account.partials.nav')

    <div class="reg-wrap-wide">
        <div class="reg-header">
            <h1>Current Enrollments</h1>
            <p>Every session your employees are currently enrolled in, across all locations.</p>
        </div>

        <div class="reg-card">
            {{--
                Michael, 2026-08-24 -- same information a staff member sees
                on a session's own Roster page (RosterService.buildRoster()),
                scoped to just this client's own employees (via
                Student.employerClient -- same definition Manage Employees
                already uses) across every session, not one session's full
                roster. Read-only -- no status editing or unenroll here,
                those stay staff-only tools on the admin roster.

                Michael, 2026-08-25 -- "Enrolled For" column added: found
                live from a "duplicate" report. A "Both" selection really
                is two separate enrollment records (one Lecture, one
                Field, same session) -- correct, confirmed behavior --
                but this table had no way to label which was which, so
                the two rows read as an unexplained duplicate instead of
                two legitimately different records.
            --}}
            @if (empty($enrollments))
                <p class="hint">No current enrollments on file.</p>
            @else
                <table class="enroll-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Location</th>
                            <th>Session Type</th>
                            <th>Enrolled For</th>
                            <th>Status</th>
                            <th>Lecture Complete</th>
                            <th>Field Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollments as $entry)
                            <tr>
                                <td>{{ $entry['studentName'] ?? '' }}</td>
                                <td>
                                    {{ $entry['sessionLocationName'] ?? ('Session #' . $entry['sessionId']) }}
                                    @if ($entry['vrSession'] ?? false)
                                        <span class="vr-tag">VR</span>
                                    @endif
                                </td>
                                <td>{{ $entry['schoolType'] ?? '' }}</td>
                                <td>
                                    @if (($entry['enrollmentComponents'] ?? null) === 'LECTURE_ONLY')
                                        <span class="components-badge components-lecture">Lecture</span>
                                    @elseif (($entry['enrollmentComponents'] ?? null) === 'FIELD_ONLY')
                                        <span class="components-badge components-field">Field</span>
                                    @else
                                        <span class="hint">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($entry['rosterStatus']))
                                        <span class="status-badge">{{ $entry['rosterStatus'] }}</span>
                                    @else
                                        <span class="hint">Pending</span>
                                    @endif
                                </td>
                                <td>{{ ($entry['lectureComplete'] ?? false) ? 'Yes' : 'No' }}</td>
                                <td>
                                    @if (!empty($entry['fieldDate']))
                                        {{ \Illuminate\Support\Carbon::parse($entry['fieldDate'])->format('m/d/Y') }}
                                    @else
                                        <span class="hint">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
