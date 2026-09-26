@extends('layouts.app')
 
@section('title', 'Account Login - Compliance Assurance Associates, Inc.')
 
@section('content')
{{--
    Michael, 2026-08-31 -- Password Reset feature. Same appearance
    cleanup as admin.login -- dropped this page's own, page-specific
    .reg-* <style> block, now genuinely shared (caa-brand.css).
--}}
<div class="reg-wrap">
    <div class="reg-header">
        <h1>Account Login</h1>
        <p>Log in to continue your lecture or testing.</p>
    </div>
 
    <div class="reg-card">
        @if ($errors->any())
            <div class="admin-form-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
 
        <form method="POST" action="{{ route('account.login.attempt') }}">
            @csrf
 
            <div class="reg-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}" autofocus>
            </div>
 
            <div class="reg-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
 
            <button type="submit" class="btn-primary btn-full">Log In</button>
        </form>
    </div>
 
    <p class="reg-footer-link"><a href="{{ route('account.password.forgot') }}">Forgot your password?</a></p>
    <p class="reg-footer-link">Don't have an account yet? <a href="{{ route('account.register') }}">Register here</a>.</p>
</div>
@endsection