{{--
    Modernized from DIBs' real left-nav structure (view-source provided
    2026-08-15). Section/item labels and order match the original closely
    for continuity -- the visual treatment is what's modernized (brand
    colors/typography from caa-brand.css instead of the solid green fill).

    Each item is one of:
      - 'route'    => a real named route in THIS app -- live link
      - 'external' => a real external URL (Stacktest, Dynamics 365, etc.)
                      -- live link, opens in a new tab
      - disabled (no 'route'/'external' key) -- greyed, struck-through,
                      unclickable. Same visual convention DIBs already
                      uses for permission-restricted links (e.g. "Delete
                      Clients" in their own screenshot), reused here for
                      "doesn't exist in our build yet" rather than "not
                      authorized." Not a placeholder trick -- an honest
                      signal of what's real today vs. structural.

    NOTE: several of DIBs' real sub-items reference actual systems this
    project has never touched (QuickBooks invoicing UI, a "Database
    Utility," VR video rating tools, MP log analysis) -- those stay
    disabled until/unless there's a reason to build them.
--}}
@php
$adminMenu = [
    ['label' => 'Dashboard', 'icon' => 'fa-gauge', 'route' => 'admin.dashboard', 'items' => []],

    ['label' => 'Clients', 'icon' => 'fa-user', 'items' => [
        ['label' => 'View/Edit/Export/Add Clients'],
        ['label' => 'Combine Clients'],
        ['label' => 'Change Password'],
        ['label' => 'Delete Clients'],
    ]],

    ['label' => 'Employees', 'icon' => 'fa-users', 'items' => [
        ['label' => 'View/Edit/Export Employees'],
        ['label' => 'Add Employee'],
        ['label' => 'Find Employee'],
        ['label' => 'Reassign Employee'],
        ['label' => 'Combine'],
    ]],

    ['label' => 'Classes & Enroll', 'icon' => 'fa-building-columns', 'items' => [
        ['label' => 'Sessions View/Edit/List', 'route' => 'admin.dashboard'],
        ['label' => 'Rosters for Office-Use'],
        ['label' => 'Rosters for Field-Use'],
        ['label' => 'Digital Testing Admin', 'route' => 'admin.digital-testing.index'],
        ['label' => 'View Calendar', 'route' => 'admin.calendar'],
        ['label' => 'Add Class Session'],
        ['label' => 'Manual Enroll (add student)'],
        ['label' => 'School Prep'],
        ['label' => 'Roster Stats by Date'],
        ['label' => 'Compare Enroll of 2 Sess'],
        ['label' => 'Search for Bid'],
        ['label' => 'Public Session Evaluation'],
        ['label' => 'VR Sessions'],
        ['label' => 'VR State List Admin'],
        ['label' => 'VR Event Log'],
        ['label' => 'VR Videos Stats'],
        ['label' => 'VR Students'],
        ['label' => 'Rate Opacity Videos'],
    ]],

    ['label' => 'Payments', 'icon' => 'fa-credit-card', 'items' => [
        ['label' => 'Record Manual-Payment'],
        ['label' => 'Manage Payments by Date'],
        ['label' => 'View Invoice'],
        ['label' => 'Manual Gen QBO Invoice'],
        ['label' => 'QuickBooks Access'],
    ]],

    ['label' => 'Certifications', 'icon' => 'fa-certificate', 'items' => [
        ['label' => 'Cert History (view/print)'],
        ['label' => 'Certify Employee'],
        ['label' => 'Upload Certifications'],
        ['label' => 'Batch Print-Certificates'],
        ['label' => 'View/Edit Comp-Record'],
        ['label' => 'Cert Records by State'],
    ]],

    ['label' => 'Emails', 'icon' => 'fa-envelope', 'items' => [
        ['label' => 'Client Mailbox', 'route' => 'admin.mailbox.index'],
        ['label' => 'Template Library', 'external' => config('mailbox.template_library_url')],
        ['label' => 'Send Bulkmail to Roster'],
    ]],

    ['label' => 'CAA Resources', 'icon' => 'fa-id-badge', 'items' => [
        ['label' => 'List/Edit/Add Users'],
        ['label' => 'Manage Instructors'],
        ['label' => 'Manage Locations'],
        ['label' => 'Manage Cameras'],
        ['label' => 'Manage Calendar Events'],
        ['label' => 'Manage Self-Paced Lecture'],
        ['label' => 'Restart Apache'],
        ['label' => 'Company Documents'],
        ['label' => 'Trailers Travel Paths'],
        ['label' => 'Sign in on Stacktest', 'external' => 'http://stacktest.net:5782/user/sign_in'],
        ['label' => 'Trailer Status on Stacktest', 'external' => 'http://stacktest.net:5782/caa/37cba7bd-e94b-47eb-95d7-f6619dca4935/dashboard/trailer_status'],
    ]],

    ['label' => '3rd Party Resources', 'icon' => 'fa-rocket', 'items' => [
        ['label' => 'Dynamics 365', 'external' => 'https://orgd1169256.crm.dynamics.com/'],
        ['label' => 'SDS Sheets', 'external' => 'https://www.jjkellerportal.com/chemicals/sds_employee_access/7hBJ22HyLU6fqyXr6KC5VA'],
        ['label' => 'Incident Reporting & Maint', 'external' => 'https://forms.office.com/r/PCjkgqxCRS'],
    ]],

    ['label' => 'Reports/Lists/Utils', 'icon' => 'fa-table', 'items' => [
        ['label' => "Reg'd Clnts w/o QBO Cust-ID"],
        ['label' => 'Clients That Need QBO Inv'],
        ['label' => 'Fut Sess w/o QBO ClassRef'],
        ['label' => 'Sessions w/o sess-info owner'],
        ['label' => 'Clients w/o client-info owner'],
        ['label' => 'List Stale Employees'],
        ['label' => 'QBO Custs w/ Balance Owed'],
        ['label' => 'Resource Usage'],
        ['label' => 'Clients Need Inv for Priv Sess'],
        ['label' => 'Students that Skipped a Cert'],
        ['label' => 'Self-paced Lecture Results'],
        ['label' => 'Clients On-Hold'],
        ['label' => 'Reg Pay Recs w/ NO QBO Inv'],
        ['label' => 'Database Utility'],
        ['label' => 'Analyze MP Log'],
    ]],

    ['label' => 'Tools', 'icon' => 'fa-wrench', 'items' => [
        ['label' => 'Upload Files to Webmaster'],
        ['label' => 'Edit Homepage News Box'],
        ['label' => 'Generate QR Code'],
    ]],
];
@endphp

