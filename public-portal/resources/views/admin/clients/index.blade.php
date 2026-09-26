@extends('layouts.admin')

@section('title', 'Clients - Admin')

@section('content')
    <h1>Clients</h1>

    <form method="GET" action="{{ route('admin.clients.index') }}" style="margin-bottom:1rem;">
        <div class="admin-search-wrap" style="margin-right:0.75rem;">
            <input type="text" id="client-search-name" name="q" class="admin-search-box" autocomplete="off"
                   placeholder="Search by company, record name, or client ID..." value="{{ $query }}" autofocus>
            <div id="client-search-name-flyout" class="admin-search-flyout"></div>
        </div>
        {{-- Visible Search button removed (Michael, 2026-08-23) -- the
             flyout already jumps straight to a client on click, making
             it redundant for the common case. The form itself stays,
             so pressing Enter still triggers the full-table search
             below -- the one case the flyout doesn't cover, since it's
             capped at 10 results while the full table shows every
             match. --}}
    </form>

    <div class="admin-search-wrap">
        <label for="client-search-email" style="display:block; font-size:0.85rem; font-weight:600; color:#444444; margin-bottom:0.35rem;">Search by Email</label>
        <input type="text" id="client-search-email" class="admin-search-box" autocomplete="off" placeholder="Type an email address...">
        <div id="client-search-email-flyout" class="admin-search-flyout"></div>
    </div>

    <script>
    (function () {
        // Michael, 2026-08-23: two independent flyouts on this page --
        // the main name/company search box, and a separate email-only
        // box. Both hit the same admin.clients.search endpoint (which
        // now matches on email too, not just name/company/ID) --
        // deliberately kept as one shared function rather than two
        // near-duplicate copies, since the only real difference
        // between them is which input/flyout element pair they're
        // wired to.
        function wireFlyout(inputId, flyoutId) {
            const input = document.getElementById(inputId);
            const flyout = document.getElementById(flyoutId);
            let debounceTimer;

            input.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                const q = this.value.trim();
                // Michael, 2026-08-23: a single digit is still a
                // meaningful, narrowing client-ID query (e.g. "2" as
                // the start of client #2, #21, #25...) even though a
                // single letter would be too noisy for name/company
                // matching -- so the usual 2-character minimum is
                // relaxed specifically for purely-numeric input.
                const isNumeric = /^\d+$/.test(q);
                if (q.length < (isNumeric ? 1 : 2)) {
                    flyout.style.display = 'none';
                    return;
                }
                debounceTimer = setTimeout(() => {
                    fetch('{{ route('admin.clients.search') }}?q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(results => {
                            if (!results.length) {
                                flyout.innerHTML = '<div style="padding:0.5rem 0.75rem; color:#888;">No matches</div>';
                                flyout.style.display = 'block';
                                return;
                            }
                            flyout.innerHTML = results.map(c => {
                                const loc = [c.city, c.state].filter(Boolean).join(', ');
                                return '<div class="admin-search-option" data-id="' + c.id + '">'
                                    + '<strong>' + c.label + '</strong>'
                                    + (loc ? ' <span style="color:#888; font-size:0.85em;">&mdash; ' + loc + '</span>' : '')
                                    + ' <span style="color:#aaa; font-size:0.8em;">(#' + c.id + ')</span>'
                                    + '</div>';
                            }).join('');
                            flyout.style.display = 'block';
                        });
                }, 250);
            });

            flyout.addEventListener('click', function (e) {
                const opt = e.target.closest('.admin-search-option');
                if (!opt) return;
                window.location.href = '{{ url('/admin/clients') }}/' + opt.dataset.id + '/edit';
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#' + inputId) && !e.target.closest('#' + flyoutId)) {
                    flyout.style.display = 'none';
                }
            });
        }

        wireFlyout('client-search-name', 'client-search-name-flyout');
        wireFlyout('client-search-email', 'client-search-email-flyout');
    })();
    </script>

    @if ($query !== '')
        @if (empty($clients))
            <p class="hint" style="margin-top:1rem;">No clients matched "{{ $query }}".</p>
        @else
            <div class="admin-card">
                <table>
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>City / State</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $c)
                            <tr>
                                <td>{{ $c['recordName'] ?? $c['company'] ?? '' }}</td>
                                <td>{{ trim(($c['firstName'] ?? '') . ' ' . ($c['lastName'] ?? '')) }}</td>
                                <td>{{ trim(($c['city'] ?? '') . (isset($c['city'], $c['state']) ? ', ' : '') . ($c['state'] ?? '')) }}</td>
                                <td>{{ $c['email'] ?? '—' }}</td>
                                <td>{{ $c['clientType'] ?? '—' }}</td>
                                <td><a href="{{ route('admin.clients.edit', ['client' => $c['id']]) }}">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <p class="hint" style="margin-top:1rem;">Search for a client by company name, record name, or client ID.</p>
    @endif
@endsection
