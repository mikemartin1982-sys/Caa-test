@extends('layouts.app')

@section('title', 'Reset Password - Compliance Assurance Associates, Inc.')

@section('content')
<div class="reg-wrap reg-wrap-staff">
    <div class="reg-header">
        <h1>Reset Password</h1>
        <p>Choose a new password for your account.</p>
    </div>

    <div class="reg-card">
        @if ($errors->any())
            <div class="admin-form-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.password.reset.submit') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="reg-field">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="8" autofocus>
            </div>

            <div class="reg-field">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
            </div>

            <button type="submit" class="btn-primary btn-full">Reset Password</button>
        </form>
    </div>

    <p class="reg-footer-link"><a href="{{ route('admin.login') }}">Back to Admin Login</a></p>
</div>
@endsection
