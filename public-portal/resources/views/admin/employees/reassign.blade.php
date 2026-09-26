@extends('layouts.admin')

@section('title', 'Reassign Employee - Admin')

@push('styles')
<style>
    .reassign-wrap { display: flex; gap: 2rem; align-items: flex-start; max-width: 800px; }
    .reassign-col { flex: 1; }
    .reassign-col h3 { font-size: 0.95rem; margin-bottom: 0.5rem; }
    .reassign-arrow { font-size: 1.8rem; color: #888; padding-top: 2rem; }
</style>
@endpush

@section('content')
    <h1>Reassign Employee</h1>
    <p class="hint" style="margin-bottom:1.5rem;">Move an employee to a different client. Pick either side first &mdash; once both are chosen, you'll be asked to confirm.</p>

    <div class="reassign-wrap">
        <div class="reassign-col">
            <h3>Employee</h3>
            <div class="admin-search-wrap">
                <input type="text" id="employee-search" class="admin-search-box" autocomplete="off" placeholder="Search by name, email, phone, or ID...">
                <div id="employee-flyout" class="admin-search-flyout"></div>
            </div>
            <div id="employee-chosen" class="admin-inline-feedback"></div>
        </div>

        <div class="reassign-arrow">&rarr;</div>

        <div class="reassign-col">
            <h3>New Client</h3>
            <div class="admin-search-wrap">
                <input type="text" id="client-search" class="admin-search-box" autocomplete="off" placeholder="Search by company, record name, or client ID...">
                <div id="client-flyout" class="admin-search-flyout"></div>
            </div>
            <div id="client-chosen" class="admin-inline-feedback"></div>
        </div>
    </div>

    <form method="POST" id="reassign-form" style="display:none;">
        @csrf
        @method('PATCH')
    </form>

    <script>
    (function () {
        let chosenStudent = null; // { id, name, employerClientId }
        let chosenClient = null;  // { id, label }

        function wireFlyout(inputId, flyoutId, searchUrl, resultRenderer, onPick) {
            const input = document.getElementById(inputId);
            const flyout = document.getElementById(flyoutId);
            let debounceTimer;

            input.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                const q = this.value.trim();
                // Michael, 2026-08-23 pattern (Client/Find Employee search):
                // a single digit is still a meaningful, narrowing ID query.
                const isNumeric = /^\d+$/.test(q);
                if (q.length < (isNumeric ? 1 : 2)) {
                    flyout.style.display = 'none';
                    return;
                }
                debounceTimer = setTimeout(() => {
                    fetch(searchUrl + '?q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(results => {
                            if (!results.length) {
                                flyout.innerHTML = '<div style="padding:0.5rem 0.75rem; color:#888;">No matches</div>';
                                flyout.style.display = 'block';
                                return;
                            }
                            flyout.innerHTML = results.map(resultRenderer).join('');
                            flyout.style.display = 'block';
                        });
                }, 250);
            });

            flyout.addEventListener('click', function (e) {
                const opt = e.target.closest('.admin-search-option');
                if (!opt) return;
                onPick(opt.dataset);
                input.value = '';
                flyout.style.display = 'none';
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#' + inputId) && !e.target.closest('#' + flyoutId)) {
                    flyout.style.display = 'none';
                }
            });
        }

        // Michael, 2026-08-24 -- matches real DIBs source for this exact
        // page: pick either side in any order; the moment BOTH are set,
        // confirmation fires automatically, not gated behind a separate
        // submit button click.
        function maybeConfirm() {
            if (!chosenStudent || !chosenClient) return;

            if (!chosenStudent.employerClientId) {
                alert('This employee has no current employer on file, so there is nothing to reassign from.');
                return;
            }
            if (String(chosenStudent.employerClientId) === String(chosenClient.id)) {
                alert('This employee is already assigned to that client.');
                chosenClient = null;
                document.getElementById('client-chosen').style.display = 'none';
                return;
            }

            const ok = confirm('Are you sure you want to reassign employee:\n    ' + chosenStudent.name
                + '\nto destination client:\n    ' + chosenClient.label + ' ?');
            if (!ok) {
                chosenStudent = null;
                chosenClient = null;
                document.getElementById('employee-chosen').style.display = 'none';
                document.getElementById('client-chosen').style.display = 'none';
                return;
            }

            const form = document.getElementById('reassign-form');
            form.action = '{{ url('/admin/clients') }}/' + chosenStudent.employerClientId + '/students/' + chosenStudent.id + '/reassign';
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'new_client_id';
            hidden.value = chosenClient.id;
            form.appendChild(hidden);
            form.submit();
        }

        wireFlyout('employee-search', 'employee-flyout', '{{ route('admin.employees.search') }}',
            (s) => '<div class="admin-search-option" data-id="' + s.id + '" data-name="' + s.name + '" data-employer-id="' + (s.employerClientId ?? '') + '">'
                + '<strong>' + s.name + '</strong> <span style="color:#aaa;">(' + (s.studentNumber ?? ('#' + s.id)) + ')</span>'
                + (s.employerClientName ? ' <span style="color:#888;">&mdash; ' + s.employerClientName + '</span>' : '')
                + '</div>',
            (data) => {
                chosenStudent = { id: data.id, name: data.name, employerClientId: data.employerId };
                const box = document.getElementById('employee-chosen');
                box.style.display = 'block';
                box.innerHTML = '<strong>Selected:</strong> ' + data.name;
                maybeConfirm();
            }
        );

        wireFlyout('client-search', 'client-flyout', '{{ route('admin.clients.search') }}',
            (c) => '<div class="admin-search-option" data-id="' + c.id + '" data-label="' + c.label + '">'
                + c.label + ' <span style="color:#aaa;">(#' + c.id + ')</span></div>',
            (data) => {
                chosenClient = { id: data.id, label: data.label };
                const box = document.getElementById('client-chosen');
                box.style.display = 'block';
                box.innerHTML = '<strong>Selected:</strong> ' + data.label;
                maybeConfirm();
            }
        );
    })();
    </script>
@endsection
