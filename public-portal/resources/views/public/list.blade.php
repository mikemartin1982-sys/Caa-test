@extends('layouts.app')

@section('title', 'Smoke School List by Location and Date - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .ls-sort { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin: 1rem 0; }
    .ls-sort a, .ls-sort span.ls-active { padding: 0.35rem 0.9rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem; text-decoration: none; }
    .ls-sort span.ls-active { background: #005da0; border-color: #005da0; color: #fff; font-weight: 600; }
    .ls-sort a { background: #f3f4f6; color: #444; }
    .ls-sort a:hover { background: #e5e7eb; }
    .ls-table { width: 100%; border-collapse: collapse; }
    .ls-table th { text-align: left; padding: 0.6rem 0.5rem; border-bottom: 2px solid #e5e7eb; font-size: 0.9rem; }
    .ls-table td { padding: 0.75rem 0.5rem; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
    .ls-table td.ls-dates { white-space: nowrap; }
    .ls-state-heading td { background: #f7f9fb; font-weight: 700; color: #005da0; padding-top: 1rem; }
    @media (max-width: 640px) {
        .ls-table thead { display: none; }
        .ls-table, .ls-table tbody, .ls-table tr, .ls-table td { display: block; width: 100%; }
        .ls-table tr { border-bottom: 1px solid #e5e7eb; padding: 0.5rem 0; }
        .ls-table td { border: none; padding: 0.15rem 0; }
    }
</style>
@endpush

@section('content')
    <h1>All Upcoming Public Smoke Schools</h1>
    <p><a href="{{ route('public.calendar') }}">View as a calendar</a> &middot; <a href="{{ route('public.map') }}">View as a map</a></p>

    <div class="ls-sort">
        <strong>Sort by:</strong>
        @foreach (['date' => 'Date', 'state' => 'Location (state)'] as $key => $label)
            @if ($orderBy === $key)
                <span class="ls-active" aria-current="true">{{ $label }}</span>
            @else
                <a href="{{ route('public.list', ['orderby' => $key]) }}">{{ $label }}</a>
            @endif
        @endforeach
    </div>

    @if ($schools->isEmpty())
        <p>No upcoming public smoke schools are currently listed. Check back soon, or ask about a
            <a href="{{ route('public.private-smoke-schools') }}">private smoke school</a> at your location.</p>
    @else
        <table class="ls-table">
            <thead>
                <tr><th>Date</th><th>City, State</th><th>Location</th></tr>
            </thead>
            <tbody>
                @php $currentState = null; @endphp
                @foreach ($schools as $school)
                    @if ($orderBy === 'state' && $school['stateName'] !== $currentState)
                        @php $currentState = $school['stateName']; @endphp
                        <tr class="ls-state-heading"><td colspan="3">{{ $currentState }}</td></tr>
                    @endif
                    <tr>
                        <td class="ls-dates">{{ $school['dates'] }}</td>
                        <td>{{ $school['city'] }}, {{ $school['state'] }}</td>
                        <td><a href="{{ $school['url'] }}">{{ $school['name'] }} &raquo;</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
