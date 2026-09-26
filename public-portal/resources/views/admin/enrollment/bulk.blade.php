@extends('layouts.app')

@section('title', 'Enroll Students - Compliance Assurance Associates, Inc.')

@section('content')
{{--
    Michael, 2026-08-31/09-01 -- QBO Per-Student Invoicing, Stage 3A.
    Confirmed with Michael as a genuinely separate flow from Manual
    Enroll, not a replacement of it -- this one replaces the Session
    Roster's own "+ Enroll Student" link specifically, since the real,
    confirmed pain point was that once a client was picked, only ONE
    employee could be enrolled at a time. Session is already known
    here (from the URL itself, since this is reached from a specific
    session's roster) -- no session-lookup step needed, unlike Manual
    Enroll's own step 1.

    Client-search flyout markup/JS adapted directly from Manual
    Enroll's own, already-working pattern (admin.clients.search,
    .admin-search-* shared classes) -- same real endpoint, same real
    response shape, not reinvented.
--}}
<div class="admin-content">
    <h1>Enroll Students</h1>
    <p class="hint">Search for a client, then choose Lecture and/or Field for each active employee you want to enroll.</p>

    @if (session('status'))
        <p class="hint" style="color:#b82027; font-weight:600;">{{ session('status') }}</p>
    @endif

    <div class="admin-card">
        <h2>1. Client</h2>
        <div class="admin-search-wrap">
            <input type="text" id="client-search-input" class="admin-form-field" autocomplete="off" placeholder="Search by company, record name, or client ID...">
            <div id="client-search-flyout" class="admin-search-flyout"></div>
        </div>
        <div id="chosen-client-display" style="margin-top:0.5rem;"></div>
    </div>

    <div class="admin-card" id="employees-card" style="display:none;">
        <h2>2. Employees</h2>
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Phone</th><th style="text-align:center;">Lecture</th><th style="text-align:center;">Field</th></tr>
            </thead>
            <tbody id="employees-table-body"></tbody>
        </table>

        <form method="POST" action="{{ route('admin.enroll.bulk.store', ['session' => $sessionId]) }}" id="bulk-enroll-form" style="margin-top:1.25rem;">
            @csrf
            <input type="hidden" name="client_id" id="chosen-client-id">
            <div id="hidden-student-inputs"></div>
            <button type="submit" class="btn-primary" id="submit-btn" disabled>Enroll Selected Employees</button>
        </form>
    </div>
</div>

<script>
(function () {
    const sessionId = {{ $sessionId }};
    const clientSearchInput = document.getElementById('client-search-input');
    const clientSearchFlyout = document.getElementById('client-search-flyout');
    const chosenClientDisplay = document.getElementById('chosen-client-display');
    const chosenClientId = document.getElementById('chosen-client-id');
    const employeesCard = document.getElementById('employees-card');
    const tbody = document.getElementById('employees-table-body');
    const hiddenInputs = document.getElementById('hidden-student-inputs');
    const submitBtn = document.getElementById('submit-btn');

    // --- 1. Client search, adapted directly from Manual Enroll's own pattern ---
    let debounceTimer;
    clientSearchInput.addEventListener('input', function () {
        const q = clientSearchInput.value.trim();
        clearTimeout(debounceTimer);
        const isNumeric = /^\d+$/.test(q);
        if (q.length < (isNumeric ? 1 : 2)) {
            clientSearchFlyout.style.display = 'none';
            return;
        }
        debounceTimer = setTimeout(() => {
            fetch('{{ route('admin.clients.search') }}?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(results => {
                    if (!results.length) {
                        clientSearchFlyout.innerHTML = '<div style="padding:0.5rem 0.75rem; color:#888;">No matches</div>';
                        clientSearchFlyout.style.display = 'block';
                        return;
                    }
                    clientSearchFlyout.innerHTML = results.map(c => {
                        const loc = [c.city, c.state].filter(Boolean).join(', ');
                        return '<div class="admin-search-option" data-id="' + c.id + '" data-label="' + c.label + '">'
                            + '<strong>' + c.label + '</strong>'
                            + (loc ? ' <span style="color:#888; font-size:0.85em;">&mdash; ' + loc + '</span>' : '')
                            + '</div>';
                    }).join('');
                    clientSearchFlyout.style.display = 'block';
                });
        }, 250);
    });

    clientSearchFlyout.addEventListener('click', function (e) {
        const opt = e.target.closest('.admin-search-option');
        if (!opt) return;
        chosenClientId.value = opt.dataset.id;
        chosenClientDisplay.innerHTML = '<strong>' + opt.dataset.label + '</strong> <span style="color:#888;">(#' + opt.dataset.id + ')</span>';
        clientSearchInput.value = '';
        clientSearchFlyout.style.display = 'none';
        loadEmployeesForClient(opt.dataset.id);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#client-search-input') && !e.target.closest('#client-search-flyout')) {
            clientSearchFlyout.style.display = 'none';
        }
    });

    // --- 2. Employee table, AJAX-loaded once a client is chosen ---
    function loadEmployeesForClient(clientId) {
        fetch('{{ url('/admin/sessions') }}/' + sessionId + '/bulk-enroll/clients/' + clientId + '/employees')
            .then(r => r.json())
            .then(data => {
                const employees = data.employees || [];
                employeesCard.style.display = 'block';
                if (!employees.length) {
                    tbody.innerHTML = '<tr><td colspan="5">No active employees for this client.</td></tr>';
                    submitBtn.disabled = true;
                    return;
                }
                tbody.innerHTML = employees.map(function (emp, i) {
                    var lectureNote = emp.alreadyLecture ? ' <span style="color:#888; font-size:0.8em;">(already enrolled)</span>' : '';
                    var fieldNote = emp.alreadyField ? ' <span style="color:#888; font-size:0.8em;">(already enrolled)</span>' : '';
                    return '<tr>'
                        + '<td>' + (emp.name || '') + '</td>'
                        + '<td>' + (emp.email || '') + '</td>'
                        + '<td>' + (emp.phone || '') + '</td>'
                        + '<td style="text-align:center;"><input type="checkbox" class="lecture-check" data-student-id="' + emp.id + '"' + (emp.alreadyLecture ? ' disabled' : '') + '>' + lectureNote + '</td>'
                        + '<td style="text-align:center;"><input type="checkbox" class="field-check" data-student-id="' + emp.id + '"' + (emp.alreadyField ? ' disabled' : '') + '>' + fieldNote + '</td>'
                        + '</tr>';
                }).join('');
                submitBtn.disabled = false;
            });
    }

    // --- 3. Build hidden inputs for every checked row right before submit ---
    document.getElementById('bulk-enroll-form').addEventListener('submit', function () {
        hiddenInputs.innerHTML = '';
        let index = 0;
        document.querySelectorAll('#employees-table-body tr').forEach(function (row) {
            const lectureCheck = row.querySelector('.lecture-check');
            const fieldCheck = row.querySelector('.field-check');
            if (!lectureCheck) return;
            const studentId = lectureCheck.dataset.studentId;
            if (!lectureCheck.checked && !fieldCheck.checked) return;

            hiddenInputs.innerHTML += '<input type="hidden" name="students[' + index + '][student_id]" value="' + studentId + '">'
                + (lectureCheck.checked ? '<input type="hidden" name="students[' + index + '][lecture]" value="1">' : '')
                + (fieldCheck.checked ? '<input type="hidden" name="students[' + index + '][field]" value="1">' : '');
            index++;
        });
    });
})();
</script>
@endsection
