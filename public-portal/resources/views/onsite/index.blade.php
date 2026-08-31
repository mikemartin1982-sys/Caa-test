@extends('layouts.app')

@section('title', 'Digital Testing Sign-In - Compliance Assurance Associates, Inc.')

@section('content')
    <div style="max-width: 420px; margin: 2rem auto; text-align: center;">
        <h1>Digital Testing Sign-In</h1>
        <p>Enter your Session ID to get started. Your instructor will have this number.</p>

        @if ($errors->any())
            <p class="text-note">{{ $errors->first('session_id') }}</p>
        @endif

        <form method="POST" action="{{ route('onsite.lookup') }}">
            @csrf
            <input type="number" name="session_id" placeholder="Session ID" required
                   style="width:100%; padding:0.75rem; font-size:1.1rem; text-align:center; border:1px solid #d1d5db; border-radius:0.375rem; margin-bottom:1rem;">
            <button type="submit" class="btn-primary btn-full">Continue</button>
        </form>
    </div>
@endsection
