@extends('layouts.app')

@section('title', 'Edit Account Info - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    /* Michael, 2026-08-24 -- a local pairing variant on top of the
       shared .reg-field (layouts/app.blade.php) -- side-by-side rows
       (first/last name, city/state/zip) are specific to this form,
       not part of the shared login-style language itself. */
    .reg-field-pair { display: flex; gap: 1rem; }
    .reg-field-pair .reg-field { flex: 1; margin-bottom: 1.1rem; }
</style>
@endpush

@section('content')
    @include('account.partials.nav')

    <div class="reg-wrap">
        <div class="reg-header">
            <h1>Edit Account Info</h1>
        </div>

        <div class="reg-card">
            @if ($errors->any())
                <div class="reg-errors">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Michael, 2026-08-24 -- "manage their own client information."
                 Submits to account.update, which deliberately takes no client
                 id from this form at all -- see AccountDashboardController's
                 own docblock for why that matters. company only shown for
                 Organization accounts, matching the same required_if logic
                 registration itself already uses. --}}
            <form method="POST" action="{{ route('account.update') }}">
                @csrf
                @method('PATCH')

                @if ($client->isOrganization())
                    <div class="reg-field">
                        <label for="company">Company Name</label>
                        <input type="text" id="company" name="company" value="{{ old('company', $clientRecord['company'] ?? '') }}">
                    </div>
                @endif

                <div class="reg-field-pair">
                    <div class="reg-field">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $clientRecord['firstName'] ?? '') }}">
                    </div>
                    <div class="reg-field">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $clientRecord['lastName'] ?? '') }}">
                    </div>
                </div>

                <div class="reg-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $clientRecord['email'] ?? '') }}">
                </div>
                <div class="reg-field">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $clientRecord['phone'] ?? '') }}">
                </div>

                <div class="reg-field">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="{{ old('address', $clientRecord['address'] ?? '') }}">
                </div>
                <div class="reg-field-pair">
                    <div class="reg-field">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="{{ old('city', $clientRecord['city'] ?? '') }}">
                    </div>
                    <div class="reg-field" style="max-width:80px;">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" maxlength="2" value="{{ old('state', $clientRecord['state'] ?? '') }}">
                    </div>
                    <div class="reg-field" style="max-width:120px;">
                        <label for="zip">ZIP</label>
                        <input type="text" id="zip" name="zip" value="{{ old('zip', $clientRecord['zip'] ?? '') }}">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">Save Changes</button>
            </form>
        </div>
    </div>
@endsection
