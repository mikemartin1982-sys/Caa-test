@extends('layouts.app')

@section('title', 'Account Login - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .reg-wrap { max-width: 420px; margin: 0 auto; }
    .reg-header { text-align: center; margin-bottom: 2rem; }
    .reg-header h1 { margin-bottom: 0.4rem; }
    .reg-header p { color: #666666; font-size: 1rem; }

    .reg-card {
        background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;
        padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .reg-errors {
        background: #fdeceb; border: 1px solid #f2b8b5; color: #b82027;
        padding: 1rem 1.25rem; border-radius: 0.375rem; margin-bottom: 1.5rem;
    }
    .reg-errors p { margin: 0; color: #b82027; }
    .reg-errors p + p { margin-top: 0.35rem; }

    .reg-field { margin-bottom: 1.1rem; }
    .reg-field label {
        display: block; font-size: 0.85rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.35rem;
    }
    .reg-field input {
        width: 100%; padding: 0.65rem 0.8rem; border: 1px solid #d1d5db; border-radius: 0.375rem;
        font-size: 0.95rem; color: #1a1a1a; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .reg-field input:focus {
        outline: none; border-color: #005da0; box-shadow: 0 0 0 3px rgba(0,93,160,0.12);
    }

    .reg-footer-link { text-align: center; margin-top: 1.5rem; color: #666666; }
</style>
@endpush

@section('content')
<div class="reg-wrap">
    <div class="reg-header">
        <h1>Account Login</h1>
        <p>Log in to continue your lecture or testing.</p>
    </div>

    <div class="reg-card">
        @if ($errors->any())
            <div class="reg-errors">
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

    <p class="reg-footer-link">Don't have an account yet? <a href="{{ route('account.register') }}">Register here</a>.</p>
</div>
@endsection
