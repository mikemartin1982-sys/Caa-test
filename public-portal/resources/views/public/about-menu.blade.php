@extends('layouts.app')

@section('title', 'Information about the leading smoke school - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "About" hub page, starting the About
    section fork -- same real pattern as professional-services.php at
    the start of the VEO Services fork. All six of its real sub-pages
    (About, Why Choose Us, FAQs, Team, Our Clients, Jobs) genuinely
    don't exist yet, so all six cards render as plain, non-clickable
    .public-nav-card-disabled tiles for now -- each one converts to a
    real, clickable .public-nav-card as its own page gets built.

    Real, honest departure: the bottom promo bar's only CTA
    (why-choose-compliance.php) doesn't exist, so that section is
    omitted entirely, matching the same approach used on the VEO
    Expertise/Readings pages.
--}}
@section('content')
    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>About Compliance Assurance Smoke School</h1>
            <p class="public-page-subhead">Learn about CAA's experience, team, and approach to visible emissions training</p>
        </div>

        <div class="public-nav-grid">

            <a href="{{ route('public.about') }}" class="public-nav-card">
                <div class="public-nav-card-icon">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
                </div>
                <span class="public-nav-card-label">About Compliance Assurance</span>
            </a>

            <a href="{{ route('public.why-choose-compliance') }}" class="public-nav-card">
                <div class="public-nav-card-icon">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="public-nav-card-label">Why Choose Compliance Assurance?</span>
            </a>

            <a href="{{ route('public.faqs') }}" class="public-nav-card">
                <div class="public-nav-card-icon">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M12 21a9 9 0 100-18 9 9 0 000 18z"/></svg>
                </div>
                <span class="public-nav-card-label">FAQs</span>
            </a>

            <a href="{{ route('public.about-team') }}" class="public-nav-card">
                <div class="public-nav-card-icon">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <span class="public-nav-card-label">Meet Our Team</span>
            </a>

            <a href="{{ route('public.our-customers') }}" class="public-nav-card">
                <div class="public-nav-card-icon">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span class="public-nav-card-label">Our Clients</span>
            </a>

            <div class="public-nav-card-disabled" title="Not built yet">
                <div class="public-nav-card-icon">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="public-nav-card-label">Jobs</span>
            </div>

        </div>
    </div>
@endsection
