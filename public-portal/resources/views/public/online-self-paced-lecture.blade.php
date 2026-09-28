@extends('layouts.app')

@section('title', 'Online self-paced lecture course for visible emissions')

{{--
    Michael, 2026-09-27 -- Online Self-Paced Lecture sales page, text
    verbatim from the live online-self-paced-lecture.php. Links: "request a
    client account" -> account registration; "Visible Emissions Course" ->
    our /lecture sign-in. The "Course Material Samples" button appears once
    the veo-course-summary page exists (route public.veo-course-summary).
    Photo: the live home-base-image.jpg if added to images/home-page/,
    otherwise the lecture course's own home-base image.
--}}
@php
    $photo = file_exists(public_path('images/home-page/home-base-image.jpg'))
        ? '/images/home-page/home-base-image.jpg'
        : '/images/lecture/home-base-image-1.jpg';
@endphp

@push('styles')
<style>
    .ol-body { display: flex; flex-wrap: wrap; gap: 2.5rem; margin: 1.5rem 0 2rem; }
    .ol-text { flex: 1 1 360px; }
    .ol-text h3:first-child { margin-top: 0; }
    .ol-side { flex: 0 1 45%; min-width: 280px; }
    .ol-side img { width: 100%; margin-bottom: 1rem; display: block; }
    .ol-side .btn-full { margin-bottom: 1rem; }
    .ol-must { color: #b82027; }
    @media (max-width: 760px) { .ol-side { flex-basis: 100%; } }
</style>
@endpush

@section('content')
    <div class="ol-intro">
        <h1>Online Self-Paced Visible Emissions Lecture Course</h1>
        <h2>EPA Method 9 / Method 22 training, on your schedule</h2>
    </div>

    <div class="ol-body">
        <div class="ol-text">
            <h3>How the Smoke School Lecture Course Works</h3>
            <p>Compliance Assurance Associates, Inc. (CAA) self-paced lecture is a web-based visible emissions observations (VEO) course that satisfies the EPA Method 9 / Method 22 classroom/lecture requirements.
            This online opacity training is approved by TCEQ and must be completed prior to field certification for Texas students.</p>

            <h3>How to Enroll</h3>
            <p class="ol-must"><em>You must be a CAA client and enrolled to take the course.</em> Upon successful enrollment, students receive a confirmation email with the web page address and instructions on participation.</p>
            <p>Enrolling for lecture sessions is done through enrollment for a field session or by enrolling for the lecture session only.</p>
            <p>If you are not a CAA client, <a href="{{ route('account.register') }}" title="Create a client account for visible emissions">request a client account here &raquo;</a></p>

            <h3>Questions</h3>
            <p>Please call CAA at <a href="tel:+1-901-381-9960">901-381-9960</a> or <a href="mailto:info@compliance-assurance.com">email info@compliance-assurance.com</a>.</p>
        </div>

        <div class="ol-side">
            <img src="{{ $photo }}" alt="Online course in visible emissions">
            @if (Route::has('public.veo-course-summary'))
                <a href="{{ route('public.veo-course-summary') }}" title="Visible emissions online lecture course" class="btn-primary btn-full">
                    View Smoke School Lecture Course Material Samples &raquo;
                </a>
            @endif
            <h3>Enrolled and Ready to Take the Course?</h3>
            <a href="{{ route('lecture.sign-in') }}" class="btn-secondary btn-full">Visible Emissions Course &raquo;</a>
        </div>
    </div>
@endsection
