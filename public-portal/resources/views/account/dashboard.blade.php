@extends('layouts.app')

@section('title', 'My Account - Compliance Assurance Associates, Inc.')

@section('content')
    @include('account.partials.nav')

    <div class="reg-wrap">
        <div class="reg-header">
            <h1>Welcome, {{ $client->name }}</h1>
        </div>

        <div class="reg-card">
            <div class="reg-field">
                <label>Account Type</label>
                <div>{{ $client->isOrganization() ? 'Organization' : 'Individual' }}</div>
            </div>

            <p style="color:#666666; font-size:0.9rem; margin-top:1.5rem;">
                Downloading certificates is coming soon.
            </p>

            <form method="POST" action="{{ route('account.logout') }}" style="margin-top:1rem;">
                @csrf
                <button type="submit" class="btn-secondary">Log Out</button>
            </form>
        </div>
    </div>
@endsection
