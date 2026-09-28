@extends('layouts.app')

@section('title', 'VirtualOpacity smoke school testimonials')

{{--
    Michael, 2026-09-27 -- VirtualOpacity testimonials, text verbatim from
    the live vr-testimonials.php. "Sign up now" goes to our VR Smoke School
    page (the live vr-client-info.php equivalent). The VirtualOpacity logo
    on each card shows once 12903501-VirtualOpacity-Logo.png is added to
    public/images/home-page/.
--}}
@php
    $logo = file_exists(public_path('images/home-page/12903501-VirtualOpacity-Logo.png'))
        ? '/images/home-page/12903501-VirtualOpacity-Logo.png' : null;

    $testimonials = [
        ['quote' => 'I thoroughly enjoyed certifying with VR. It improved my learning experience and saved me a tremendous amount of time.',
         'name' => 'Jackson C.', 'lines' => ['Staff Environmental Engineer', 'Major E&C consulting group', 'San Diego, California']],
        ['quote' => "I think it is super. A great improvement over the field smoke school. I hope I don't ever have to go back to the field school.",
         'name' => 'Bill S.', 'lines' => ['Environmental Consultant', 'Nearly 50 years of smoke school experience', 'Texas']],
        ['quote' => 'The VR testing is way cool. Passed on the first go. Thank you!',
         'name' => 'Carl B.', 'lines' => ['Stack Testing Consultant', 'Missouri']],
        ['quote' => 'Perfect. You guys have been a breeze to work with. Thank you.',
         'name' => 'Mike R.', 'lines' => ['Environment, Health and Safety (EHS) Manager', 'Minnesota']],
        ['quote' => 'I wanted to let you know that my experience with VirtualOpacity for the VR smoke school certification went very smoothly. I appreciate the innovative approach and the seamless process it offered.',
         'name' => 'M.K.', 'lines' => ['Utility Employee', 'Virginia']],
        ['quote' => 'Staff are enjoying this, great job with the program.',
         'name' => 'T.S.', 'lines' => ['Chief of Engineering', 'Cleveland, OH']],
        ['quote' => 'Awesome setup for testing.',
         'name' => 'M.B.', 'lines' => ['Student', 'Bowling Green, KY']],
        ['quote' => 'Excellent service and great training.',
         'name' => 'D.R.A.', 'lines' => ['Student', 'Dallas, TX']],
    ];
@endphp

@push('styles')
<style>
    .vt-intro h2 { font-size: 1.1rem; font-weight: 500; color: #555; margin-top: 0.25rem; }
    .vt-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 1.5rem; }
    .vt-card { background: #fff; border-radius: 0.75rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 1.5rem; transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; }
    .vt-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.14); }
    .vt-card img { width: 4rem; margin-bottom: 1rem; }
    .vt-card blockquote { font-style: italic; margin: 0 0 1rem; flex: 1; }
    .vt-who { border-top: 1px solid #e5e7eb; padding-top: 1rem; }
    .vt-who p { margin: 0; }
    .vt-who .vt-name { font-weight: 700; margin-bottom: 0.25rem; }
    .vt-who .vt-lines { font-size: 0.9rem; color: #666; }
    @media (max-width: 900px) { .vt-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .vt-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="vt-intro">
        <h1>VirtualOpacity<sup>&reg;</sup> Testimonials</h1>
        <h2>What Method 9 observers are saying about CAA's virtual smoke school</h2>
    </div>

    <p>Join the professionals who use virtual smoke school. <a href="{{ route('public.vr-smoke-school') }}" title="Request access to virtual smoke school">Sign up now &raquo;</a></p>

    <div class="vt-grid">
        @foreach ($testimonials as $t)
            <figure class="vt-card" style="margin:0;">
                @if ($logo)
                    <img src="{{ $logo }}" alt="VirtualOpacity virtual smoke school">
                @endif
                <blockquote>&ldquo;{{ $t['quote'] }}&rdquo;</blockquote>
                <figcaption class="vt-who">
                    <p class="vt-name">{{ $t['name'] }}</p>
                    <p class="vt-lines">{!! implode('<br>', array_map('e', $t['lines'])) !!}</p>
                </figcaption>
            </figure>
        @endforeach
    </div>
@endsection
