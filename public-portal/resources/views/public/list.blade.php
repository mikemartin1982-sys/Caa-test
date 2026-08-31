@extends('layouts.app')

@section('title', 'All Smoke Schools - Compliance Assurance Associates, Inc.')

@section('content')
    <h1>All Upcoming Public Smoke Schools</h1>
    <p><a href="{{ route('public.calendar') }}">View as a calendar</a> &middot; <a href="{{ route('public.map') }}">View as a map</a></p>

    <ul style="list-style:none; padding:0;">
        @foreach ($sessions as $session)
            <li style="padding:1rem 0; border-bottom:1px solid #f0f0f0;">
                <strong>
                    <a href="{{ route('public.session-detail', ['state' => strtolower($session['addressState'] ?? ''), 'slug' => $session['id']]) }}">
                        {{ $session['locationName'] ?? 'Smoke School' }}
                    </a>
                </strong>
                <br>
                {{ $session['addressCity'] ?? '' }}, {{ $session['addressState'] ?? '' }} &middot; {{ $session['region'] ?? '' }}
            </li>
        @endforeach
    </ul>
@endsection
