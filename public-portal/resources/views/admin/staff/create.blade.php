@extends('layouts.admin')

@section('title', 'Create Staff Account - Admin')

@section('content')
{{--
    Michael, 2026-08-31 -- appearance cleanup: .staff-form-wrap/
    .staff-field/.staff-row/.staff-errors weren't actually
    staff-specific -- generic form-building blocks now shared
    (.admin-form-*) alongside every other admin form. .sd-hint (applied
    inline, never actually defined as a real class anywhere) folds into
    the real, shared .hint instead.
--}}
<div class="admin-form-wrap">
    <h1>Create Staff Account</h1>

    @if ($errors->any())
        <div class="admin-form-errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.staff.store') }}">
        @csrf

        <div class="admin-form-row">
            <div class="admin-form-field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required value="{{ old('name') }}">
            </div>
            <div class="admin-form-field">
                <label for="initials">Initials</label>
                <input type="text" id="initials" name="initials" value="{{ old('initials') }}" placeholder="e.g. DWM">
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="{{ old('username') }}">
            </div>
            <div class="admin-form-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
        </div>

        <div class="admin-form-field">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="STAFF" {{ old('role', 'STAFF') === 'STAFF' ? 'selected' : '' }}>Staff</option>
                <option value="COMPLIANCE_ADMINISTRATOR" {{ old('role') === 'COMPLIANCE_ADMINISTRATOR' ? 'selected' : '' }}>Compliance Administrator</option>
            </select>
            <p class="hint" style="margin-top:0.3rem;">Compliance Administrator can manage staff accounts, session confirmation emails, and bid generation. Choose deliberately.</p>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}">
            </div>
            <div class="admin-form-field">
                <label for="job_title">Job Title</label>
                <input type="text" id="job_title" name="job_title" value="{{ old('job_title') }}">
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-field">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
            </div>
            <div class="admin-form-field">
                <label for="mobile_phone">Mobile Phone</label>
                <input type="text" id="mobile_phone" name="mobile_phone" value="{{ old('mobile_phone') }}">
            </div>
        </div>

        <button type="submit" class="btn-primary">Create Staff Account</button>
        <a href="{{ route('admin.staff.index') }}" style="margin-left:1rem;">Cancel</a>
    </form>
</div>
@endsection
