@extends('layouts.app')

@section('title', 'Forgot Password - Compliance Assurance Associates, Inc.')

@section('content')
<div class="reg-wrap">
    <div class="reg-header">
        <h1>Forgot Password</h1>
        <p>Enter your email and we'll send you a reset link, if an account exists.</p>
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

        <form method="POST" action="{{ route('account.password.forgot.submit') }}">
            @csrf

            <div class="reg-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}" autofocus>
            </div>

            <button type="submit" class="btn-primary btn-full">Send Reset Link</button>
        </form>
    </div>

    <p class="reg-footer-link"><a href="{{ route('account.login') }}">Back to Account Login</a></p>
</div>
@endsection
