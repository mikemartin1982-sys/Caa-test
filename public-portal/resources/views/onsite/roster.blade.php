@extends('layouts.app')

@section('title', 'Digital Testing Sign-In - Compliance Assurance Associates, Inc.')

@section('content')
    <div style="max-width: 420px; margin: 2rem auto; text-align: center;">
        <h1>Find Your Name</h1>
        <p>{{ $session['locationName'] ?? 'Session #' . $sessionId }}</p>

        @if ($errors->any())
            <p class="text-note">{{ $errors->first('enrollment_id') }}</p>
        @endif

        @if (!$signInOpen)
            <p class="text-note">Sign-in isn't open for this session right now. See your instructor.</p>
        @elseif (empty($roster))
            <p class="text-note">No students are enrolled in this session yet. See your instructor.</p>
        @else
            <form method="POST" action="{{ route('onsite.check-in', ['sessionId' => $session['id']]) }}">
                @csrf
                <select name="enrollment_id" required
                        style="width:100%; padding:0.75rem; font-size:1.1rem; border:1px solid #d1d5db; border-radius:0.375rem; margin-bottom:1rem;">
                    <option value="">Select your name...</option>
                    @foreach ($roster as $entry)
                        <option value="{{ $entry['enrollmentId'] ?? '' }}">{{ $entry['studentName'] ?? 'Unknown' }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary btn-full">That's Me</button>
            </form>
        @endif

        <p style="margin-top:1.5rem;"><a href="{{ route('onsite.index') }}">&laquo; Wrong session?</a></p>
    </div>
@endsection
