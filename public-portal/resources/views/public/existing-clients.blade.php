@extends('layouts.app')

@section('title', 'Existing smoke school client services')

{{--
    Michael, 2026-09-27 -- Existing Client Services hub, matching the live
    existing-clients.php. Like the live page, the first tile is "Log in",
    or "My account" for a client who is already signed in. The Customer
    Satisfaction Survey tile appears once that page exists (route
    public.survey).
--}}
@php
    $tiles = [
        auth('client')->check()
            ? ['url' => route('account.dashboard'), 'label' => 'My account', 'title' => 'Go to your account',
               'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z']
            : ['url' => route('account.login'), 'label' => 'Log in', 'title' => 'Log in to your account',
               'icon' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1'],
        ['url' => route('public.certs.lookup'), 'label' => 'Find/print certification', 'title' => 'Find and print your certification',
         'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
        ['url' => route('public.certs.find-student-number'), 'label' => 'Retrieve student record number', 'title' => 'Retrieve your student record number',
         'icon' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14'],
        ['url' => route('public.digital-student'), 'label' => 'Intro for new smoke school students', 'title' => 'Intro for new smoke school students',
         'icon' => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
        ['url' => route('lecture.sign-in'), 'label' => 'Lecture course', 'title' => 'Access the online lecture course',
         'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
    ];
    if (Route::has('public.survey')) {
        $tiles[] = ['url' => route('public.survey'), 'label' => 'Customer satisfaction survey', 'title' => 'Smoke school customer satisfaction survey',
                    'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'];
    }
@endphp

@push('styles')
<style>
    .ec-intro h2 { font-size: 1.1rem; font-weight: 500; color: #555; margin-top: 0.25rem; }
    .ec-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1.5rem 0 2.5rem; }
    .ec-tile { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; text-decoration: none; transition: border-color 0.15s, box-shadow 0.15s; }
    .ec-tile:hover { border-color: #005da0; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .ec-icon { width: 2.25rem; height: 2.25rem; flex-shrink: 0; background: #005da0; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
    .ec-label { font-size: 15px; font-weight: 600; color: #005da0; }
    @media (max-width: 900px) { .ec-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) { .ec-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="ec-intro">
        <h1>Existing Client Services</h1>
        <h2>Account management and resources for CAA smoke school clients</h2>
    </div>

    <div class="ec-grid">
        @foreach ($tiles as $tile)
            <a href="{{ $tile['url'] }}" title="{{ $tile['title'] }}" class="ec-tile">
                <span class="ec-icon">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tile['icon'] }}"/></svg>
                </span>
                <span class="ec-label">{{ $tile['label'] }}</span>
            </a>
        @endforeach
    </div>
@endsection
