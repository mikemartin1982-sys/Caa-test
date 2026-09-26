{{--
    Michael, 2026-08-24 -- shared across every account.* page, matching
    the real, existing client portal's own "Account Management Links"
    row (Home / Manage Employees / Enroll / Edit Acct Info / Current
    Enrollments). Restyled to match account.login's own visual language
    (.reg-* classes, now shared in layouts/app.blade.php) for uniformity
    across the whole portal, rather than the plain grey box this
    started as.
--}}
<div class="reg-subnav">
    <a href="{{ route('account.dashboard') }}">Client Home &raquo;</a>
    <a href="{{ route('account.employees') }}">Manage Employees &raquo;</a>
    <a href="{{ route('account.enroll') }}">Enroll &raquo;</a>
    <a href="{{ route('account.edit') }}">Edit Acct Info &raquo;</a>
    <a href="{{ route('account.current-enrollments') }}">Current Enrollments &raquo;</a>
    <span class="reg-subnav-id">Client ID: {{ $client->id }}</span>
</div>
