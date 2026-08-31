@extends('layouts.app')

@section('title', 'Find a Smoke School - Compliance Assurance Associates, Inc.')

@section('content')
    <h1>Find a Smoke School Near You</h1>
    <p><a href="{{ route('public.calendar') }}">View as a calendar</a> &middot; <a href="{{ route('public.list') }}">View as a list</a></p>

    {{-- Section 4d: the map itself renders from each session's grid coordinates
         (gridLat / gridLng), not the text address -- a real implementation
         would feed these into a map widget here. --}}
    <div id="smoke-school-map" data-sessions='@json($sessions)' style="height:420px; background:#f5f5f5; display:flex; align-items:center; justify-content:center; color:#999;">
        Map renders here from each session's grid coordinates (Section 4d)
    </div>

    <ul>
        @foreach ($sessions as $session)
            <li>
                <a href="{{ route('public.session-detail', ['state' => strtolower($session['addressState'] ?? ''), 'slug' => $session['id']]) }}">
                    {{ $session['locationName'] ?? 'Smoke School' }} &mdash; {{ $session['addressCity'] ?? '' }}, {{ $session['addressState'] ?? '' }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection
