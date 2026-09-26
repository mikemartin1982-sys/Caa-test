@extends('layouts.admin')

@section('title', 'Find Employee - Admin')

@section('content')
    <h1>Find Employee</h1>

    <form method="GET" action="{{ route('admin.employees.index') }}" style="margin-bottom:1rem;">
        <div class="admin-search-wrap">
            <input type="text" id="employee-search-name" name="q" class="admin-search-box" autocomplete="off"
                   placeholder="Search by name, email, phone, or employee ID..." value="{{ $query }}" autofocus>
            <div id="employee-search-flyout" class="admin-search-flyout"></div>
        </div>
        {{-- No visible Search button (Michael, 2026-08-23 pattern from Client search) --
             the flyout jumps straight to a result on click; the form itself stays so
             Enter still triggers the full-table search below, the one case the flyout
             doesn't cover (capped results vs. every match). --}}
    </form>

    <script>
    (function () {
        const input = document.getElementById('employee-search-name');
        const flyout = document.getElementById('employee-search-flyout');
        let debounceTimer;

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            // Michael, 2026-08-23 pattern (Client search): a single
            // digit is still a meaningful, narrowing employee-ID query
            // even though a single letter would be too noisy for
            // name/email matching.
            const isNumeric = /^\d+$/.test(q);
            if (q.length < (isNumeric ? 1 : 2)) {
                flyout.style.display = 'none';
                return;
            }
            debounceTimer = setTimeout(() => {
                fetch('{{ route('admin.employees.search') }}?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(results => {
                        if (!results.length) {
                            flyout.innerHTML = '<div style="padding:0.5rem 0.75rem; color:#888;">No matches</div>';
                            flyout.style.display = 'block';
                            return;
                        }
                        flyout.innerHTML = results.map(s => {
                            const employer = s.employerClientName ? (' &mdash; ' + s.employerClientName) : '';
                            // Michael, 2026-09-04 -- Employee Search
                            // labeling, confirmed with Michael and
                            // Chasity: explicit Active/Inactive/Combined
                            // status shown right in each result, not
                            // left for staff to discover after clicking
                            // in. Combined takes priority over active/
                            // inactive -- a merged-away record's own
                            // active status is largely moot once staff
                            // should really be looking at the real,
                            // surviving record instead.
                            const status = s.combined ? 'Combined' : (s.active ? 'Active' : 'Inactive');
                            const statusColor = s.combined ? '#8a4a10' : (s.active ? '#16803c' : '#888');
                            return '<div class="admin-search-option" data-student-id="' + s.id + '" data-client-id="' + (s.employerClientId ?? '') + '">'
                                + '<strong>' + s.name + '</strong>'
                                + ' <span style="color:#aaa; font-size:0.8em;">(' + (s.studentNumber ?? ('#' + s.id)) + ')</span>'
                                + '<span style="color:#888; font-size:0.85em;">' + employer + '</span>'
                                + '<span style="color:' + statusColor + '; font-size:0.85em; font-weight:600;"> &mdash; ' + status + '</span>'
                                + '</div>';
                        }).join('');
                        flyout.style.display = 'block';
                    });
            }, 250);
        });

        flyout.addEventListener('click', function (e) {
            const opt = e.target.closest('.admin-search-option');
            // Michael, 2026-08-30 -- found while building the Lecture
            // Certificate Upload feature: this used to require
            // data-client-id and silently do nothing without it -- a
            // student with no employer client on file (a real,
            // expected case, not rare) previously produced a click
            // that visibly did nothing at all. Two real, different URL
            // patterns depending on whether a client is known --
            // route() itself isn't available in client-side JS, so
            // built directly here, matching admin.students.show
            // (client-scoped) vs admin.students.show-global exactly.
            if (!opt || !opt.dataset.studentId) return;
            window.location.href = opt.dataset.clientId
                ? '{{ url('/admin/clients') }}/' + opt.dataset.clientId + '/students/' + opt.dataset.studentId
                : '{{ url('/admin/students') }}/' + opt.dataset.studentId;
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#employee-search-name') && !e.target.closest('#employee-search-flyout')) {
                flyout.style.display = 'none';
            }
        });
    })();
    </script>

    @if ($query !== '')
        @if (empty($students))
            <p class="hint">No employees matched "{{ $query }}".</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Employee #</th>
                        <th>Employer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $s)
                        <tr>
                            <td>{{ $s['name'] ?? '' }}</td>
                            <td>{{ $s['studentNumber'] ?? '' }}</td>
                            <td>{{ $s['employerClientName'] ?? '—' }}</td>
                            <td>{{ $s['email'] ?? '' }}</td>
                            <td>{{ $s['phone'] ?? '—' }}</td>
                            <td>
                                @if ($s['combined'] ?? false)
                                    <span class="status-badge" style="background:#fdf0e6; color:#8a4a10;">Combined</span>
                                @elseif ($s['active'] ?? false)
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td>
                                {{--
                                    Michael, 2026-08-30 -- found while
                                    building the Lecture Certificate
                                    Upload feature: this used to link to
                                    the whole employer roster, not the
                                    student's own page, and was omitted
                                    entirely for a student with no
                                    employer client at all -- a silent
                                    dead end for exactly the "No Client
                                    Record" case real DIBs data shows is
                                    genuinely expected, not rare.
                                --}}
                                @if (!empty($s['employerClientId']))
                                    <a href="{{ route('admin.students.show', ['client' => $s['employerClientId'], 'student' => $s['id']]) }}">View Student</a>
                                @else
                                    <a href="{{ route('admin.students.show-global', ['student' => $s['id']]) }}">View Student</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <p class="hint" style="margin-top:1rem;">Search for an employee by name, email, phone, or employee ID.</p>
    @endif
@endsection
