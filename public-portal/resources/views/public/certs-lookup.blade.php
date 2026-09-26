@extends('layouts.app')

@section('title', 'Certificate Lookup - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-04 -- Public Certificate Lookup, matching the
    real, existing DIBs feature (certs.php). Confirmed with Michael:
    genuinely public, no login at all.

    Michael, 2026-09-05 -- found live: this page was missed during the
    CSS pass that fixed the four Smoke School Discovery pages -- still
    used admin-content/sd-row/sd-label/sd-input/sd-save-bar, none of
    which exist in caa-brand.css at all (confirmed then, same as those
    four pages). Not a missing/broken CSS file at all -- caa-brand.css
    itself was loading fine (200) the whole time -- this page just
    never got the same real-class fix applied to it. Now uses the same
    .public-* components as the rest of the public site.
--}}
@section('content')
    <div class="public-content" style="max-width:500px; margin:2rem auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Certificate Lookup</h1>
            <p class="public-page-subhead">Enter your Student # and last name to find and download your certificate(s).</p>
        </div>

        @if ($error ?? false)
            <p class="admin-form-errors">{{ $error }}</p>
        @endif

        <div class="public-card-action">
            <form method="POST" action="{{ route('public.certs.lookup.submit') }}">
                @csrf
                <div class="public-field">
                    <label for="student_number">Student #</label>
                    <input type="text" id="student_number" name="student_number" value="{{ old('student_number') }}" required>
                </div>
                <div class="public-field">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                </div>
                <button type="submit" class="btn-primary btn-full">Look Up My Certificate</button>
            </form>
        </div>

        <p style="margin-top:1.5rem;">
            Don't know your Student #? <a href="{{ route('public.certs.find-student-number') }}">Find it here</a>.
        </p>
    </div>
@endsection
