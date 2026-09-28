@extends('layouts.app')

@section('title', 'Visible Emissions Observations Resources')

{{--
    Michael, 2026-09-27 -- Resources hub, matching the live resources-for-VEO.php
    (headings, tiles, promo bar). The live "New student smoke school intro"
    tile points at about-smoke-schools.php while its menu item points at
    digital-student.php; ours uses the Digital Student page, same as the
    menu. The Online Lecture Course, Video Library, and EPA Method 9/22
    tiles appear once their pages exist (routes public.veo-course-summary,
    public.resources-videos, public.resources-epa-method-9).
--}}
@php
    $tiles = [
        ['route' => 'public.veo-course-summary', 'label' => 'Online lecture course', 'title' => 'Online smoke school lecture course',
         'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        ['route' => 'public.digital-student', 'label' => 'New student smoke school intro', 'title' => 'New student smoke school introduction',
         'icon' => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
        ['route' => 'public.resources-videos', 'label' => 'Video library', 'title' => 'Visible emissions videos',
         'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z'],
        ['route' => 'public.resources-epa-method-9', 'label' => 'EPA Method 9 and Method 22 resources', 'title' => 'Resources for Method 9 and Method 22 by the EPA',
         'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['route' => 'public.veo-form-instructions', 'label' => 'Method 9 form instructions', 'title' => 'How to fill out a Method 9 VEO form',
         'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
    ];
    $tiles = array_filter($tiles, fn ($t) => Route::has($t['route']));
@endphp

@push('styles')
<style>
    .rs-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1.5rem 0 2.5rem; }
    .rs-tile { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; text-decoration: none; transition: border-color 0.15s, box-shadow 0.15s; }
    .rs-tile:hover { border-color: #005da0; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .rs-icon { width: 2.25rem; height: 2.25rem; flex-shrink: 0; background: #005da0; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
    .rs-label { font-size: 15px; font-weight: 600; color: #005da0; }
    .rs-promo { border-radius: 0.5rem; padding: 1.25rem 2rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem; }
    .rs-promo p { margin: 0; }
    .rs-promo .btn-primary { white-space: nowrap; }
    @media (max-width: 900px) { .rs-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) { .rs-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="rs-intro">
        <h1>EPA Method 9 and Visible Emissions Resources</h1>
        <h2>Training, reference materials, and tools for visible emissions observers</h2>
    </div>

    <div class="rs-grid">
        @foreach ($tiles as $tile)
            <a href="{{ route($tile['route']) }}" title="{{ $tile['title'] }}" class="rs-tile">
                <span class="rs-icon">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tile['icon'] }}"/></svg>
                </span>
                <span class="rs-label">{{ $tile['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="caa-promo rs-promo">
        <p>Learn what sets CAA smoke schools apart and why our Method 9 certification techniques are trusted nationwide.</p>
        <a href="{{ route('public.why-choose-compliance') }}" class="btn-primary">Why choose CAA? &raquo;</a>
    </div>
@endsection
