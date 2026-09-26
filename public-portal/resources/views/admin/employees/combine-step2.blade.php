@extends('layouts.admin')

@section('title', 'Combine Employees - ' . ($clientRecord['recordName'] ?? $clientRecord['company'] ?? 'Client') . ' - Admin')

@section('content')
    <h1>Combine Employees: {{ $clientRecord['recordName'] ?? $clientRecord['company'] ?? '' }}</h1>
    <p><a href="{{ route('admin.employees.combine.step1') }}">&laquo; Choose a different company</a></p>
    <p class="hint">Pick the two employee records to merge. The record with the lower, older ID always survives &mdash; whichever column you check it in.</p>

    @if (empty($students))
        <p class="hint">No employees on file for this company yet.</p>
    @else
        {{--
            Michael, 2026-08-31 -- appearance cleanup: dropped the
            page-specific .combine-table class -- a plain <table>
            already gets the real brand styling from caa-brand.css.
        --}}
        <form method="POST" action="{{ route('admin.clients.students.combine', ['client' => $clientRecord['id'], 'student' => '__SURVIVOR__']) }}" id="combine-form" onsubmit="return handleSubmit(event);">
            @csrf
            <table style="max-width:700px;">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th style="text-align:center;">Employee 1</th>
                        <th style="text-align:center;">Employee 2</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $s)
                        <tr>
                            <td>
                                {{ $s['name'] ?? '' }}
                                <div class="hint">{{ $s['studentNumber'] ?? ('#' . $s['id']) }}{{ ($s['active'] ?? true) ? '' : ' — Inactive' }}</div>
                            </td>
                            <td style="text-align:center;"><input type="radio" name="employee1" value="{{ $s['id'] }}"></td>
                            <td style="text-align:center;"><input type="radio" name="employee2" value="{{ $s['id'] }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn-primary" style="margin-top:1rem;">Combine Selected Employees</button>
        </form>

        <script>
        function handleSubmit(event) {
            const emp1 = document.querySelector('input[name="employee1"]:checked');
            const emp2 = document.querySelector('input[name="employee2"]:checked');

            if (!emp1 || !emp2) {
                alert('Error: you need to pick both\nEmployee 1 AND Employee 2');
                event.preventDefault();
                return false;
            }
            if (emp1.value === emp2.value) {
                alert('Error: Employee 1 and Employee 2 can\'t be the same record.');
                event.preventDefault();
                return false;
            }

            // Michael, 2026-08-24 -- matches real DIBs source: the lower
            // id always survives, so the confirmation message says so
            // plainly before anything happens, regardless of which
            // column the person happened to check it in. The server
            // enforces this same rule independently (see
            // StudentController.combine()'s own comment) -- this is
            // just showing the person the true outcome up front.
            const id1 = parseInt(emp1.value, 10);
            const id2 = parseInt(emp2.value, 10);
            const survivorId = Math.min(id1, id2);
            const duplicateId = Math.max(id1, id2);

            const ok = confirm('Are you sure you want to combine employee #' + duplicateId + ' into #' + survivorId + '?');
            if (!ok) {
                event.preventDefault();
                return false;
            }

            const form = document.getElementById('combine-form');
            form.action = form.action.replace('__SURVIVOR__', survivorId);
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'duplicate_student_id';
            hidden.value = duplicateId;
            form.appendChild(hidden);
            return true;
        }
        </script>
    @endif
@endsection
