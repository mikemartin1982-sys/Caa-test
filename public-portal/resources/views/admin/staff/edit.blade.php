@extends('layouts.admin')

@section('title', 'Edit Staff Account - Admin')

@section('content')
{{-- Michael, 2026-08-31 -- same appearance cleanup as Create Staff Account. --}}
<div class="admin-form-wrap">
    <h1>Edit Staff Account</h1>

    @if ($errors->any())
        <div class="admin-form-errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="admin-form-row">
        <div class="admin-form-field">
            <label for="username_display">Username</label>
            <input type="text" id="username_display" value="{{ $staffMember['username'] ?? '' }}" disabled>
            <p class="hint" style="margin-top:0.3rem;">Username can't be changed here.</p>
        </div>
        <div class="admin-form-field">
            <label>Password</label>
            <input type="text" value="Not editable from this form" disabled>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.staff.update', ['staff' => $staffMember['id']]) }}">
        @csrf
        @method('PATCH')

        <div class="admin-form-row">
            <div class="admin-form-field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required value="{{ old('name', $staffMember['name'] ?? '') }}">
            </div>
            <div class="admin-form-field">
                <label for="initials">Initials</label>
                <input type="text" id="initials" name="initials" value="{{ old('initials', $staffMember['initials'] ?? '') }}">
            </div>
        </div>

        <div class="admin-form-field">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="STAFF" {{ old('role', $staffMember['role'] ?? '') === 'STAFF' ? 'selected' : '' }}>Staff</option>
                <option value="COMPLIANCE_ADMINISTRATOR" {{ old('role', $staffMember['role'] ?? '') === 'COMPLIANCE_ADMINISTRATOR' ? 'selected' : '' }}>Compliance Administrator</option>
            </select>
            <p class="hint" style="margin-top:0.3rem;">Compliance Administrator can manage staff accounts, session confirmation emails, and bid generation. Choose deliberately.</p>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $staffMember['email'] ?? '') }}">
            </div>
            <div class="admin-form-field">
                <label for="job_title">Job Title</label>
                <input type="text" id="job_title" name="job_title" value="{{ old('job_title', $staffMember['jobTitle'] ?? '') }}">
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-field">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $staffMember['phone'] ?? '') }}">
            </div>
            <div class="admin-form-field">
                <label for="mobile_phone">Mobile Phone</label>
                <input type="text" id="mobile_phone" name="mobile_phone" value="{{ old('mobile_phone', $staffMember['mobilePhone'] ?? '') }}">
            </div>
        </div>

        <button type="submit" class="btn-primary">Save Changes</button>
        <a href="{{ route('admin.staff.index') }}" style="margin-left:1rem;">Cancel</a>
    </form>
</div>
@endsection
