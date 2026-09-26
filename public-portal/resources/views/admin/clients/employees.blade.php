@extends('layouts.admin')

@section('title', 'Employees - ' . ($clientRecord['recordName'] ?? $clientRecord['company'] ?? 'Client') . ' - Admin')

@section('content')
    <h1>Employees: {{ $clientRecord['recordName'] ?? $clientRecord['company'] ?? '' }}</h1>
    <p><a href="{{ route('admin.clients.edit', ['client' => $clientRecord['id']]) }}">&laquo; Back to Client</a></p>

    @if (empty($employeeRoster))
        <p class="hint">No employees on file yet.</p>
    @else
        {{--
            Michael, 2026-08-31 -- appearance cleanup: stripped the
            scattered inline style="padding:0.5rem;" on every cell plus
            its own hardcoded border colors -- a plain <table> already
            gets the real, established brand styling from caa-brand.css.
        --}}
        <div class="admin-card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Active</th>
                        <th>Last Lecture</th>
                        <th>Last Field</th>
                        <th>Enrolled</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employeeRoster as $emp)
                        <tr>
                            <td>
                                <a href="{{ route('admin.students.show', ['client' => $clientRecord['id'], 'student' => $emp['id']]) }}">
                                    <strong>{{ $emp['name'] ?? '' }}</strong>
                                </a>
                                <div class="hint">{{ $emp['studentNumber'] ?? '' }}</div>
                            </td>
                            <td>
                                {{ $emp['email'] ?? '' }}<br>
                                <span class="hint">{{ $emp['phone'] ?? '—' }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.clients.students.update', ['client' => $clientRecord['id'], 'student' => $emp['id']]) }}">
                                    @csrf @method('PATCH')
                                    <label>
                                        <input type="checkbox" name="active" value="1" @checked($emp['active'] ?? false) onchange="this.form.submit()">
                                        {{ ($emp['active'] ?? false) ? 'Active' : 'Inactive' }}
                                    </label>
                                </form>
                            </td>
                            <td>
                                @if ($emp['lectureComplete'] ?? false)
                                    {{ $emp['lectureCompletionDate'] ?? '' }}
                                    <div class="hint">{{ $emp['lectureCompletionSource'] ?? '' }}</div>
                                @else
                                    <span class="hint">Not on file</span>
                                @endif
                            </td>
                            <td>
                                @if (!empty($emp['lastFieldDate']))
                                    {{ \Illuminate\Support\Carbon::parse($emp['lastFieldDate'])->format('m/d/Y') }}
                                    {{-- Near Expiration deliberately not shown here (Michael, 2026-08-23) --
                                         the underlying logic is computed and available in the API response
                                         (lastFieldStatus), but whether/where to surface that specific state
                                         on this table is left for management to decide, not baked in here.
                                         Only Certified/Expired are shown for now. --}}
                                    @if (($emp['lastFieldStatus'] ?? null) === 'EXPIRED')
                                        <div class="hint" style="color:#b82027;">Expired</div>
                                    @else
                                        <div class="hint" style="color:#16803C;">Certified</div>
                                    @endif
                                @else
                                    <span class="hint">No field cert on file</span>
                                @endif
                            </td>
                            <td>
                                @if (!empty($emp['currentEnrollmentSessionId']))
                                    <a href="{{ route('admin.sessions.roster', ['session' => $emp['currentEnrollmentSessionId']]) }}">
                                        {{ $emp['currentEnrollmentSessionName'] ?? ('Session #' . $emp['currentEnrollmentSessionId']) }}
                                        @if ($emp['currentEnrollmentIsVr'] ?? false) (VR) @endif
                                    </a>
                                @else
                                    {{-- Michael, 2026-08-23 -- closes the loop DIBs' own roster page
                                         had: each row not currently enrolled gets a direct link into
                                         Manual Enroll, pre-selecting both this client AND this specific
                                         student, not just the client. --}}
                                    <a href="{{ route('admin.enroll.create', ['client' => $clientRecord['id'], 'student' => $emp['id']]) }}">Enroll &raquo;</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{--
            Reassign Employee and Combine deliberately removed from this
            table (Michael, 2026-08-24) -- they briefly lived here as
            per-row inline panels, but that put two consequential,
            hard-to-reverse actions one misclick away from every name in
            a long list. Both now live on their own dedicated pages
            instead (admin.employees.reassign / admin.employees.combine),
            matching real DIBs source Michael provided for this exact
            purpose -- a standalone two-sided search with a deliberate
            confirmation step before anything happens.
        --}}
    @endif
@endsection
