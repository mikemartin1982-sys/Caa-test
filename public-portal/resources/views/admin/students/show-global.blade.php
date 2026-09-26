@extends('layouts.admin')

@section('title', ($student['name'] ?? 'Student') . ' - Admin')

@section('content')
    <h1>{{ $student['name'] ?? '' }}</h1>
    <p>
        <a href="{{ route('admin.employees.index') }}">&laquo; Back to Employee Search</a>
    </p>

    {{--
        Michael, 2026-08-30 -- Lecture Certificate Upload feature. This
        page exists specifically for a student with NO employer client
        on file (a real, expected case from the future legacy-data
        migration, confirmed with Michael) -- so unlike the
        client-scoped admin.students.show page, there's no client
        context to show at all. Flagged plainly rather than silently
        omitted, so staff understand why this looks different from the
        usual student page, not wonder if something's broken.
    --}}
    <p class="hint">This student has no employer client on file.</p>

    <div class="admin-card">
        <div class="info-row"><strong>Student #</strong> {{ $student['studentNumber'] ?? '' }}</div>
        <div class="info-row"><strong>Email</strong> {{ $student['email'] ?? '' }}</div>
        <div class="info-row"><strong>Phone</strong> {{ $student['phone'] ?? '—' }}</div>
        <div class="info-row"><strong>Status</strong> {{ ($student['active'] ?? false) ? 'Active' : 'Inactive' }}</div>
    </div>

    @include('admin.students.partials.lecture-certificate')
@endsection
