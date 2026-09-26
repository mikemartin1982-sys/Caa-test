@extends('layouts.admin')

@section('title', 'Staff Accounts - Admin')

@section('content')
    <h1>Staff Accounts</h1>
    <p><a href="{{ route('admin.staff.create') }}" class="btn-primary" style="display:inline-block; padding:0.5rem 1rem; text-decoration:none;">+ Create Staff Account</a></p>

    {{--
        Michael, 2026-08-31 -- appearance cleanup: dropped the
        page-specific .staff-table class -- a plain <table> already
        gets the real brand styling from caa-brand.css. Role badges now
        build on the shared .status-badge pill wrapper (.status-role-admin/
        .status-role-staff), the same pattern as every other status badge
        across admin, instead of their own separate wrapper CSS.
    --}}
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Email</th>
                <th>Job Title</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($staff as $s)
                <tr>
                    <td>{{ $s['name'] ?? '' }}</td>
                    <td>{{ $s['username'] ?? '' }}</td>
                    <td>
                        @if (($s['role'] ?? '') === 'COMPLIANCE_ADMINISTRATOR')
                            <span class="status-badge status-role-admin">Compliance Administrator</span>
                        @else
                            <span class="status-badge status-role-staff">Staff</span>
                        @endif
                    </td>
                    <td>{{ $s['email'] ?? '—' }}</td>
                    <td>{{ $s['jobTitle'] ?? '—' }}</td>
                    <td><a href="{{ route('admin.staff.edit', ['staff' => $s['id']]) }}">Edit</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
