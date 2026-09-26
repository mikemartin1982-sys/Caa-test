@extends('layouts.app')

@section('title', 'Manage Employees - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .account-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .account-table th, .account-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
    .account-table th { background: #f9fafb; font-weight: 600; }
    .status-badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600; }
    .status-current { background: #e6f4ea; color: #16803C; }
    .status-expired { background: #fdecea; color: #b82027; }
    .hint { font-size: 0.82rem; color: #888888; }
    /* Michael, 2026-08-24 -- local pairing variant on the shared
       .reg-field (layouts/app.blade.php), same as account.edit. */
    .reg-field-pair { display: flex; gap: 1rem; }
    .reg-field-pair .reg-field { flex: 1; margin-bottom: 1.1rem; }
</style>
@endpush

@section('content')
    @include('account.partials.nav')

    <div class="reg-wrap-wide">
        <div class="reg-header">
            <h1>Manage Employees</h1>
        </div>

        <div class="reg-card">
            {{--
                Michael, 2026-08-24 -- reconciled from the orphaned Portal\
                namespace (see AccountDashboardController's own docblock), now
                backed by real certification data (the roster endpoint built
                for the admin side) instead of the hardcoded placeholder this
                table originally showed. Download links deliberately not
                included -- no certificate storage exists yet at all, so a
                link here would point to nothing rather than being honest
                about what's not built yet.
            --}}
            @if (empty($employees))
                <p class="hint">No employees on file yet.</p>
            @else
                <table class="account-table">
                    <thead>
                        <tr>
                            <th>Employee #</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Active</th>
                            <th>Last Lecture</th>
                            <th>Last Field Certification</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            <tr>
                                <td>{{ $employee['studentNumber'] ?? '' }}</td>
                                <td>{{ $employee['name'] ?? '' }}</td>
                                <td>{{ $employee['email'] ?? '' }}</td>
                                <td>{{ $employee['phone'] ?? '—' }}</td>
                                <td>
                                    {{-- Michael, 2026-08-24 -- "mark inactive." Submits to
                                         account.employees.update -- the client id in that
                                         request is always the logged-in client's own
                                         (never taken from this form), and the Java side
                                         independently verifies this employee actually
                                         belongs to them before allowing the change -- see
                                         AccountDashboardController::updateEmployee()'s own
                                         comment for the full reasoning. --}}
                                    <form method="POST" action="{{ route('account.employees.update', ['student' => $employee['id']]) }}">
                                        @csrf @method('PATCH')
                                        <label>
                                            <input type="checkbox" name="active" value="1" @checked($employee['active'] ?? false) onchange="this.form.submit()">
                                            {{ ($employee['active'] ?? false) ? 'Active' : 'Inactive' }}
                                        </label>
                                    </form>
                                </td>
                                <td>
                                    @if ($employee['lectureComplete'] ?? false)
                                        {{ $employee['lectureCompletionDate'] ?? 'Complete' }}
                                    @else
                                        <span class="hint">Not on file</span>
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($employee['lastFieldDate']))
                                        {{ \Illuminate\Support\Carbon::parse($employee['lastFieldDate'])->format('m/d/Y') }}
                                        @if (($employee['lastFieldStatus'] ?? null) === 'EXPIRED')
                                            <span class="status-badge status-expired">Expired</span>
                                        @else
                                            <span class="status-badge status-current">Current</span>
                                        @endif
                                    @else
                                        <span class="hint">No field certification on file</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Michael, 2026-08-24 -- "add to" (employee management). Submits
                 to account.employees.store, which always uses the logged-in
                 client's own id; there is no client id in this form at all. --}}
            <h3 style="margin-top:1.5rem;">Add Employee</h3>
            <form method="POST" action="{{ route('account.employees.store') }}">
                @csrf
                <div class="reg-field-pair">
                    <div class="reg-field">
                        <label for="emp_name">Name</label>
                        <input type="text" id="emp_name" name="name" required>
                    </div>
                    <div class="reg-field">
                        <label for="emp_email">Email</label>
                        <input type="email" id="emp_email" name="email" required>
                    </div>
                    <div class="reg-field">
                        <label for="emp_phone">Phone</label>
                        <input type="text" id="emp_phone" name="phone">
                    </div>
                </div>
                <button type="submit" class="btn-primary">Add Employee</button>
            </form>
        </div>
    </div>
@endsection
