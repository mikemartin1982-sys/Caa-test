<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Compliance Assurance Associates, Inc.')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/caa-brand.css">
    <style>
        body { font-family: -apple-system, sans-serif; margin: 0; }
        h1, h2, h3 { font-family: 'Montserrat', sans-serif; color: #1a1a1a; }
        header.site-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; border-bottom: 1px solid #e5e7eb; }
        header.site-header img.logo { height: 48px; }
        nav a { margin-left: 1.75rem; font-weight: 600; font-size: 0.95rem; }
        nav a.nav-login { color: #b82027; }

        /*
            Michael, 2026-09-05 -- Smoke School Discovery nav
            consolidation. Confirmed with Michael: all four
            informational pages (VR, In-Person, Public, Private) plus
            the existing Calendar/Map links, grouped into one real
            dropdown, rather than one top-level entry per page -- lets
            a visitor drill down directly to Public or Private without
            first landing on the In-Person hub page. A real, simple,
            hover-based dropdown -- this nav had no dropdown mechanism
            at all before this.
        */
        nav .nav-dropdown { position: relative; display: inline-block; }
        nav .nav-dropdown > a { margin-left: 1.75rem; }
        nav .nav-dropdown-menu {
            display: none; position: absolute; top: 100%; left: 0;
            background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.375rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); min-width: 240px;
            padding: 0.4rem 0; z-index: 50;
        }
        nav .nav-dropdown:hover .nav-dropdown-menu { display: block; }

        /*
            Michael, 2026-09-05 -- found live: the "Smoke Schools"
            dropdown, being the last item in the nav, had its flyout
            (left-aligned to its trigger, matching every other
            dropdown) pushed past the real viewport's right edge and
            visibly cut off. Right-aligning just this one, last
            dropdown's own flyout fixes it without changing the
            left-aligned behavior of the other two, which sit fine
            within the viewport as-is.
        */
        nav .nav-dropdown:last-of-type .nav-dropdown-menu { left: auto; right: 0; }
        nav .nav-dropdown-menu a {
            display: block; margin-left: 0; padding: 0.5rem 1rem;
            font-weight: 500; font-size: 0.9rem; color: #1a1a1a; white-space: nowrap;
        }
        nav .nav-dropdown-menu a:hover { background: #f7f9fb; color: #005da0; }
        nav .nav-dropdown-menu a.nav-sub { padding-left: 1.75rem; color: #666666; font-size: 0.85rem; }

        /*
            Michael, 2026-09-05 -- Prospective/Existing Clients, real
            colored-button dropdowns matching the live DIBs site's own
            intentional visual hierarchy -- these two are genuine,
            primary audience-based CTAs, distinct from the plain-text
            informational links elsewhere in the nav.
        */
        nav .nav-btn {
            display: inline-flex; align-items: center; margin-left: 1.75rem;
            padding: 0.5rem 1rem; border-radius: 0.375rem; color: #ffffff !important;
            font-weight: 700; font-size: 0.9rem;
        }
        nav .nav-btn-red { background-color: #b82027; }
        nav .nav-btn-red:hover { background-color: #9a1a20; color: #ffffff !important; }
        nav .nav-btn-blue { background-color: #005da0; }
        nav .nav-btn-blue:hover { background-color: #004a80; color: #ffffff !important; }

        /*
            Michael, 2026-09-05 -- homepage rebuild. Plain, real CSS
            (not Tailwind utility classes -- this stylesheet
            deliberately doesn't pull in Tailwind, per its own header
            comment) for the hero's responsive column layout and the
            two smoke-school cards row, matching the live source's own
            "stack on mobile, side-by-side on desktop" behavior.
        */
        .home-hero-row { display: flex; flex-direction: column; }
        .home-hero-images { display: flex; flex-direction: column; width: 100%; }
        .home-hero-images > div { display: flex; flex-direction: row; flex: 1; }
        .service-cards-row { display: flex; flex-direction: column; }
        @media (min-width: 1024px) {
            .home-hero-row { flex-direction: row; }
            .home-hero-images { width: 70%; }
            .home-hero-panel { width: 30%; }
        }
        @media (min-width: 768px) {
            .service-cards-row { flex-direction: row; }
        }
        nav a.nav-login:hover { color: #9a1a20; }
        main { padding: 2rem; max-width: 1100px; margin: 0 auto; }
        footer.site-footer { padding: 2rem; text-align: center; color: #666; font-size: 0.85rem; border-top: 1px solid #e5e7eb; margin-top: 3rem; }
        .status-banner { background:#e8f5e9; border:1px solid #a5d6a7; padding:1rem; border-radius:0.375rem; margin-bottom:1.5rem; color: #2e5e33; }

        /*
            Michael, 2026-08-24 -- shared across account.login (its own
            original source) and every account.* portal page, for visual
            uniformity. Defined once here rather than duplicated across
            each page's own @push('styles') -- login.blade.php itself is
            untouched and keeps its own local copy, which is harmless
            duplication (same selector, same rule), not a conflict.
        */
        .reg-wrap { max-width: 420px; margin: 0 auto; }
        .reg-wrap-wide { max-width: 100%; margin: 0 auto; }
        .reg-header { text-align: center; margin-bottom: 2rem; }
        .reg-header h1 { margin-bottom: 0.4rem; }
        .reg-header p { color: #666666; font-size: 1rem; }

        .reg-card {
            background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;
            padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .reg-errors {
            background: #fdeceb; border: 1px solid #f2b8b5; color: #b82027;
            padding: 1rem 1.25rem; border-radius: 0.375rem; margin-bottom: 1.5rem;
        }
        .reg-errors p { margin: 0; color: #b82027; }
        .reg-errors p + p { margin-top: 0.35rem; }

        .reg-field { margin-bottom: 1.1rem; }
        .reg-field label {
            display: block; font-size: 0.85rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.35rem;
        }
        .reg-field input {
            width: 100%; padding: 0.65rem 0.8rem; border: 1px solid #d1d5db; border-radius: 0.375rem;
            font-size: 0.95rem; color: #1a1a1a; transition: border-color 0.15s, box-shadow 0.15s;
        }
        .reg-field input:focus {
            outline: none; border-color: #005da0; box-shadow: 0 0 0 3px rgba(0,93,160,0.12);
        }

        .reg-footer-link { text-align: center; margin-top: 1.5rem; color: #666666; }

        /* Shared account.* sub-nav row, matching the same reg-card visual language. */
        .reg-subnav {
            background: #f7f9fb; border: 1px solid #e5e7eb; border-radius: 0.5rem;
            padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; text-align: center; font-size: 0.92rem;
        }
        .reg-subnav a { font-weight: 600; margin: 0 0.6rem; }
        .reg-subnav .reg-subnav-id { color: #666666; margin-left: 0.6rem; }
    </style>
    @stack('styles')
</head>
<body>
<header class="site-header">
    <a href="{{ route('public.home') }}">
        <img class="logo" src="/images/home-page/smoke-schools-by-compliance-assurance-1.png" alt="Compliance Assurance Associates, Inc."
             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
        <span class="logo-fallback" style="display:none; font-family:'Montserrat',sans-serif; font-weight:700; font-size:1.25rem; color:#1a1a1a;">
            Compliance Assurance Associates, Inc.
        </span>
    </a>
    <nav>
        <a href="{{ route('public.home') }}">Home</a>

        {{--
            Michael, 2026-09-05 -- full nav rebuild to match the real,
            live DIBs site's own structure. Confirmed with Michael:
            only real, existing pages are linked -- the rest of DIBs'
            own nav (About, VEO App, VEO Services, Resources, Blog,
            most Prospective/Existing Clients sub-items) genuinely
            doesn't exist in this platform yet, so it's omitted
            entirely rather than shown broken or struck-through --
            that convention is right for an internal staff tool, but
            not for a real, public-facing marketing page. Prospective/
            Existing Clients kept as their own, real colored-button
            dropdowns (red/blue), matching the real, intentional visual
            hierarchy already established in the live source -- these
            two are genuine, primary audience-based CTAs, distinct from
            plain informational links.
        --}}
        <div class="nav-dropdown">
            <a href="{{ route('account.register') }}" class="nav-btn nav-btn-red">Prospective Clients</a>
            <div class="nav-dropdown-menu">
                <a href="{{ route('account.register') }}">Request a New Client Account</a>
                <a href="{{ route('public.new-clients') }}">Learn About Becoming a CAA Client</a>
                <a href="{{ route('public.calendar') }}">Find a Smoke School</a>
                <a href="{{ route('public.public-smoke-schools') }}">Learn About Public Smoke Schools</a>
                <a href="{{ route('public.private-smoke-schools') }}">Learn About Private Smoke Schools</a>
            </div>
        </div>

        <div class="nav-dropdown">
            <a href="{{ route('account.login') }}" class="nav-btn nav-btn-blue">Existing Clients</a>
            <div class="nav-dropdown-menu">
                <a href="{{ route('account.login') }}">Log In</a>
                <a href="{{ route('public.certs.lookup') }}">Find / Print Certification</a>
                <a href="{{ route('public.certs.find-student-number') }}">Retrieve Student Record Number</a>
                <a href="{{ route('public.digital-student') }}">New Student Smoke School Intro</a>
            </div>
        </div>

        {{-- Michael, 2026-09-06 -- "About" section closed out -- five of
             its six real sub-pages exist. "Jobs" is deliberately omitted
             here, matching the same pattern used throughout this nav
             (only real, working links shown in dropdown menus) -- it's
             a real, deferred decision for management to make later, not
             a temporary gap, and stays visible as a greyed card on the
             hub page itself. --}}
        <div class="nav-dropdown">
            <a href="{{ route('public.about-menu') }}">About</a>
            <div class="nav-dropdown-menu">
                <a href="{{ route('public.about') }}">About Compliance Assurance</a>
                <a href="{{ route('public.why-choose-compliance') }}">Why Choose Compliance Assurance?</a>
                <a href="{{ route('public.faqs') }}">FAQs</a>
                <a href="{{ route('public.about-team') }}">Meet Our Team</a>
                <a href="{{ route('public.our-customers') }}">Our Clients</a>
            </div>
        </div>

        {{-- Michael, 2026-09-05 -- Smoke School Discovery nav consolidation --
             all four informational pages plus the existing Calendar/Map links,
             grouped into one real dropdown. --}}
        <div class="nav-dropdown">
            <a href="{{ route('public.in-person-smoke-schools') }}">Smoke Schools</a>
            <div class="nav-dropdown-menu">
                <a href="{{ route('public.vr-smoke-school') }}">VR Smoke School</a>
                <a href="{{ route('public.in-person-smoke-schools') }}">In-Person Smoke Schools</a>
                <a href="{{ route('public.public-smoke-schools') }}" class="nav-sub">↳ Public Schools</a>
                <a href="{{ route('public.private-smoke-schools') }}" class="nav-sub">↳ Private Schools</a>
                <a href="{{ route('public.calendar') }}">Find In-Person Smoke Schools</a>
                <a href="{{ route('public.map') }}" class="nav-sub">↳ Via Map</a>
            </div>
        </div>

        {{-- Michael, 2026-09-06 -- "VEO Services" dropdown complete --
             all four real sub-items now exist. --}}
        <div class="nav-dropdown">
            <a href="{{ route('public.professional-services') }}">VEO Services</a>
            <div class="nav-dropdown-menu">
                <a href="{{ route('public.veo-services-compliance-plans') }}">Air Quality Compliance Plans</a>
                <a href="{{ route('public.veo-expertise') }}">VEO Law-Related Services</a>
                <a href="{{ route('public.veo-form-instructions') }}">Method 9 Form Instructions</a>
                <a href="{{ route('public.veo-readings') }}">VEO Readings</a>
            </div>
        </div>
    </nav>
</header>

<main>
    @if (session('status'))
        <div class="status-banner">
            {{ session('status') }}
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    &copy; {{ date('Y') }} Compliance Assurance Associates, Inc. &middot; 682 Orvil Smith Rd. Harvest, AL 35749
</footer>
</body>
</html>
