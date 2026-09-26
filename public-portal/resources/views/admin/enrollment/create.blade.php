@extends('layouts.admin')

@section('title', 'Manual Enroll - Admin')

@push('styles')
<style>
    .enroll-wrap { max-width: 640px; }
    .enroll-section { border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 1.25rem; margin-bottom: 1.25rem; }
    .enroll-section h3 { margin-top: 0; font-size: 1rem; }
    .enroll-section.enroll-locked { opacity: 0.5; pointer-events: none; }
    .enroll-input { padding: 0.55rem 0.8rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.95rem; width: 100%; max-width: 320px; }
    .enroll-chosen-client { margin-top: 0.5rem; font-size: 0.9rem; }
    .enroll-add-student { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f0f0f0; }
</style>
@endpush

@section('content')
<div class="enroll-wrap">
    <h1>Manual Enroll</h1>

    @if (session('status'))
        <p style="color:#b82027; font-weight:600;">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.enroll.store') }}" id="enroll-form">
        @csrf
        <input type="hidden" name="session_id" id="chosen-session-id" value="{{ old('session_id') }}">
        <input type="hidden" name="client_id" id="chosen-client-id" value="{{ old('client_id', $preselectedClient['id'] ?? '') }}">
        <input type="hidden" name="student_id" id="chosen-student-id" value="{{ old('student_id', $preselectedStudentId ?? '') }}">

        {{-- ===================== 1. Session ===================== --}}
        <div class="enroll-section">
            <h3>1. Session</h3>
            <input type="number" id="session-id-input" class="enroll-input" placeholder="Session ID" value="{{ old('session_id', $preselectedSessionId ?? '') }}">
            <button type="button" id="lookup-session-btn" class="btn-secondary" style="margin-left:0.5rem; padding:0.5rem 0.9rem;">Lookup</button>
            <div class="hint">Enter the session ID (from the calendar or dashboard) and confirm it's the right one before enrolling into it.</div>
            <div id="session-confirm-box" class="admin-inline-feedback"></div>
        </div>

        {{-- ===================== 2. Client ===================== --}}
        <div class="enroll-section">
            <h3>2. Client</h3>
            @if ($preselectedClient)
                <div class="enroll-chosen-client">
                    <strong>{{ $preselectedClient['recordName'] ?? $preselectedClient['company'] ?? '' }}</strong>
                    <span style="color:#888;">(#{{ $preselectedClient['id'] }})</span>
                </div>
            @else
                <div class="admin-search-wrap">
                    <input type="text" id="client-search-input" class="enroll-input" autocomplete="off" placeholder="Search by company, record name, or client ID...">
                    <div id="client-search-flyout" class="admin-search-flyout"></div>
                </div>
                <div id="chosen-client-display" class="enroll-chosen-client"></div>
            @endif
        </div>

        {{-- ===================== 3. Student ===================== --}}
        <div class="enroll-section" id="student-section" @if(!$preselectedClient) style="opacity:0.5;" @endif>
            <h3>3. Student</h3>
            <select id="student-select" class="enroll-input">
                <option value="">-- Pick a client first --</option>
                @foreach ($preselectedStudents as $s)
                    <option value="{{ $s['id'] }}" @selected(old('student_id', $preselectedStudentId ?? null) == $s['id'])>{{ $s['name'] }} ({{ $s['studentNumber'] ?? '' }})</option>
                @endforeach
            </select>

            <div class="enroll-add-student">
                <a href="#" id="show-add-student" style="font-size:0.85rem;">+ Add a new employee not yet on file</a>
                <div id="add-student-form" style="display:none; margin-top:0.5rem;">
                    <input type="text" id="new-student-name" class="enroll-input" placeholder="Full name" style="margin-bottom:0.4rem;">
                    <input type="text" id="new-student-phone" class="enroll-input" placeholder="Phone (optional)" style="margin-bottom:0.4rem;">
                    <input type="email" id="new-student-email" class="enroll-input" placeholder="Email (required -- certificates are sent here)" style="margin-bottom:0.4rem;">
                    <button type="button" id="save-new-student-btn" class="btn-secondary" style="padding:0.4rem 0.9rem; font-size:0.85rem;">Add Employee</button>
                </div>
            </div>
        </div>

        {{-- ===================== 4. Enrollment Type ===================== --}}
        <div class="enroll-section">
            <h3>4. Enrollment Type</h3>
            <div class="hint" style="margin-top:0; margin-bottom:0.6rem;">
                In-person lecture availability is established when a session is planned with the client,
                not chosen here -- "Lecture Only" always means the self-paced lecture.
            </div>
            <label style="display:block; margin-bottom:0.4rem;">
                <input type="radio" name="components" value="LECTURE_ONLY" class="components-radio" @checked(old('components') === 'LECTURE_ONLY')> Lecture Only (self-paced)
            </label>
            <label style="display:block; margin-bottom:0.4rem;">
                <input type="radio" name="components" value="FIELD_ONLY" class="components-radio" @checked(old('components') === 'FIELD_ONLY')> Field Only
            </label>
            <label style="display:block;">
                <input type="radio" name="components" value="BOTH" class="components-radio" @checked(old('components') === 'BOTH')> Both
            </label>
        </div>

        <button type="submit" class="btn-primary" id="submit-enroll-btn" disabled>Enroll</button>
    </form>
</div>

<script>
(function () {
    const sessionIdInput = document.getElementById('session-id-input');
    const lookupBtn = document.getElementById('lookup-session-btn');
    const sessionConfirmBox = document.getElementById('session-confirm-box');
    const chosenSessionId = document.getElementById('chosen-session-id');

    const chosenClientId = document.getElementById('chosen-client-id');
    const clientSearchInput = document.getElementById('client-search-input');
    const clientSearchFlyout = document.getElementById('client-search-flyout');
    const chosenClientDisplay = document.getElementById('chosen-client-display');

    const studentSection = document.getElementById('student-section');
    const studentSelect = document.getElementById('student-select');
    const chosenStudentId = document.getElementById('chosen-student-id');

    const showAddStudent = document.getElementById('show-add-student');
    const addStudentForm = document.getElementById('add-student-form');
    const saveNewStudentBtn = document.getElementById('save-new-student-btn');

    const submitBtn = document.getElementById('submit-enroll-btn');
    const componentsRadios = document.querySelectorAll('.components-radio');

    // Michael, 2026-08-25 -- Client Portal Enroll rebuild carried over
    // to admin: submit now also requires an Enrollment Type choice,
    // same reasoning as the existing session/client/student checks --
    // prevents a half-filled-out form from posting and hitting a
    // confusing validation error server-side with no context for why.
    function refreshSubmitState() {
        const componentsChosen = document.querySelector('.components-radio:checked') !== null;
        submitBtn.disabled = !(chosenSessionId.value && chosenClientId.value && chosenStudentId.value && componentsChosen);
    }

    componentsRadios.forEach(function (radio) {
        radio.addEventListener('change', refreshSubmitState);
    });

    // --- 1. Session lookup ---
    // Michael, 2026-08-23 -- extracted into a named function (was
    // inline in the click handler) so it can also run automatically
    // on page load when arriving from a Session Roster page's own
    // "+ Enroll Student" link -- we already know exactly which
    // session that is, so there's no reason to make staff click
    // Lookup manually for something already known. Still shows the
    // same confirmation box either way, for verification.
    function lookupSession() {
        const id = sessionIdInput.value.trim();
        if (!id) return;
        fetch('{{ route('admin.enroll.lookup-session') }}?session_id=' + encodeURIComponent(id))
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                sessionConfirmBox.style.display = 'block';
                if (!ok) {
                    sessionConfirmBox.className = 'admin-inline-feedback admin-inline-feedback-error';
                    sessionConfirmBox.textContent = data.error || 'Session not found.';
                    chosenSessionId.value = '';
                } else {
                    sessionConfirmBox.className = 'admin-inline-feedback';
                    const loc = [data.addressCity, data.addressState].filter(Boolean).join(', ');
                    sessionConfirmBox.innerHTML = '<strong>' + (data.locationName || ('Session #' + data.id)) + '</strong>'
                        + ' &mdash; ' + data.schoolType + (data.vrSession ? ' (VR)' : '')
                        + (loc ? ' &mdash; ' + loc : '');
                    chosenSessionId.value = data.id;
                }
                refreshSubmitState();
            });
    }
    lookupBtn.addEventListener('click', lookupSession);

    // --- 2. Client flyout (only wired if not pre-selected) ---
    if (clientSearchInput) {
        let debounceTimer;
        clientSearchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            // Michael, 2026-08-23: relaxed to 1 character for purely
            // numeric input, so a single-digit client ID (e.g. "2")
            // actually triggers a search -- see admin/clients/index's
            // same fix for the full reasoning.
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
            loadStudentsForClient(opt.dataset.id);
            refreshSubmitState();
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#client-search-input') && !e.target.closest('#client-search-flyout')) {
                clientSearchFlyout.style.display = 'none';
            }
        });
    }

    // --- 3. Student dropdown, dynamically loaded once a client is chosen ---
    function loadStudentsForClient(clientId) {
        studentSection.style.opacity = '1';
        studentSelect.innerHTML = '<option value="">Loading...</option>';
        fetch('{{ url('/admin/enroll/clients') }}/' + clientId + '/students')
            .then(r => r.json())
            .then(students => {
                if (!students.length) {
                    studentSelect.innerHTML = '<option value="">No employees on file yet -- add one below</option>';
                    return;
                }
                studentSelect.innerHTML = '<option value="">-- Choose an employee --</option>'
                    + students.map(s => '<option value="' + s.id + '">' + s.name + ' (' + (s.studentNumber || '') + ')</option>').join('');
            });
    }

    studentSelect.addEventListener('change', function () {
        chosenStudentId.value = this.value;
        refreshSubmitState();
    });

    // --- Inline "add new employee" ---
    showAddStudent.addEventListener('click', function (e) {
        e.preventDefault();
        addStudentForm.style.display = addStudentForm.style.display === 'none' ? 'block' : 'none';
    });

    saveNewStudentBtn.addEventListener('click', function () {
        const clientId = chosenClientId.value;
        if (!clientId) {
            alert('Pick a client first.');
            return;
        }
        const name = document.getElementById('new-student-name').value.trim();
        if (!name) {
            alert('Name is required.');
            return;
        }
        const newStudentEmail = document.getElementById('new-student-email').value.trim();
        if (!newStudentEmail) {
            // Michael, 2026-08-23 -- email is required, not optional --
            // certificates are emailed to the student on successful
            // certification.
            alert('A valid email is required so certificates can be sent upon certification.');
            return;
        }
        fetch('{{ url('/admin/enroll/clients') }}/' + clientId + '/students', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
            },
            body: JSON.stringify({
                name: name,
                phone: document.getElementById('new-student-phone').value.trim() || null,
                email: document.getElementById('new-student-email').value.trim() || null,
            }),
        })
            .then(r => r.json())
            .then(student => {
                const opt = document.createElement('option');
                opt.value = student.id;
                opt.textContent = student.name + ' (' + (student.studentNumber || '') + ')';
                opt.selected = true;
                studentSelect.appendChild(opt);
                chosenStudentId.value = student.id;
                addStudentForm.style.display = 'none';
                document.getElementById('new-student-name').value = '';
                document.getElementById('new-student-phone').value = '';
                document.getElementById('new-student-email').value = '';
                refreshSubmitState();
            });
    });

    refreshSubmitState();

    // Michael, 2026-08-23 -- auto-lookup when arriving with a session
    // already known (a Session Roster page's own "+ Enroll Student"
    // link) -- the input's already pre-filled by Blade above; this
    // just runs the same confirmation step automatically instead of
    // requiring a manual click for something already known.
    @if (!empty($preselectedSessionId))
        lookupSession();
    @endif
})();
</script>
@endsection
