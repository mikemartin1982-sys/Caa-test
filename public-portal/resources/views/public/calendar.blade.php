@extends('layouts.app')

@section('title', 'Smoke School Calendar - Compliance Assurance Associates, Inc.')

@section('content')
    <h1>Public Smoke School Calendar</h1>
    <p><a href="{{ route('public.map') }}">View as a map</a> &middot; <a href="{{ route('public.list') }}">View as a list</a></p>

    @if (empty($sessions))
        <p>No upcoming public smoke schools are currently listed. Check back soon.</p>
    @else
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align:left; border-bottom: 2px solid #e5e5e5;">
                    <th style="padding:0.5rem;">Location</th>
                    <th style="padding:0.5rem;">City, State</th>
                    <th style="padding:0.5rem;">Region</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions as $session)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding:0.5rem;">
                            <a href="{{ route('public.session-detail', ['state' => strtolower($session['addressState'] ?? ''), 'slug' => $session['id']]) }}">
                                {{ $session['locationName'] ?? 'Smoke School' }}
                            </a>
                        </td>
                        <td style="padding:0.5rem;">{{ $session['addressCity'] ?? '' }}, {{ $session['addressState'] ?? '' }}</td>
                        <td style="padding:0.5rem;">{{ $session['region'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
