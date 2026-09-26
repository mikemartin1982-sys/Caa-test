@extends('layouts.admin')

@section('title', 'Add Employee - ' . ($clientRecord['recordName'] ?? $clientRecord['company'] ?? 'Client') . ' - Admin')

@section('content')
    <h1>Add Employee: {{ $clientRecord['recordName'] ?? $clientRecord['company'] ?? '' }}</h1>
    <p><a href="{{ route('admin.employees.add.step1') }}">&laquo; Choose a different company</a></p>

    {{--
        Michael, 2026-08-31 -- appearance cleanup: this was a raw,
        unstyled error div (plain color, no background/border, plain
        <div> children not even <p>) -- a cruder version of the same
        validation-error pattern already shared as .admin-form-errors
        elsewhere.
    --}}
    @if ($errors->any())
        <div class="admin-form-errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.clients.students.store', ['client' => $clientRecord['id']]) }}">
        @csrf
        <div class="admin-form-field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="admin-form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            <p class="hint">Required &mdash; certificates are emailed to the employee upon certification.</p>
        </div>
        <div class="admin-form-field">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
        </div>
        <button type="submit" class="btn-primary">Add Employee</button>
    </form>
@endsection
