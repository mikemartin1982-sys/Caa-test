@extends('layouts.admin')

@section('title', 'Combine Employees - Admin')

@section('content')
    <h1>Combine Employees</h1>
    <p class="hint" style="margin-bottom:1.5rem;">Start typing a company name. Duplicate employee records almost always happen within the same company, so pick that company first to see its own employee list.</p>

    <div class="admin-search-wrap">
        <input type="text" id="company-search" class="admin-search-box" autocomplete="off" placeholder="Search by company or record name...">
        <div id="company-flyout" class="admin-search-flyout"></div>
    </div>

    <script>
    (function () {
        const input = document.getElementById('company-search');
        const flyout = document.getElementById('company-flyout');
        let debounceTimer;

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
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
                        flyout.innerHTML = results.map(c =>
                            '<div class="admin-search-option" data-id="' + c.id + '">' + c.label + ' <span style="color:#aaa;">(#' + c.id + ')</span></div>'
                        ).join('');
                        flyout.style.display = 'block';
                    });
            }, 250);
        });

        flyout.addEventListener('click', function (e) {
            const opt = e.target.closest('.admin-search-option');
            if (!opt) return;
            window.location.href = '{{ url('/admin/employees/combine') }}/' + opt.dataset.id;
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#company-search') && !e.target.closest('#company-flyout')) {
                flyout.style.display = 'none';
            }
        });
    })();
    </script>
@endsection
