@extends('layouts.app')

@section('title', 'Find EPA Method 9 Smoke School Training in the US')

{{--
    Michael, 2026-09-27 -- Find a Smoke School landing page, matching the
    live find-a-smoke-school.php. The photo (find-a-class.jpg) shows once
    it's added to public/images/home-page/. "Create a New Client Account"
    goes to account registration, same as elsewhere on this site.
--}}
@php
    $photo = file_exists(public_path('images/home-page/find-a-class.jpg')) ? '/images/home-page/find-a-class.jpg' : null;
@endphp

@push('styles')
<style>
    .fs-intro h2 { font-size: 1.1rem; font-weight: 500; color: #555; margin-top: 0.25rem; }
    .fs-body { display: flex; flex-wrap: wrap; gap: 2.5rem; align-items: flex-start; margin: 1.5rem 0 2.5rem; }
    .fs-photo { flex: 0 0 380px; max-width: 100%; }
    .fs-photo img { width: 100%; border-radius: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.12); display: block; }
    .fs-links { flex: 1 1 320px; }
    .fs-links h3:first-child { margin-top: 0; }
    .fs-links ul { margin: 0 0 2rem; }
    .fs-links li { margin-bottom: 0.4rem; }
    .fs-promo { border-radius: 0.5rem; padding: 2rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem; }
    .fs-promo h3 { margin: 0 0 0.25rem; }
    .fs-promo p { margin: 0; }
    .fs-promo .btn-secondary { text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; }
</style>
@endpush

@section('content')
    <div class="fs-intro">
        <h1>Find Smoke School Training</h1>
        <h2>Multiple ways to find opacity training</h2>
    </div>

    <div class="fs-body">
        @if ($photo)
            <div class="fs-photo">
                <img src="{{ $photo }}" alt="Find a smoke school training session">
            </div>
        @endif

        <div class="fs-links">
            <h3>Find a Smoke School</h3>
            <ul>
                <li><a href="{{ route('public.calendar') }}" title="Method 9 training calendar">View our smoke school training calendar &raquo;</a></li>
                <li><a href="{{ route('public.list') }}" title="List of VEO classes">View a text list of smoke schools sortable by date or class location &raquo;</a></li>
                <li><a href="{{ route('public.map') }}" title="Selectable map of opacity training">Search for smoke schools by geographical area via an interactive map &raquo;</a></li>
            </ul>

            <h3>Prospective Clients</h3>
            <p style="margin-bottom:0.5rem;"><a href="{{ route('account.register') }}" title="New clients for opacity training">Create a New Client Account &raquo;</a></p>
            <p>Request a client account setup and enroll online.</p>
        </div>
    </div>

    <div class="caa-promo fs-promo">
        <div>
            <h3>Interested in a Private Smoke School?</h3>
            <p>We will meet your organization's schedule and requirements.</p>
        </div>
        <a href="{{ route('public.private-smoke-schools') }}" title="Learn about on-site opacity training" class="btn-secondary">Learn More &raquo;</a>
    </div>
@endsection
