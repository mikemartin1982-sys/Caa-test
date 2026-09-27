@extends('layouts.admin')

@section('title', 'Change Password - Admin')

@section('content')
<div class="admin-form-wrap">
    <h1>Change Password</h1>
    <p class="hint">Signed in as <strong>{{ auth('staff')->user()->username }}</strong>.</p>

    @if ($errors->any())
        <div class="admin-form-errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.password.update') }}">
        @csrf
        @method('PUT')

        <div class="admin-form-field">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>

        <div class="admin-form-row">
            <div class="admin-form-field">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                <p class="hint" style="margin-top:0.3rem;">At least 8 characters.</p>
            </div>
            <div class="admin-form-field">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn-primary">Change Password</button>
        <a href="{{ route('admin.dashboard') }}" style="margin-left:1rem;">Cancel</a>
    </form>
</div>
@endsection
