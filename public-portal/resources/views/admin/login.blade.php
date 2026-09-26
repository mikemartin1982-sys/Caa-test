@extends('layouts.app')

@section('title', 'Admin Login - Compliance Assurance Associates, Inc.')

@section('content')
{{--
    Michael, 2026-08-31 -- Password Reset feature. Appearance cleanup
    alongside it: dropped this page's own, page-specific .reg-* <style>
    block entirely -- these are now genuinely shared (caa-brand.css),
    alongside every other page using the same look, since they were
    never actually shared to begin with (each login page defined its
    own, separate, identical set). .reg-wrap-staff below is the one
    real, deliberate accent difference (red, matching this side's own
    established look) -- everything else about the two sides' auth
    pages is now identical, shared CSS.
--}}
<div class="reg-wrap reg-wrap-staff">
    <div class="reg-header">
        <h1>Admin Login</h1>
        <p>For CAA staff only. Clients, use <a href="{{ route('account.login') }}">Account Login</a> instead.</p>
    </div>

    <div class="reg-card">
        @if ($errors->any())
            <div class="admin-form-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}">
            @csrf

            <div class="reg-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="{{ old('username') }}" autofocus>
            </div>

            <div class="reg-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary btn-full">Log In</button>
        </form>
    </div>

    <p class="reg-footer-link"><a href="{{ route('admin.password.forgot') }}">Forgot your password?</a></p>
</div>
@endsection
