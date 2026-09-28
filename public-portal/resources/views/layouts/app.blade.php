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
        /* Michael, 2026-09-27 -- page heading colors matched to the live
           site's caa-style-sheet.css (font stays Montserrat by choice):
           titles/subtitles/h4 CAA blue, section headings CAA red. Scoped to
           <main> so the header and footer keep their own styling; inline or
           more specific heading styles (hero, promo bars) still win. */
        main h1, main h2, main h4 { color: #005da0; }
        main h3 { color: #b82027; }
        header.site-header { padding: 1rem 2rem 0.75rem; border-bottom: 1px solid #e5e7eb; }
        header.site-header img.logo { height: 48px; }
        .site-topbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; }
        header.site-header nav { padding-top: 0.75rem; }
        header.site-header nav > a:first-child { margin-left: 0; }
        .site-utility { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; }
        .site-utility .util-icon { display: inline-flex; width: 1.5rem; height: 1.5rem; color: #6b7280; transition: color 0.15s; }
        .site-utility .util-icon svg { width: 100%; height: 100%; }
        .site-utility .util-youtube { width: 1.75rem; height: 1.75rem; }
        .site-utility .util-youtube:hover { color: #ff0000; }
        .site-utility .util-facebook:hover { color: #1877f2; }
        .site-utility .util-linkedin:hover { color: #0a66c2; }
        .site-utility .util-phone:hover { color: #005da0; }
        .site-utility .util-email:hover { color: #b82027; }
        .site-utility .util-divider { width: 1px; height: 1.75rem; background: #e5e7eb; margin: 0 0.25rem; }
        .site-utility .util-btn { display: inline-flex; align-items: center; padding: 0.35rem 1rem; border-radius: 4px; color: #fff; font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-decoration: none; transition: background-color 0.15s; }
        .site-utility .util-btn-blue { background: #005da0; }
        .site-utility .util-btn-blue:hover { background: #004a80; color: #e5e7eb; }
        .site-utility .util-btn-red { background: #b82027; }
        .site-utility .util-btn-red:hover { background: #9a1a20; color: #e5e7eb; }
        .site-utility .util-fieldtest { display: inline-flex; align-items: center; color: #6b7280; font-size: 0.85rem; font-weight: 600; text-decoration: none; }
        .site-utility .util-fieldtest:hover { color: #005da0; }
        .site-utility .util-fieldtest img { width: 1.75rem; height: 1.75rem; }
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
        footer.site-footer { background: #002a4a; margin-top: 3rem; font-size: 13px; line-height: 1.5; color: #aab4c0; }
        .footer-grid { max-width: 1280px; margin: 0 auto; padding: 2rem 1.5rem; display: grid; grid-template-columns: repeat(4, 1fr); gap: 2.5rem; }
        .footer-grid h4 { font-family: inherit; font-size: 13px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #c8d8e8; margin: 0 0 12px; }
        .footer-grid p { margin: 0; }
        .footer-grid .footer-name { color: #fff; font-weight: 600; }
        .footer-grid .footer-gap { margin-bottom: 10px; }
        .footer-grid a { color: #aab4c0; text-decoration: none; }
        .footer-grid a:hover { color: #fff; }
        .footer-newsletter p { margin-bottom: 6px; }
        .footer-newsletter label { display: block; font-size: 12px; margin: 6px 0 4px; }
        .footer-newsletter input[type=email] { width: 100%; box-sizing: border-box; padding: 8px 10px; font-size: 13px; border-radius: 4px; border: 1px solid #0a5080; background: #004a8a; color: #fff; }
        .footer-newsletter input::placeholder { color: #c0ccda; }
        .footer-newsletter button { width: 100%; margin-top: 8px; padding: 9px; font-size: 13px; font-weight: 700; color: #fff; background: #b82027; border: none; border-radius: 4px; cursor: pointer; letter-spacing: 0.05em; text-transform: uppercase; }
        .footer-newsletter button:hover { background: #9a1a20; }
        .footer-bottom { border-top: 1px solid #0a4a7a; padding: 12px 1.5rem; max-width: 1280px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.5rem; font-size: 12px; color: #7aa4c4; }
        .footer-bottom p { margin: 0; }
        .footer-bottom div { display: flex; gap: 20px; }
        .footer-bottom a { color: #7aa4c4; text-decoration: none; }
        .footer-bottom a:hover { color: #fff; }
        @media (max-width: 900px) { .footer-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .footer-grid { grid-template-columns: 1fr; } }
        .status-banner { background:#e8f5e9; border:1px solid #a5d6a7; padding:1rem; border-radius:0.375rem; margin-bottom:1.5rem; color: #2e5e33; }

        /*
            Michael, 2026-08-24 -- shared across account.login (its own
            original source) and every account.* portal page, for visual
            uniformity. Defined once here rather than duplicated across
            each page's own @@push('styles') -- login.blade.php itself is
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
    <div class="site-topbar">
    <a href="{{ route('public.home') }}">
        <img class="logo" src="/images/home-page/smoke-schools-by-compliance-assurance-1.png" alt="Compliance Assurance Associates, Inc."
             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
        <span class="logo-fallback" style="display:none; font-family:'Montserrat',sans-serif; font-weight:700; font-size:1.25rem; color:#1a1a1a;">
            Compliance Assurance Associates, Inc.
        </span>
    </a>
    {{-- Pages that aren't part of the public site (the staff login and
         password pages) opt out of the public menu and utility bar with
         @section('hideNav'). --}}
    @unless (View::hasSection('hideNav'))
        {{-- Michael, 2026-09-27 -- utility bar matching the live site's top
             bar: social/contact icons, LOGIN and CERTS buttons, and the
             field-testing entry point. --}}
        <div class="site-utility">
            <a href="https://www.youtube.com/@EPAmethod9" title="VR Smoke School on YouTube" class="util-icon util-youtube" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1C4.5 20.4 12 20.4 12 20.4s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.7 15.5V8.5l6.3 3.5-6.3 3.5z"/></svg>
            </a>
            <a href="https://www.facebook.com/SmokeSchoolExpert/" title="CAA Smoke School on Facebook" class="util-icon util-facebook" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.93-1.95 1.88v2.27h3.32l-.53 3.49h-2.79V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
            </a>
            <a href="https://www.linkedin.com/company/202842" title="CAA Smoke School on LinkedIn" class="util-icon util-linkedin" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.37V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.59 0 4.26 2.37 4.26 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zm1.78 13.02H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
            </a>
            <a href="tel:901-381-9960" title="Call Compliance Assurance" class="util-icon util-phone">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
            </a>
            <a href="mailto:info@compliance-assurance.com" title="Contact smoke school by email" class="util-icon util-email">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </a>
            <span class="util-divider" aria-hidden="true"></span>
            <a href="{{ route('account.login') }}" title="Login to your account" class="util-btn util-btn-blue">LOGIN</a>
            <a href="{{ route('public.certs.lookup') }}" title="Find/print certification records" class="util-btn util-btn-red">CERTS</a>
            <a href="{{ route('onsite.index') }}" title="Field testing login" class="util-fieldtest">
                <img src="/images/smoke-school-icon.png" alt="Smoke School Field testing"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                <span style="display:none;">Field Testing</span>
            </a>
        </div>
    @endunless
    </div>
    @unless (View::hasSection('hideNav'))
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
            <a href="{{ route('public.new-clients') }}" class="nav-btn nav-btn-red">Prospective Clients</a>
            <div class="nav-dropdown-menu">
                <a href="{{ route('account.register') }}">Create a New Client Account</a>
                <a href="{{ route('public.new-clients') }}">Learn About Becoming a CAA Client</a>
                <a href="{{ route('public.find-a-smoke-school') }}">Find a Smoke School</a>
                <a href="{{ route('public.public-smoke-schools') }}">Learn About Public Smoke Schools</a>
                <a href="{{ route('public.private-smoke-schools') }}">Learn About Private Smoke Schools</a>
            </div>
        </div>

        <div class="nav-dropdown">
            <a href="{{ route('public.existing-clients') }}" class="nav-btn nav-btn-blue">Existing Clients</a>
            <div class="nav-dropdown-menu">
                <a href="{{ route('account.login') }}">Log In</a>
                <a href="{{ route('public.certs.lookup') }}">Find / Print Certification</a>
                <a href="{{ route('public.certs.find-student-number') }}">Retrieve Student Record Number</a>
                <a href="{{ route('public.digital-student') }}">New Student Smoke School Intro</a>
                <a href="{{ route('lecture.sign-in') }}">Lecture Course</a>
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
                <a href="{{ route('public.vr-states') }}" class="nav-sub">↳ ALT-152A State Status Map</a>
                <a href="{{ route('public.vr-smoke-school-states') }}" class="nav-sub">↳ ALT-152A State Implementation List</a>
                <a href="{{ route('public.vr-testimonials') }}" class="nav-sub">↳ Testimonials</a>
                <a href="{{ route('public.in-person-smoke-schools') }}">In-Person Smoke Schools</a>
                <a href="{{ route('public.public-smoke-schools') }}" class="nav-sub">↳ Public Schools</a>
                <a href="{{ route('public.private-smoke-schools') }}" class="nav-sub">↳ Private Schools</a>
                <a href="{{ route('public.calendar') }}">Find In-Person Smoke Schools</a>
                <a href="{{ route('public.map') }}" class="nav-sub">↳ Via Map</a>
                <a href="{{ route('public.list') }}" class="nav-sub">↳ By Location / Date</a>
                <a href="{{ route('public.online-self-paced-lecture') }}">Online Self-Paced Lecture</a>
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

        {{-- Michael, 2026-09-27 -- "Resources" dropdown matching the live
             site; items for pages not built yet appear once their routes
             exist. --}}
        <div class="nav-dropdown">
            <a href="{{ route('public.resources') }}">Resources</a>
            <div class="nav-dropdown-menu">
                @if (Route::has('public.veo-course-summary'))
                    <a href="{{ route('public.veo-course-summary') }}">Online Lecture Course</a>
                @endif
                <a href="{{ route('public.digital-student') }}">New Student Smoke School Intro</a>
                @if (Route::has('public.resources-videos'))
                    <a href="{{ route('public.resources-videos') }}">Video Library</a>
                @endif
                @if (Route::has('public.resources-epa-method-9'))
                    <a href="{{ route('public.resources-epa-method-9') }}">EPA Method 9 and 22 Resources</a>
                @endif
                <a href="{{ route('public.veo-form-instructions') }}">Method 9 Form Instructions</a>
            </div>
        </div>
    </nav>
    @endunless
</header>

<main>
    @if (session('status'))
        <div class="status-banner">
            {{ session('status') }}
        </div>
    @endif

    @yield('content')
</main>

{{-- Michael, 2026-09-27 -- footer matching the live site: company info,
     contacts, Raleigh office, and the Brevo newsletter signup. Departures:
     the SSL.com site seal is left out (it's issued for the live domain's
     certificate, not this one), and the newsletter form posts straight to
     Brevo without loading Brevo's scripts. --}}
<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <h4>Company Information</h4>
            <p class="footer-name">Joseph Spivey</p>
            <p>President</p>
            <p><a href="tel:+1-919-830-7682">Mobile: 919-830-7682</a></p>
            <p class="footer-gap"><a href="mailto:joe.spivey@compliance-assurance.com">Email &raquo;</a></p>
            <p>682 Orvil Smith Road</p>
            <p>Harvest, AL 35749</p>
            <p>Corporate: 901-381-9960</p>
            <p>Hours: 8:00 AM &ndash; 4:00 PM CST</p>
        </div>
        <div>
            <h4>Contacts</h4>
            <p class="footer-name">Derek Mason</p>
            <p>Vice President</p>
            <p>Mobile: <a href="tel:+1-2566039456">256-603-9456</a></p>
            <p class="footer-gap"><a href="mailto:derek.mason@compliance-assurance.com">Email &raquo;</a></p>
            <p class="footer-name">Office &mdash; Chasity Miranda</p>
            <p><a href="tel:+1-901-381-9960">901-381-9960 Ext. 1</a></p>
            <p><a href="mailto:chasity.miranda@compliance-assurance.com">Email &raquo;</a></p>
        </div>
        <div>
            <h4>Raleigh, NC</h4>
            <p class="footer-name">Joseph Spivey</p>
            <p>President</p>
            <p><a href="tel:+1-919-830-7682">Mobile: 919-830-7682</a></p>
            <p><a href="mailto:joe.spivey@compliance-assurance.com">Email &raquo;</a></p>
        </div>
        <div>
            <h4>Newsletter</h4>
            <form class="footer-newsletter" method="POST"
                  action="https://d3588980.sibforms.com/serve/MUIEAH2xu7A8Vor17NFRB_JPMc3Ymr9CgOQFR-X_df7NH3BOqkzDqyrQBfLOa4dt2KKusQzIhrO7DOmov7t1nABXUMfk5HwhQ4geyXiRaNiYbz8lS_F3R2SXXouG0PRtOblPLltpkEC53DjJSUc3RduhKvSthvqrH7tcjczrI3qnS0sWyUZkfZONKrtVT3L9JTYn3NoRDBMgHdOy">
                <p>Subscribe to our newsletter and stay updated.</p>
                <label for="footer-newsletter-email">Enter your email address to subscribe</label>
                <input type="email" id="footer-newsletter-email" name="EMAIL" placeholder="EMAIL" autocomplete="email" required>
                {{-- Brevo's own honeypot field, kept empty and hidden. --}}
                <input type="text" name="email_address_check" value="" style="display:none;" tabindex="-1" autocomplete="off">
                <input type="hidden" name="locale" value="en">
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </div>
    <div class="footer-bottom">
        <p>Copyright &copy; {{ date('Y') }} Compliance Assurance Associates, Inc. All Rights Reserved.</p>
        <div>
            @if (Route::has('public.terms'))
                <a href="{{ route('public.terms') }}">Terms and Conditions &raquo;</a>
            @endif
            @if (Route::has('public.privacy'))
                <a href="{{ route('public.privacy') }}">Privacy Policy &raquo;</a>
            @endif
        </div>
    </div>
</footer>
</body>
</html>
