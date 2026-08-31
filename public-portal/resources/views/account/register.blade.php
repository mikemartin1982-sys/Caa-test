@extends('layouts.app')

@section('title', 'Create Account - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .reg-wrap { max-width: 560px; margin: 0 auto; }
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

    .reg-section-label {
        font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 0.8rem;
        color: #005da0; text-transform: uppercase; letter-spacing: 0.05em;
        margin-bottom: 0.6rem;
    }

    .reg-option-group { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; }
    .reg-option-card {
        flex: 1; display: block; border: 2px solid #e5e7eb; border-radius: 0.5rem;
        padding: 0.9rem 1rem; cursor: pointer; transition: border-color 0.15s, background-color 0.15s;
    }
    .reg-option-card input[type=radio] { margin-right: 0.5rem; accent-color: #005da0; }
    .reg-option-card .reg-option-title { font-weight: 700; color: #1a1a1a; }
    .reg-option-card .reg-option-sub { display: block; font-size: 0.82rem; color: #666666; margin-top: 0.2rem; }
    .reg-option-card:hover { border-color: #a9c3d8; }
    .reg-option-card:has(input:checked) { border-color: #005da0; background-color: #f0f6fb; }

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
    .reg-field-row { display: flex; gap: 1rem; }
    .reg-field-row .reg-field { flex: 1; }

    .reg-footer-link { text-align: center; margin-top: 1.5rem; color: #666666; }
</style>
@endpush

@section('content')
<div class="reg-wrap">
    <div class="reg-header">
        <h1>Create Your Account</h1>
        <p>Register once, then complete your lecture and testing at your own pace.</p>
    </div>

    <div class="reg-card">
        @if ($errors->any())
            <div class="reg-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('account.register.submit') }}">
            @csrf

            <div class="reg-section-label">Account Type</div>
            <div class="reg-option-group">
                <label class="reg-option-card">
                    <input type="radio" name="client_type" value="INDIVIDUAL" onchange="accountTypeChanged()"
                           {{ old('client_type', 'INDIVIDUAL') === 'INDIVIDUAL' ? 'checked' : '' }}>
                    <span class="reg-option-title">Individual</span>
                    <span class="reg-option-sub">I'm registering myself</span>
                </label>
                <label class="reg-option-card">
                    <input type="radio" name="client_type" value="ORGANIZATION" onchange="accountTypeChanged()"
                           {{ old('client_type') === 'ORGANIZATION' ? 'checked' : '' }}>
                    <span class="reg-option-title">Organization</span>
                    <span class="reg-option-sub">I'll manage employees under one account</span>
                </label>
            </div>

            <div class="reg-field" id="company-field" style="display:none;">
                <label for="company">Company Name</label>
                <input type="text" id="company" name="company" value="{{ old('company') }}">
            </div>

            <div class="reg-field-row">
                <div class="reg-field">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required value="{{ old('first_name') }}">
                </div>
                <div class="reg-field">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required value="{{ old('last_name') }}">
                </div>
            </div>

            <div class="reg-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}">
            </div>

            <div class="reg-field">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" required value="{{ old('phone') }}">
            </div>

            <div class="reg-field-row">
                <div class="reg-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="reg-field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
                </div>
            </div>

            <div class="reg-section-label" style="margin-top:0.5rem;">Testing Path</div>
            <div class="reg-option-group" style="margin-bottom:1.75rem;">
                <label class="reg-option-card">
                    <input type="radio" name="testing_path" value="VR"
                           {{ old('testing_path', 'VR') === 'VR' ? 'checked' : '' }}>
                    <span class="reg-option-title">VR</span>
                    <span class="reg-option-sub">Complete testing on your own schedule</span>
                </label>
                <label class="reg-option-card">
                    <input type="radio" name="testing_path" value="TRADITIONAL"
                           {{ old('testing_path') === 'TRADITIONAL' ? 'checked' : '' }}>
                    <span class="reg-option-title">Traditional</span>
                    <span class="reg-option-sub">Attend a scheduled, in-person smoke school</span>
                </label>
            </div>

            <button type="submit" class="btn-primary btn-full">Create Account</button>
        </form>
    </div>

    <p class="reg-footer-link">Already have an account? <a href="{{ route('account.login') }}">Log in here</a>.</p>
</div>

<script>
    function accountTypeChanged() {
        const isOrg = document.querySelector('input[name="client_type"]:checked').value === 'ORGANIZATION';
        const companyField = document.getElementById('company-field');
        companyField.style.display = isOrg ? 'block' : 'none';
        document.getElementById('company').required = isOrg;
    }
    // Run on load, in case validation failed and the form re-rendered
    // with ORGANIZATION already selected via old('client_type').
    accountTypeChanged();
</script>
@endsection