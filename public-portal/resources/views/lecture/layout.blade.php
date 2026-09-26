<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Online Visible Emissions Training for Smoke School Students')</title>

    {{--
        Michael, 2026-09-06 -- Self-Paced Lecture course shell. Deliberately
        its own, separate layout -- the real, live course itself has no
        shared nav/header/footer at all, a genuinely different visual
        system (Plus Jakarta Sans, not Montserrat), and its own,
        lightweight session-based "signed in as" state rather than
        Laravel's own Auth guard system. Matching that real, deliberate
        separation here rather than forcing it into layouts.app.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1a1a1a;
            background-color: #ffffff;
        }
        a { color: #005da0; }
        .lect-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .lect-header img { height: 48px; }
        .lect-header-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #005da0;
        }
        .lect-signout {
            font-size: 0.85rem;
            color: #b82027;
            font-weight: 700;
            text-decoration: none;
        }
        .lect-body {
            display: flex;
            align-items: flex-start;
        }
        .lect-sidebar {
            width: 260px;
            flex-shrink: 0;
            background-color: #f7f9fb;
            border-right: 1px solid #e5e7eb;
            padding: 1rem 0;
            min-height: calc(100vh - 73px);
        }
        .lect-main {
            flex: 1;
            min-width: 0;
            max-width: 1300px;
            padding: 2rem;
        }
        /* Michael, 2026-09-07 -- centers .lect-main only when it's the
           sole child of .lect-body (no real sidebar section on the
           page, e.g. sign-in) -- pages with a real sidebar (home-base,
           content pages) keep their existing, already-correct
           flush-left layout right next to the sidebar. */
        .lect-main:only-child {
            margin: 0 auto;
        }
        .lect-page-title {
            background-color: #005da0;
            padding: 1rem 1.5rem;
        }
        .lect-page-title h1 {
            margin: 0;
            color: #ffffff;
            font-size: 1.4rem;
        }
        .lect-quiz-progress {
            margin: 0 1rem 1rem;
            padding: 0.75rem;
            background-color: #b82027;
            color: #ffffff;
            border-radius: 0.375rem;
            font-size: 0.85rem;
            line-height: 1.4;
        }
        .lect-quiz-progress a { color: #ffe9ea; }
        .lect-section-list { list-style: none; margin: 0; padding: 0; }
        .lect-section-item { margin-bottom: 0.15rem; }
        .lect-section-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1a1a1a;
            text-decoration: none;
        }
        .lect-section-link:hover { background-color: #eef2f6; }
        .lect-section-link.is-current { background-color: #e1f0ff; color: #005da0; }
        .lect-section-check { width: 1.1rem; text-align: center; flex-shrink: 0; }
        .lect-section-check.is-touched { color: #1a8a3a; }
        .lect-section-check.is-locked { color: #bbbbbb; }
        .lect-page-list { list-style: none; margin: 0 0 0.5rem; padding: 0 0 0 2.25rem; }
        .lect-page-item { margin-bottom: 0.1rem; }
        .lect-page-link {
            display: block;
            padding: 0.3rem 0.5rem;
            font-size: 0.82rem;
            color: #1a1a1a;
            text-decoration: none;
        }
        .lect-page-link:hover { background-color: #eef2f6; }
        .lect-page-link.is-locked { color: #999999; cursor: default; }
        .lect-page-link.is-read::before { content: "\2713"; color: #1a8a3a; margin-right: 0.35rem; }
        .lect-page-link.is-current { background-color: #e1f0ff; color: #005da0; font-weight: 700; }
        .lect-footer {
            background-color: #005da0;
            color: #ffffff;
            text-align: center;
            padding: 0.6rem;
            font-size: 0.8rem;
        }

        /*
            Michael, 2026-09-07 -- shared toggle/collapse component for
            real content pages (Course Assistance, Quiz Structure, and
            future sections). Content lives in the database, not a
            Blade view, so this CSS + the matching lectureToggle() JS
            below live here once, in the shared layout, rather than
            being redefined inside every page's own stored content.
            Same visual convention as the "Why Choose Compliance
            Assurance" page's own toggle sections.
        */
        .lect-toggle { border: 1px solid #e5e7eb; border-radius: 0.375rem; margin-bottom: 1rem; overflow: hidden; }
        .lect-toggle-btn {
            width: 100%; display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem; background-color: #f7f9fb; border: none; text-align: left;
            cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; font-weight: 700;
            color: #005da0;
        }
        .lect-toggle-btn:hover { background-color: #f0f4f8; }
        .lect-toggle-icon { color: #b82027; font-size: 1.25rem; font-weight: 700; line-height: 1; flex-shrink: 0; margin-left: 1rem; }
        .lect-toggle-body { padding: 0.75rem 1.25rem 1.25rem; }

        /*
            Michael, 2026-09-07 -- shared tabs component for Resources
            pages (Glossary, etc.) -- Michael confirmed these should stay
            as real tabs, not be simplified to the toggle pattern above.
            The real, original source uses a jQuery plugin
            (plugins.js) this layout doesn't load at all, so this is a
            small, real, from-scratch implementation matching the same
            visual/behavioral intent: click a tab, show its panel, hide
            the others within that same tab group.
        */
        .lect-tabs { margin-bottom: 1.5rem; }
        .lect-tab-nav { display: flex; gap: 0.25rem; list-style: none; margin: 0; padding: 0; border-bottom: 2px solid #e5e7eb; }
        .lect-tab-nav li { margin: 0; }
        .lect-tab-btn {
            display: inline-block; padding: 0.6rem 1.1rem; background: none; border: none;
            border-bottom: 2px solid transparent; margin-bottom: -2px; cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9rem; font-weight: 700;
            color: #666666;
        }
        .lect-tab-btn.is-active { color: #005da0; border-bottom-color: #005da0; }
        .lect-tab-panel { display: none; padding: 1.25rem 0.25rem; }
        .lect-tab-panel.is-active { display: block; }
    </style>

    @stack('styles')
</head>
<body>

    <div class="lect-header">
        <div style="display:flex; align-items:center; gap:1rem;">
            <img src="/images/home-page/smoke-schools-by-compliance-assurance_100high.png" alt="Compliance Assurance Associates">
            <span class="lect-header-title">Visible Emissions Training</span>
        </div>
        @if (session('lecture_student_id'))
            <div>
                <form method="POST" action="{{ route('lecture.sign-out') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="lect-signout" style="background:none; border:none; padding:0; font-family:inherit; cursor:pointer;">Sign Out</button>
                </form>
            </div>
        @endif
    </div>

    <div class="lect-page-title">
        <h1>@yield('page-title')</h1>
    </div>

    <div class="lect-body">
        @hasSection('sidebar')
            @yield('sidebar')
        @endif

        <main class="lect-main">
            @yield('content')
        </main>
    </div>

    <div class="lect-footer">
        Copyright &copy; {{ date('Y') }} All Rights Reserved by Compliance Assurance Associates, Inc.
    </div>

    <script>
        // Michael, 2026-09-07 -- shared toggle/collapse handler for real
        // content pages (see .lect-toggle CSS above). Content lives in
        // the database, so this is defined once here rather than inside
        // every page's own stored content.
        function lectureToggle(contentId, iconId) {
            const content = document.getElementById(contentId);
            const icon = document.getElementById(iconId);
            const isHidden = content.style.display === 'none';
            content.style.display = isHidden ? 'block' : 'none';
            icon.textContent = isHidden ? '\u2212' : '+';
        }

        // Michael, 2026-09-07 -- shared tabs handler for Resources pages.
        // groupId scopes each click to its own real tab group (e.g. one
        // per glossary term), so clicking a tab in one group never
        // affects any other group on the same page.
        function lectureTab(groupId, tabId) {
            document.querySelectorAll('[data-tab-group="' + groupId + '"]').forEach(function (el) {
                el.classList.remove('is-active');
            });
            document.getElementById(tabId + '-btn').classList.add('is-active');
            document.getElementById(tabId).classList.add('is-active');
        }
    </script>

    @stack('scripts')
</body>
</html>