<div class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <a href="{{ route('admin.dashboard') }}">CAA Administration</a>
    </div>

    @foreach ($adminMenu as $i => $section)
        <div class="admin-nav-section">
            @if (empty($section['items']))
                {{-- Dashboard-style entry: no submenu, direct link --}}
                <a href="{{ route($section['route']) }}" class="admin-nav-toggle @if(request()->routeIs($section['route'])) is-current @endif">
                    <span class="admin-nav-icon"><i class="fa-solid {{ $section['icon'] }}"></i></span>
                    {{ $section['label'] }}
                </a>
            @else
                <button type="button" class="admin-nav-toggle" aria-expanded="false"
                        onclick="const m=this.nextElementSibling; const open=this.getAttribute('aria-expanded')==='true'; this.setAttribute('aria-expanded', !open); m.style.display = open ? 'none' : 'block';">
                    <span class="admin-nav-icon"><i class="fa-solid {{ $section['icon'] }}"></i></span>
                    {{ $section['label'] }}
                    <span class="admin-nav-arrow">&#9656;</span>
                </button>
                <ul class="admin-submenu" style="display:none;">
                    @foreach ($section['items'] as $item)
                        <li>
                            @if (isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']))
                                <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                            @elseif (isset($item['external']))
                                <a href="{{ $item['external'] }}" target="_blank" rel="noopener">{{ $item['label'] }}</a>
                            @else
                                <span class="admin-nav-disabled" title="Not built yet">{{ $item['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach

    <div class="admin-sidebar-utility">
        <button type="button" onclick="window.location.reload();">
            <span class="admin-nav-icon"><i class="fa-solid fa-rotate"></i></span>
            Refresh Page Contents
        </button>
        <button type="button" onclick="document.querySelectorAll('link[rel=stylesheet]').forEach(l => l.href = l.href.split('?')[0] + '?t=' + Date.now());">
            <span class="admin-nav-icon"><i class="fa-solid fa-rotate"></i></span>
            Refresh Page Styles
        </button>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">
                <span class="admin-nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                Log Out ({{ auth('staff')->user()->name }})
            </button>
        </form>
    </div>

    {{--
        Placeholder per plan -- real activity tracking (last-active
        timestamps for staff/clients/VR students) isn't built yet and
        isn't a current priority. Static dashes rather than fabricated
        numbers, so this doesn't look like real data it isn't.
    --}}
    <div class="admin-sidebar-stats">
        <div><strong>Recently-active CAA Users:</strong> &mdash;</div>
        <div><strong>Recently-active Clients:</strong> &mdash;</div>
        <div><strong>Recently-active VR:</strong> &mdash;</div>
    </div>
</div>
