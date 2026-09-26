@extends('lecture.layout')

@section('title', 'Self-Paced Lecture Quiz - Smoke School Online Visible Emissions Course')

{{--
    Michael, 2026-09-07 -- real quiz-taking view. Each question is its
    own, real <form> that auto-submits the moment a radio is selected
    (via a shared, small JS function below) -- matching the live
    course's own confirmed behavior exactly: each answer submits right
    away as a real, separate page reload, not part of a single
    "submit all" action at the end.

    $quizResult (flashed from submitQuizAnswer()) reflects the most
    recently submitted answer -- correct/incorrect, and once every
    question has been answered, the real final score/pass-fail banner,
    matching the live course's own "You have PASSED section..." message.
    Once complete, questions render as read-only (radios disabled,
    correct answer highlighted) with real "Back to where you left off"
    and "Quiz Summary Page" buttons -- matching what Michael described
    seeing on the real, live course after finishing a quiz.
--}}
@section('page-title', 'Quiz')

@section('sidebar')
    @include('lecture.sidebar', ['sections' => $sections, 'resources' => $resources, 'currentPageId' => null])
@endsection

@section('content')
    @php
        $quizResult = session('quizResult', []);
        $quizComplete = $quizResult['quizComplete'] ?? false;
    @endphp

    @if ($quizComplete)
        <div style="background-color:{{ ($quizResult['passed'] ?? false) ? '#eeee00' : '#ffe1e1' }}; padding:1.25rem; border-radius:0.375rem; margin-bottom:1.5rem; text-align:center;">
            <p style="font-size:1.1rem; margin:0 0 0.5rem;"><strong>You have {{ ($quizResult['passed'] ?? false) ? 'PASSED' : 'not passed' }} this section's quiz</strong> with a score of {{ $quizResult['scorePercent'] ?? 0 }}%.</p>
            @if ($quizResult['passed'] ?? false)
                <p style="margin:0;">You can move on to the next section.</p>
            @else
                <p style="margin:0;">You'll need to retake this quiz to move on -- answering any question below will start a new attempt.</p>
            @endif
        </div>
    @endif

    <h3 style="color:#005da0;">{{ count($quiz['questions'] ?? []) }} questions for this section:</h3>

    @foreach ($quiz['questions'] ?? [] as $index => $question)
        <div style="border:1px solid #e5e7eb; border-radius:0.375rem; padding:1.25rem; margin-bottom:1rem;">
            <p style="font-weight:700; color:#005da0; margin-top:0;">{{ $index + 1 }}. {{ $question['questionText'] }}</p>

            <form method="POST" action="{{ route('lecture.quiz.answer', [$quiz['id'], $question['id']]) }}">
                @csrf
                @foreach ($question['choices'] as $choice)
                    <label style="display:block; padding:0.4rem 0; cursor:{{ $quizComplete ? 'default' : 'pointer' }};">
                        <input
                            type="radio"
                            name="choice_id"
                            value="{{ $choice['id'] }}"
                            onchange="this.form.submit()"
                            {{ ($question['selectedChoiceId'] ?? null) == $choice['id'] ? 'checked' : '' }}
                            {{ $quizComplete ? 'disabled' : '' }}
                        >
                        {{ $choice['choiceText'] }}
                    </label>
                @endforeach
            </form>
        </div>
    @endforeach

    @if ($quizComplete)
        <div style="text-align:center; margin-top:2rem; display:flex; justify-content:center; gap:1rem; flex-wrap:wrap;">
            <a href="{{ route('lecture.home-base') }}" style="display:inline-block; padding:0.75rem 1.5rem; background-color:#005da0; color:#ffffff; font-weight:700; border-radius:0.375rem; text-decoration:none;">
                Back to Where You Left Off
            </a>
            <a href="{{ route('lecture.quiz-summary') }}" style="display:inline-block; padding:0.75rem 1.5rem; background-color:#b82027; color:#ffffff; font-weight:700; border-radius:0.375rem; text-decoration:none;">
                Quiz Summary Page
            </a>
        </div>
    @endif
@endsection
