@extends('layouts.app')

@section('title', ($session['locationName'] ?? 'Smoke School') . ' - Compliance Assurance Associates, Inc.')

@section('content')
    <h1>{{ $session['locationName'] ?? 'Smoke School' }}</h1>

    {{-- Location block: Map link built from grid coordinates, not the text
         address, so it points exactly where the pin was placed (Section 4d) --}}
    <section>
        <h2>Location</h2>
        <p>
            {{ $session['addressStreet'] ?? '' }}<br>
            {{ $session['addressCity'] ?? '' }}, {{ $session['addressState'] ?? '' }} {{ $session['addressZip'] ?? '' }}
        </p>
        @if (!empty($session['gridLat']) && !empty($session['gridLng']))
            <p>
                <a href="https://maps.google.com/?q={{ $session['gridLat'] }},{{ $session['gridLng'] }}" target="_blank" rel="noopener">
                    Map &raquo;
                </a>
            </p>
        @endif

        {{-- Self-service directions: seeded with the session's coordinates as
             the destination; the visitor supplies their own starting point. --}}
        <form action="https://maps.google.com/maps" method="get" target="_blank" rel="noopener">
            <input type="hidden" name="daddr" value="{{ $session['gridLat'] ?? '' }},{{ $session['gridLng'] ?? '' }}">
            <label for="saddr">Directions from:</label>
            <input type="text" id="saddr" name="saddr" placeholder="Your starting address or zip">
            <button type="submit">Get Directions</button>
        </form>
    </section>

    {{-- External registration override (Section 4d) --}}
    @if (!empty($session['externalRegistrationName'] ?? null))
        <section style="background:#fff8e1; padding:1rem; border-radius:4px;">
            <p>
                Registration for this school is handled by <strong>{{ $session['externalRegistrationName'] }}</strong>.
                @if (!empty($session['externalRegistrationPhone'] ?? null))
                    Call {{ $session['externalRegistrationPhone'] }} to register.
                @endif
            </p>
        </section>
    @else
        {{-- Two-part pricing (Section 3a): Field Certification and Self-Paced
             Lecture are separate line items, both management-set. --}}
        <section>
            <h2>Pricing</h2>
            <ul>
                <li>Field Certification Smoke School: ${{ number_format($session['fieldCertificationPrice'] ?? 0, 2) }}</li>
                <li>Self-Paced Lecture Course: ${{ number_format($session['selfPacedLecturePrice'] ?? 0, 2) }}</li>
            </ul>
            <a href="#" class="enroll-button">Enroll Now</a>
        </section>
    @endif

    @if (!empty($session['publicSessionNotes'] ?? null))
        <section>
            <h2>Session Notes</h2>
            <p>{{ $session['publicSessionNotes'] }}</p>
        </section>
    @endif
@endsection
