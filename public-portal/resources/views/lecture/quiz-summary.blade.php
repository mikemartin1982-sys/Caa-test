@extends('lecture.layout')

@section('title', 'Self-Paced Lecture Quiz Summary - Smoke School Online Visible Emissions Course')

{{--
    Michael, 2026-09-07 -- the real Quiz Summary Page (lecture-quiz.php
    with no real section id in the live course) -- a top-level table
    showing every section at once: name, question count, how many the
    student has answered (from their real, most recent completed
    attempt), and that attempt's own score -- matching the live course's
    own summary table exactly. Locked sections show as plain, disabled
    text, matching the live course's own greyed-out treatment.

    Real, honest simplification: the live course's own "Back to where
    you left off" button returns to the exact page a student was on
    before entering the quiz. This version returns to Home Base instead
    -- tracking that specific prior-page state wasn't part of this
    build.
--}}
@section('page-title', 'Quiz Summary Page')

@section('sidebar')
    @include('lecture.sidebar', ['sections' => $sections, 'resources' => $resources, 'currentPageId' => null])
@endsection

@section('content')
    <h3 style="color:#005da0;">Quiz Sections</h3>

    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background-color:#b82027; color:#ffffff;">
                <th style="padding:0.75rem; text-align:left;">Section</th>
                <th style="padding:0.75rem; text-align:left;">Questions Answered</th>
                <th style="padding:0.75rem; text-align:left;">Score</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary as $section)
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:0.75rem;">
                        @if ($section['unlocked'])
                            <strong style="color:#005da0;">{{ $section['name'] }}</strong>
                        @else
                            <span style="color:#999999;" title="Disabled because you haven't completed the previous section.">{{ $section['name'] }}</span>
                        @endif
                    </td>
                    <td style="padding:0.75rem;">
                        {{ $section['questionsAnswered'] }} of {{ $section['totalQuestions'] }} questions answered
                    </td>
                    <td style="padding:0.75rem;">
                        @if (! is_null($section['answersCorrectPercent']))
                            {{ $section['answersCorrectPercent'] }}%
                        @else
                            &nbsp;
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="text-align:center; margin-top:2rem;">
        <a href="{{ route('lecture.home-base') }}" style="display:inline-block; padding:0.75rem 1.5rem; background-color:#005da0; color:#ffffff; font-weight:700; border-radius:0.375rem; text-decoration:none;">
            Back to Where You Left Off
        </a>
    </div>
@endsection
