@extends('layouts.app')

@section('title', 'Enroll Employees - Compliance Assurance Associates, Inc.')

@push('styles')
<style>
    .enroll-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .enroll-table th, .enroll-table td { padding: 0.6rem 0.75rem; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; vertical-align: top; }
    .enroll-table th { background: #f9fafb; font-weight: 600; }
    .hint { font-size: 0.82rem; color: #888888; }
    .vr-tag { display: inline-block; padding: 0.1rem 0.5rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; background: #eef2ff; color: #4338ca; margin-left: 0.4rem; }
    select { padding: 0.4rem 0.6rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.85rem; width: 100%; max-width: 280px; }
    .components-choice { margin-top: 0.5rem; }
    .components-choice label { display: inline-block; margin-right: 1rem; font-size: 0.85rem; }
    .components-choice input { margin-right: 0.3rem; }
    .components-choice.is-disabled label { color: #aaaaaa; }
</style>
@endpush

@section('content')
    @include('account.partials.nav')

    <div class="reg-wrap-wide">
        <div class="reg-header">
            <h1>Enroll Employees</h1>
            <p>Pick a session for each employee, then choose Lecture, Field, or Both. Only employees with a completed selection will be submitted.</p>
        </div>

        <div class="reg-card">
            {{--
                Michael, 2026-08-25 -- full rebuild to match the real,
                existing client portal's own shape (source provided by
                Michael): one row per EMPLOYEE, not one row per session --
                each employee gets their own session dropdown and their
                own Lecture Only/Field Only/Both/None choice, submitted
                together in one form.

                Every session in each dropdown already passed both
                eligibility checks (Public/Private/Semi-Private/VTCA
                host-or-authorized-list, and the VR client flag) on the
                Java side before it ever reached this page -- see
                Account\EnrollmentController's own docblock. Nothing
                shown here can be rejected on submit for session-
                eligibility reasons; only genuine data issues (already
                enrolled for that component, no valid email on file)
                can still occur, and are reported back per-employee
                after submit.

                In-person lecture availability is deliberately NOT a
                choice here -- confirmed with Michael: that's
                established when a session is planned with the client
                (pricing/scheduling, staff-side), not something the
                enrollment itself needs to expose. "Lecture Only" here
                always means the self-paced lecture.
            --}}
            @if (empty($employees))
                <p class="hint">No employees on file yet. <a href="{{ route('account.employees') }}">Add one first &raquo;</a></p>
            @elseif (empty($eligibleSessions))
                <p class="hint">No sessions are currently available for enrollment.</p>
            @else
                <form method="POST" action="{{ route('account.enrollments.store') }}">
                    @csrf
                    <table class="enroll-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Session</th>
                                <th>Enroll For</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                <tr>
                                    <td>{{ $employee['name'] ?? '' }}</td>
                                    <td>
                                        <select name="session[{{ $employee['id'] }}]" class="session-select" data-emp="{{ $employee['id'] }}">
                                            <option value="">-- Select Session --</option>
                                            @foreach ($eligibleSessions as $session)
                                                <option value="{{ $session['id'] }}">
                                                    {{ $session['locationName'] ?? ('Session #' . $session['id']) }}
                                                    ({{ $session['schoolType'] ?? '' }}{{ ($session['vrSession'] ?? false) ? ' - VR' : '' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="components-choice is-disabled" id="components-wrap-{{ $employee['id'] }}">
                                            <label>
                                                <input type="radio" name="components[{{ $employee['id'] }}]" value="LECTURE_ONLY" disabled>
                                                Lecture Only
                                            </label>
                                            <label>
                                                <input type="radio" name="components[{{ $employee['id'] }}]" value="FIELD_ONLY" disabled>
                                                Field Only
                                            </label>
                                            <label>
                                                <input type="radio" name="components[{{ $employee['id'] }}]" value="BOTH" disabled>
                                                Both
                                            </label>
                                            <label>
                                                <input type="radio" name="components[{{ $employee['id'] }}]" value="" checked disabled>
                                                None
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="submit" class="btn-primary" style="margin-top:1.5rem;">Enroll Selected Employees</button>
                </form>

                <script>
                (function () {
                    // Michael, 2026-08-25 -- matches the real source's
                    // own behavior: the Lecture/Field/Both/None choice
                    // is disabled until a session is actually picked
                    // for that row, rather than always-editable and
                    // easy to leave in a half-filled state.
                    document.querySelectorAll('.session-select').forEach(function (select) {
                        select.addEventListener('change', function () {
                            const empId = this.dataset.emp;
                            const wrap = document.getElementById('components-wrap-' + empId);
                            const radios = wrap.querySelectorAll('input[type="radio"]');
                            const hasSession = this.value !== '';

                            radios.forEach(function (radio) {
                                radio.disabled = !hasSession;
                            });
                            wrap.classList.toggle('is-disabled', !hasSession);

                            if (!hasSession) {
                                wrap.querySelector('input[value=""]').checked = true;
                            }
                        });
                    });
                })();
                </script>
            @endif

            {{-- Section 4: host clients can self-service authorize outside
                 organizations on a Semi-Private session -- Private sessions reject
                 this with a friendly validation error rather than a raw one. --}}
            <section style="margin-top:2rem;">
                <h2 style="font-size:1.1rem;">Authorize an Organization for a Semi-Private Session</h2>
                <form method="POST" action="{{ route('account.authorized-clients.store') }}">
                    @csrf
                    <div class="reg-field">
                        <label for="session_id_for_auth">Session ID</label>
                        <input type="text" id="session_id_for_auth" name="session_id" required style="max-width:200px;">
                    </div>
                    <div class="reg-field">
                        <label for="client_id">Client # to authorize</label>
                        <input type="text" id="client_id" name="client_id" required style="max-width:200px;">
                    </div>
                    <button type="submit" class="btn-primary">Authorize</button>
                </form>
                @error('client_id')
                    <p style="color:#b82027;">{{ $message }}</p>
                @enderror
            </section>
        </div>
    </div>
@endsection
