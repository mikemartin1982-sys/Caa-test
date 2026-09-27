@extends('layouts.app')

@section('title', 'Find a Smoke School - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .map-intro { display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-start; justify-content: space-between; }
    .map-intro h2 { font-size: 1.1rem; font-weight: 500; color: #555; margin-top: 0.25rem; }
    .map-enroll { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; padding: 1rem 1.25rem; }
    .map-enroll p { margin: 0 0 0.5rem; }
    .map-enroll ul { margin: 0; padding-left: 1.2rem; }
    .map-wrap { max-width: 960px; margin: 1rem auto 0; }
    .us-map { width: 100%; height: auto; display: block; }
    .us-state { fill: #d1d5db; stroke: #fff; stroke-width: 1; cursor: pointer; transition: fill 0.15s; outline: none; }
    .us-state.has-schools { fill: #005da0; }
    .us-state:hover, .us-state:focus-visible { fill: #b82027; }
    .us-state.selected { fill: #b82027; }
    .map-legend { display: flex; gap: 1.5rem; justify-content: center; font-size: 0.9rem; color: #555; margin-top: 0.5rem; }
    .map-legend span::before { content: ""; display: inline-block; width: 0.9rem; height: 0.9rem; margin-right: 0.4rem; vertical-align: -0.1rem; border-radius: 2px; }
    .map-legend .legend-schools::before { background: #005da0; }
    .map-legend .legend-none::before { background: #d1d5db; }
    .state-results { max-width: 960px; margin: 1.5rem auto 0; }
    .state-results ul { list-style: none; padding: 0; }
    .state-results li { padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0; }
</style>
@endpush

@section('content')
    <div class="map-intro">
        <div>
            <h1>Find a Smoke School Near You</h1>
            <h2>Click on a state to see a list of smoke schools for that state</h2>
            <p><a href="{{ route('public.calendar') }}">View as a calendar</a> &middot; <a href="{{ route('public.list') }}">View as a list</a></p>
        </div>
        <div class="map-enroll">
            <p><strong>To enroll:</strong></p>
            <ul>
                <li><strong>Existing clients:</strong> <a href="{{ route('account.login') }}">Login to your CAA account &raquo;</a></li>
                <li><strong>Prospective clients:</strong> <a href="{{ route('account.register') }}">Create a new client account &raquo;</a></li>
            </ul>
        </div>
    </div>

    <div class="map-wrap">
        @include('public.partials.us-states-map')
        <div class="map-legend">
            <span class="legend-schools">Public schools scheduled</span>
            <span class="legend-none">None currently scheduled</span>
        </div>
    </div>

    <div class="state-results" id="state-results" aria-live="polite">
        <p id="state-prompt">Select a state on the map to see its smoke schools.</p>

        <div id="state-none" hidden>
            <h2 id="state-none-title"></h2>
            <p>
                No public smoke schools are currently scheduled in this state. CAA can bring a
                <a href="{{ route('public.private-smoke-schools') }}">private smoke school</a> to your location.
            </p>
        </div>

        {{-- Rendered for every state that has schools; the script shows only the
             selected one. Without JavaScript they all stay visible as a plain list. --}}
        @foreach ($schoolsByState as $state => $schools)
            <section class="state-list" data-state-list="{{ $state }}">
                <h2>{{ $state }}</h2>
                <ul>
                    @foreach ($schools as $school)
                        <li>
                            <strong><a href="{{ $school['url'] }}">{{ $school['name'] }}</a></strong><br>
                            {{ $school['city'] }}, {{ $state }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <script>
        (function () {
            var lists = document.querySelectorAll('[data-state-list]');
            var withSchools = {};
            lists.forEach(function (el) { withSchools[el.dataset.stateList] = el; el.hidden = true; });

            var states = document.querySelectorAll('.us-state');
            states.forEach(function (path) {
                if (withSchools[path.dataset.state]) path.classList.add('has-schools');
                path.addEventListener('click', function () { select(path); });
                path.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); select(path); }
                });
            });

            function select(path) {
                states.forEach(function (p) { p.classList.remove('selected'); });
                path.classList.add('selected');
                lists.forEach(function (el) { el.hidden = true; });
                document.getElementById('state-prompt').hidden = true;

                var list = withSchools[path.dataset.state];
                var none = document.getElementById('state-none');
                if (list) {
                    list.querySelector('h2').textContent = path.dataset.name;
                    list.hidden = false;
                    none.hidden = true;
                } else {
                    document.getElementById('state-none-title').textContent = path.dataset.name;
                    none.hidden = false;
                }
                document.getElementById('state-results').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        })();
    </script>
@endsection
