@extends('layouts.app')

@section('title', 'VirtualOpacity® VR Smoke School — State Acceptance Map')

{{--
    Michael, 2026-09-27 -- ALT-152A State Status Map, matching the live
    vr-states.php (headings, colors, hover label, verbiage, promo bar), but
    drawn with our own self-hosted US map instead of D3 + a CDN. Departure:
    the live map sends a click to per-state pages (smoke-school/{State}/)
    that don't exist here yet, so a click shows that state's status,
    regional agencies, and acceptance letters below the map instead.
    "Get Started" goes to our VR Smoke School page (the live site's
    vr-smoke-school-intro landing page isn't built).
--}}

@push('styles')
<style>
    .vs-intro h2 { font-size: 1.1rem; font-weight: 500; color: #555; margin-top: 0.25rem; }
    .vs-label { text-align: center; font-style: italic; min-height: 3.25rem; line-height: 1.4; font-size: 15px; display: flex; flex-direction: column; justify-content: center; margin: 1rem 0 0.5rem; }
    .vs-label strong { font-style: normal; }
    .vs-map { max-width: 960px; margin: 0 auto; }
    .vs-map .us-map { width: 100%; height: auto; display: block; }
    .vs-map .us-state { fill: #cde8f5; stroke: #fff; stroke-width: 0.8; cursor: pointer; transition: fill 0.12s; outline: none; }
    .vs-map .us-state.accepted { fill: #005da0; }
    .vs-map .us-state:hover, .vs-map .us-state:focus-visible, .vs-map .us-state.selected { fill: #b82025; }
    .vs-legend { display: flex; gap: 1.5rem; justify-content: center; font-size: 0.9rem; color: #555; margin-top: 0.5rem; }
    .vs-legend span::before { content: ""; display: inline-block; width: 0.9rem; height: 0.9rem; margin-right: 0.4rem; vertical-align: -0.1rem; border-radius: 2px; }
    .vs-legend .lg-accepted::before { background: #005da0; }
    .vs-legend .lg-other::before { background: #cde8f5; }
    .vs-detail { max-width: 960px; margin: 1.5rem auto 0; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.25rem 1.5rem; }
    .vs-detail h3 { margin-top: 0; }
    .vs-status { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.85rem; font-weight: 700; }
    .vs-status-accepted { background: #e6f0f8; color: #005da0; }
    .vs-status-conditional { background: #fff4e0; color: #8a5a00; }
    .vs-status-pending { background: #f3f4f6; color: #555; }
    .vs-status-not { background: #fdecea; color: #b82027; }
    .vs-regions { columns: 2; column-gap: 2rem; padding-left: 1.2rem; margin: 0.5rem 0 1rem; font-size: 0.95rem; }
    .vs-regions li { break-inside: avoid; margin-bottom: 0.25rem; }
    .vs-copy { max-width: 48rem; margin-top: 2.5rem; }
    .vs-promo { border-radius: 0.5rem; padding: 2rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem; margin-top: 2rem; }
    .vs-promo h3 { margin: 0 0 0.25rem; }
    .vs-promo p { margin: 0; }
    .vs-promo .btn-secondary { text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; }
    @media (max-width: 640px) { .vs-regions { columns: 1; } }
</style>
@endpush

@section('content')
    @php
        $badge = fn ($status) => match (true) {
            $status === 'Accepted' => 'vs-status-accepted',
            str_starts_with($status, 'Accepted') => 'vs-status-conditional',
            $status === 'Not Accepted' => 'vs-status-not',
            default => 'vs-status-pending',
        };
    @endphp

    <div class="vs-intro">
        <h1>VirtualOpacity<sup>&reg;</sup> State Acceptance Map</h1>
        <h2>State-by-state acceptance of ALT-152A</h2>
    </div>

    <div class="vs-label" id="vs-label" aria-live="polite">
        <span>Hover over a state</span>
        <span>Click to view VR smoke school status and state training and opacity information</span>
    </div>

    <div class="vs-map">
        @include('public.partials.us-states-map')
        <div class="vs-legend">
            <span class="lg-accepted">Accepted</span>
            <span class="lg-other">Pending / not accepted</span>
        </div>
    </div>

    {{-- One panel per state; the script shows the clicked one. Without
         JavaScript they all stay visible as a plain list. --}}
    @foreach ($states as $code => $state)
        <section class="vs-detail" data-state-detail="{{ $code }}" data-status="{{ $state['status'] }}" data-accepted="{{ $state['accepted'] ? '1' : '0' }}">
            <h3>{{ $state['name'] }}</h3>
            <p>VirtualOpacity<sup>&reg;</sup> (ALT-152A) status: <span class="vs-status {{ $badge($state['status']) }}">{{ $state['status'] }}</span></p>

            @if ($state['regions'])
                <p style="margin-bottom:0;"><strong>Regional air agencies</strong></p>
                <ul class="vs-regions">
                    @foreach ($state['regions'] as $region)
                        <li>{{ $region['name'] }}: <span class="vs-status {{ $badge($region['status']) }}">{{ $region['status'] }}</span></li>
                    @endforeach
                </ul>
            @endif

            @if ($state['letters'])
                <ul>
                    @foreach ($state['letters'] as $letter)
                        <li><a href="/{{ $letter['file'] }}" target="_blank" rel="noopener">{{ $letter['label'] }} (PDF) &raquo;</a></li>
                    @endforeach
                </ul>
            @endif

            <p style="margin-bottom:0;"><a href="{{ route('public.map', ['state' => $code]) }}">Find in-person smoke schools in {{ $state['name'] }} &raquo;</a></p>
        </section>
    @endforeach

    <div class="vs-copy">
        <h2>VirtualOpacity VR smoke school recognition</h2>
        <p>
            The use of CAA&rsquo;s VirtualOpacity<sup>&reg;</sup> platform has federal EPA approval. The states shown above have formally or informally indicated their acceptance of alternative opacity testing methods, including certifications conducted using the ALT-152A protocol. Note that state agencies cannot explicitly endorse specific vendors or methodologies.
        </p>
    </div>

    <div class="caa-promo vs-promo">
        <div>
            <h3>Ready to Get Certified?</h3>
            <p>Complete your Method 9 VEO certification entirely online with VirtualOpacity<sup>&reg;</sup>.</p>
        </div>
        <a href="{{ route('public.vr-smoke-school') }}" class="btn-secondary">Get Started &raquo;</a>
    </div>

    <script>
        (function () {
            var details = {};
            document.querySelectorAll('[data-state-detail]').forEach(function (el) {
                details[el.dataset.stateDetail] = el;
                el.hidden = true;
            });

            var label = document.getElementById('vs-label');
            var defaultLabel = label.innerHTML;
            var paths = document.querySelectorAll('.vs-map .us-state');

            paths.forEach(function (path) {
                var detail = details[path.dataset.state];
                if (!detail) return;   // DC has no entry
                if (detail.dataset.accepted === '1') path.classList.add('accepted');

                path.addEventListener('mouseenter', function () {
                    label.innerHTML = '<strong>' + path.dataset.name + ': ' + detail.dataset.status + '</strong>'
                        + '<span>Click to view VR smoke school status and state training and opacity information</span>';
                });
                path.addEventListener('mouseleave', function () { label.innerHTML = defaultLabel; });
                path.addEventListener('click', function () { select(path); });
                path.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); select(path); }
                });
            });

            function select(path) {
                paths.forEach(function (p) { p.classList.remove('selected'); });
                path.classList.add('selected');
                Object.keys(details).forEach(function (code) { details[code].hidden = code !== path.dataset.state; });
                details[path.dataset.state].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Arriving from the Implementation List (?state=TX).
            var preselected = @json($selectedState ?? null);
            if (preselected) {
                var target = document.querySelector('.vs-map .us-state[data-state="' + preselected + '"]');
                if (target) select(target);
            }
        })();
    </script>
@endsection
