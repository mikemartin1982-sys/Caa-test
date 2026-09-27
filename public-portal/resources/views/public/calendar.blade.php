@extends('layouts.app')

@section('title', 'Smoke School Calendar - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .cal-intro h2 { font-size: 1.1rem; font-weight: 500; color: #555; margin-top: 0.25rem; }
    .cal-info { display: flex; flex-wrap: wrap; gap: 1.5rem; margin: 1.5rem 0 2rem; }
    .cal-info > div { flex: 1 1 320px; }
    .cal-info ul { margin: 0.5rem 0 1rem; padding-left: 1.2rem; }
    .cal-note { font-size: 0.9rem; color: #b82027; }
    .cal-promo { background: #eef4fa; border-left: 4px solid #005da0; border-radius: 4px; padding: 1.25rem 1.5rem; }
    .cal-nav { display: flex; justify-content: space-between; align-items: center; max-width: 380px; margin: 0 auto 1rem; }
    .cal-nav a, .cal-nav span { padding: 0.4rem 1rem; font-size: 0.9rem; border: 1px solid #d1d5db; border-radius: 4px; background: #f3f4f6; color: #444; text-decoration: none; }
    .cal-nav a:hover { background: #e5e7eb; }
    .cal-nav span { opacity: 0.4; cursor: not-allowed; }
    .cal-nav-bottom { margin: 1.5rem auto 0; }
    .cal-month { text-align: center; font-size: 1.6rem; font-weight: 700; margin-bottom: 0.75rem; }
    .cal-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .cal-table th, .cal-table td { border: 2px solid #1a1a1a; padding: 4px; vertical-align: top; }
    .cal-table th { text-align: center; font-weight: 700; }
    .cal-table th.cal-weekend { width: 9%; }
    .cal-table td { height: 5.5rem; font-weight: 700; }
    .cal-day-num { display: inline-block; padding: 0 0.2rem; }
    .cal-holiday { background: darkorange; color: #000; cursor: help; }
    .cal-school { display: block; margin-top: 0.6rem; font-size: 0.9rem; line-height: 1.2; }
    .cal-empty { text-align: center; margin: 1rem 0 0; }
    .cal-agenda { display: none; }
    @media (max-width: 700px) {
        .cal-table { display: none; }
        .cal-agenda { display: block; list-style: none; padding: 0; margin: 0; }
        .cal-agenda li { padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; }
        .cal-agenda .cal-school { margin-top: 0.3rem; }
    }
</style>
@endpush

@section('content')
    <div class="cal-intro">
        <h1>Public Smoke School Calendar</h1>
        <h2>Method 9 visual emissions certification schedule</h2>
        <p><a href="{{ route('public.map') }}">View as a map</a> &middot; <a href="{{ route('public.list') }}">View as a list</a></p>
    </div>

    <div class="cal-info">
        <div>
            <p>To enroll in a smoke school:</p>
            <ul>
                <li><strong>Existing clients:</strong> <a href="{{ route('account.login') }}">Login to your CAA account &raquo;</a></li>
                <li><strong>Prospective clients:</strong> <a href="{{ route('account.register') }}">Create a client account &raquo;</a></li>
            </ul>
            <p class="cal-note"><strong>Note:</strong> Public school dates are subject to change. If a school date changes, you will be notified via email if you have enrolled in the class.</p>
        </div>
        <div>
            <div class="cal-promo">
                CAA also offers smoke schools throughout the U.S. in a private on-site training format. If you are
                interested in on-site training, please visit our
                <a href="{{ route('public.private-smoke-schools') }}">private school information page</a>.
            </div>
        </div>
    </div>

    @php
        $monthNav = function (string $extraClass = '') use ($prevMonth, $nextMonth) {
            $prev = $prevMonth
                ? '<a href="' . e(route('public.calendar', ['month' => $prevMonth])) . '">&lt; Previous Month</a>'
                : '<span aria-disabled="true">&lt; Previous Month</span>';
            $next = '<a href="' . e(route('public.calendar', ['month' => $nextMonth])) . '">Next Month &gt;</a>';
            return '<div class="cal-nav ' . $extraClass . '">' . $prev . $next . '</div>';
        };
    @endphp

    {!! $monthNav() !!}

    <div class="cal-month">{{ $monthLabel }}</div>

    <table class="cal-table">
        <thead>
            <tr>
                <th class="cal-weekend">Sunday</th>
                <th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th>
                <th class="cal-weekend">Saturday</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($weeks as $week)
                <tr>
                    @foreach ($week as $cell)
                        <td>
                            @if ($cell)
                                <span class="cal-day-num @if ($cell['holiday']) cal-holiday @endif"
                                      @if ($cell['holiday']) title="Holiday: {{ $cell['holiday'] }}" @endif>{{ $cell['day'] }}</span>
                                @foreach ($cell['schools'] as $school)
                                    <a class="cal-school" href="{{ $school['url'] }}">{{ $school['label'] }} &raquo;</a>
                                @endforeach
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Phone layout: the same month as a list of days that have schools or holidays. --}}
    <ul class="cal-agenda">
        @foreach ($weeks as $week)
            @foreach (array_filter($week) as $cell)
                @if ($cell['schools'] || $cell['holiday'])
                    <li>
                        <strong>{{ \Carbon\Carbon::parse($cell['date'])->format('l, F j') }}</strong>
                        @if ($cell['holiday']) <span class="cal-holiday">&nbsp;{{ $cell['holiday'] }}&nbsp;</span> @endif
                        @foreach ($cell['schools'] as $school)
                            <a class="cal-school" href="{{ $school['url'] }}">{{ $school['label'] }} &raquo;</a>
                        @endforeach
                    </li>
                @endif
            @endforeach
        @endforeach
    </ul>

    @unless ($hasSchools)
        <p class="cal-empty">No public smoke schools are scheduled for {{ $monthLabel }}. Try the next month, or <a href="{{ route('public.list') }}">view all upcoming schools</a>.</p>
    @endunless

    {!! $monthNav('cal-nav-bottom') !!}
@endsection
