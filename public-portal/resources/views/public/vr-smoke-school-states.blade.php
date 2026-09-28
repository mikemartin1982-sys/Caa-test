@extends('layouts.app')

@section('title', 'Virtual Smoke School by State | ALT-152A Acceptance Status')

{{--
    Michael, 2026-09-27 -- ALT-152A State Implementation List, text verbatim
    from the live vr-smoke-school-states.php. Data comes from
    config/alt152a.php (shared with the map). Departure: each card opens the
    state map with that state selected (/vr-states?state=XX) rather than the
    live site's per-state pages, which aren't built yet. The live list shows
    Delaware's status as "Conditionally Accepted" (the map says "Accepted
    Conditionally"), so that label is kept here.
--}}
@php
    $badge = fn ($status) => match (true) {
        str_starts_with($status, 'Accepted') => ['vl-badge-accepted', $status === 'Accepted Conditionally' ? 'Conditionally Accepted' : $status],
        $status === 'Not Accepted' => ['vl-badge-not', $status],
        default => ['vl-badge-pending', $status],
    };
@endphp

@push('styles')
<style>
    .vl-legend { display: flex; flex-wrap: wrap; gap: 1rem; margin: 0.5rem 0 2rem; font-size: 0.9rem; }
    .vl-legend span { display: inline-flex; align-items: center; gap: 0.5rem; }
    .vl-legend i { display: inline-block; width: 0.75rem; height: 0.75rem; border-radius: 999px; }
    .vl-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
    .vl-card { display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; text-decoration: none; transition: border-color 0.15s, box-shadow 0.15s; }
    .vl-card:hover { border-color: #005da0; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .vl-card-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; }
    .vl-name { font-size: 1.1rem; font-weight: 700; color: #005da0; }
    .vl-more { font-size: 0.85rem; color: #9ca3af; }
    .vl-badge { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; padding: 0.25rem 0.5rem; border-radius: 999px; }
    .vl-badge i { display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 999px; }
    .vl-badge-accepted { background: #005da0; color: #fff; }
    .vl-badge-accepted i { background: #fff; }
    .vl-badge-pending { background: #f3f4f6; color: #6b7280; }
    .vl-badge-pending i { background: #9ca3af; }
    .vl-badge-not { background: #fbe9e9; color: #b82027; }
    .vl-badge-not i { background: #b82027; }
    .vl-promo { border-radius: 0.5rem; padding: 1.25rem 2rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem; margin-top: 2.5rem; }
    .vl-promo p { margin: 0; }
    .vl-promo .btn-secondary { white-space: nowrap; }
    @media (max-width: 900px) { .vl-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) { .vl-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="vl-intro">
        <h1>Virtual Smoke School Acceptance by State</h1>
        <h2>VirtualOpacity<sup>&reg;</sup> status for U.S. states</h2>
    </div>

    <p>CAA's VirtualOpacity<sup>&reg;</sup> VR smoke school holds <a href="https://www.epa.gov/system/files/documents/2024-08/eberle-alt-152-response_signed-003.pdf" title="EPA Method 9 ALT-152A acceptance" target="_blank" rel="noopener">federal EPA approval under ALT-152A.</a> Individual states independently decide whether to accept virtual smoke school for EPA Method 9 certification. Select a state below for its full acceptance status, opacity standard, air quality agency contacts, and available in-person smoke school schedule.</p>

    <p>Prefer a map view? <a href="{{ route('public.vr-states') }}" title="Interactive VR smoke school acceptance map"><strong>See the interactive state map &raquo;</strong></a></p>

    <h3>ALT-152A Acceptance by U.S. State</h3>
    <h4>Legend for Acceptance Status</h4>
    <div class="vl-legend">
        <span><i style="background:#005da0;"></i> Accepted / Conditionally Accepted</span>
        <span><i style="background:#9ca3af;"></i> Pending</span>
        <span><i style="background:#b82027;"></i> Not Accepted</span>
    </div>

    <div class="vl-grid">
        @foreach ($states as $code => $state)
            @php [$badgeClass, $badgeLabel] = $badge($state['status']); @endphp
            <a href="{{ route('public.vr-states', ['state' => $code]) }}" title="Virtual Smoke School in {{ $state['name'] }}" class="vl-card">
                <span class="vl-card-top">
                    <span class="vl-name">{{ $state['name'] }}</span>
                    <span class="vl-badge {{ $badgeClass }}"><i></i>{{ $badgeLabel }}</span>
                </span>
                <span class="vl-more">Click for more info &raquo;</span>
            </a>
        @endforeach
    </div>

    <div class="caa-promo vl-promo">
        <p>Don't see your state accepted yet? CAA offers in-person smoke school nationwide while state ALT-152A approvals continue to expand.</p>
        <a href="{{ route('public.find-a-smoke-school') }}" class="btn-secondary">Find a smoke school &raquo;</a>
    </div>
@endsection
