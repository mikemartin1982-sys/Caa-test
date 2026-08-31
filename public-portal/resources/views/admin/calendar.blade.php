@extends('layouts.admin')

@section('title', 'Staff Calendar - Compliance Assurance Associates, Inc.')

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Staff Calendar</h1>
        <p><a href="{{ route('admin.dashboard') }}">&laquo; Back to Dashboard</a></p>
    </div>

    {{--
        This is a sorted, filterable LIST -- not an interactive month-grid
        widget. Building a true visual calendar grid (like DIBs' calendar
        view) is a bigger, separate piece of work involving either a JS
        calendar library or a hand-built grid; this is a functional first
        version covering the same underlying data (all school types, real
        dates from SessionDay), not a placeholder for the visual format.
    --}}

    <form method="GET" action="{{ route('admin.calendar') }}" style="margin-bottom:1.5rem;">
        <label for="schoolType">School Type</label>
        <select id="schoolType" name="schoolType" onchange="this.form.submit()">
            <option value="" @selected(!$selectedSchoolType)>All</option>
            <option value="PUBLIC" @selected($selectedSchoolType === 'PUBLIC')>Public</option>
            <option value="PRIVATE" @selected($selectedSchoolType === 'PRIVATE')>Private</option>
            <option value="SEMI_PRIVATE" @selected($selectedSchoolType === 'SEMI_PRIVATE')>Semi-Private</option>
            <option value="VR" @selected($selectedSchoolType === 'VR')>VR</option>
        </select>
    </form>

    @if (empty($sessions))
        <p>No sessions found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>ID</th>
                    <th>School Type</th>
                    <th>Location</th>
                    <th>Region</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions as $session)
                    <tr>
                        <td>{{ $session['date'] ?? 'Unscheduled' }}</td>
                        <td>{{ $session['id'] ?? '' }}</td>
                        <td>{{ $session['schoolType'] ?? '' }}</td>
                        <td>{{ $session['locationName'] ?? '' }}</td>
                        <td>{{ $session['region'] ?? '' }}</td>
                        <td>{{ $session['status'] ?? '' }}</td>
                        <td>{{ ($session['published'] ?? false) ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.sessions.show', ['session' => $session['id']]) }}">Details</a>
                            &middot;
                            <a href="{{ route('admin.sessions.roster', ['session' => $session['id']]) }}">Roster</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
