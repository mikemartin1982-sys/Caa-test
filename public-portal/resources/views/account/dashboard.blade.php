@extends('layouts.app')

@section('title', 'My Account - Compliance Assurance Associates, Inc.')

@section('content')
    <h1>Welcome, {{ $client->name }}</h1>

    <div class="sd-section">
        <div style="padding: 1rem 1.25rem;">
            <div class="sd-row">
                <div class="sd-label">Account Type</div>
                <div class="sd-input">{{ $client->isOrganization() ? 'Organization' : 'Individual' }}</div>
            </div>
            <div class="sd-row">
                <div class="sd-label">Email</div>
                <div class="sd-input">{{ $client->email }}</div>
            </div>
        </div>
    </div>

    <p class="sd-hint" style="margin-top:1rem;">
        Your full account portal &mdash; lecture progress, testing status, and (for Organizations) employee
        management &mdash; is being built next. For now, this confirms your account was created successfully.
    </p>

    <form method="POST" action="{{ route('account.logout') }}" style="margin-top:1rem;">
        @csrf
        <button type="submit" class="btn-secondary">Log Out</button>
    </form>
@endsection
