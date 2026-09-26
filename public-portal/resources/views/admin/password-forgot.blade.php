@extends('layouts.app')

@section('title', 'Forgot Password - Compliance Assurance Associates, Inc.')

@section('content')
<div class="reg-wrap reg-wrap-staff">
    <div class="reg-header">
        <h1>Forgot Password</h1>
        <p>Enter your username and we'll email you a reset link, if your account has an email on file.</p>
    </div>

    <div class="reg-card">
        @if (session('status'))
            <p class="hint" style="margin-bottom:1.5rem;">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="admin-form-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.password.forgot.submit') }}">
            @csrf

            <div class="reg-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="{{ old('username') }}" autofocus>
            </div>

            <button type="submit" class="btn-primary btn-full">Send Reset Link</button>
        </form>
    </div>

    <p class="reg-footer-link"><a href="{{ route('admin.login') }}">Back to Admin Login</a></p>
</div>
@endsection
