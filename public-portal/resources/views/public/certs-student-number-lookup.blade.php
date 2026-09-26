@extends('layouts.app')

@section('title', 'Find My Student Number - Compliance Assurance Associates, Inc.')

{{-- Michael, 2026-09-05 -- found live: also missed during the same CSS pass -- see certs-lookup.blade.php's own comment. --}}
@section('content')
    <div class="public-content" style="max-width:500px; margin:2rem auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Find My Student #</h1>
            <p class="public-page-subhead">Enter your email and last name to find your Student #.</p>
        </div>

        @if ($error ?? false)
            <p class="admin-form-errors">{{ $error }}</p>
        @endif

        @if ($studentNumber ?? false)
            <div class="public-card">
                <p style="font-weight:600;">Your Student # is: {{ $studentNumber }}</p>
                <a href="{{ route('public.certs.lookup') }}" class="btn-primary">Look Up My Certificate</a>
            </div>
        @else
            <div class="public-card-action">
                <form method="POST" action="{{ route('public.certs.find-student-number.submit') }}">
                    @csrf
                    <div class="public-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="public-field">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                    </div>
                    <button type="submit" class="btn-primary btn-full">Find My Student #</button>
                </form>
            </div>
        @endif

        <p style="margin-top:1.5rem;">
            <a href="{{ route('public.certs.lookup') }}">Back to Certificate Lookup</a>
        </p>
    </div>
@endsection
