@extends('layouts.app')

@section('title', 'Method 9 Training Using Virtual Reality - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-05 -- Smoke School Discovery pages, first of a
    planned series (VR, In-Person, Public, Private). Confirmed with
    Michael: build each page individually first, THEN consolidate into
    a new, real nav layout once every page actually exists -- avoids
    rebuilding the nav repeatedly as each new page gets added.

    Content kept from the real, live DIBs page (vr-client-info.php):
    intro copy, Advantages/Requirements detail, pricing, and the
    Resources links. The original page's own "Request Access to
    VirtualOpacity" form and its legacy MD5-captcha JS are deliberately
    NOT rebuilt -- confirmed with Michael: a good enough explanation of
    VR and its requirements here makes a dedicated form unnecessary,
    since the existing "Become a Client" self-service flow already
    covers real account creation.

    Sub-pages this page originally linked to (testimonials, ALT-152A
    state status) are confirmed with Michael as NOT needed yet --
    their own, separate, later items.

    Resource PDFs (EPA approval letter, FAQ, etc.) still point to the
    real, live compliance-assurance.com files for now -- we don't have
    our own copies yet. Worth revisiting once real copies exist on our
    own server, rather than depending on the site being replaced.
--}}
@section('content')
    <div class="public-content" style="max-width:900px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Virtual Smoke School Training</h1>
            <p class="public-page-subhead">The future of Method 9 training is here</p>
        </div>

        <div style="position:relative; width:100%; padding-top:56.25%; margin-bottom:1.5rem;">
            <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                    src="https://www.youtube.com/embed/4BrAL2NmzHE"
                    title="VirtualOpacity VR Smoke School"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen></iframe>
        </div>

        <p>
            In August 2024, the Environmental Protection Agency (EPA) approved the Compliance Assurance
            Associates, Inc. (CAA) VirtualOpacity&reg; platform for use in Method 9 visible emissions
            observations (VEO) certification. VirtualOpacity&reg; allows Method 9 candidates to use a
            virtual reality (VR) platform to certify for EPA Method 9 readings.
        </p>
        <p>
            Our VR smoke school eliminates scheduling and staffing overhead for environmental managers
            while giving students the flexibility to certify on their own schedule.
        </p>

        <div class="public-card">
            <h3>Advantages of VirtualOpacity Training</h3>
            <ul>
                <li>Flexible scheduling for your organization.</li>
                <li>Eliminates expenses incurred by travel and employee coverage.</li>
                <li>VR smoke school students learn at their own pace and have unlimited practice time.</li>
                <li>Environmentally friendly &mdash; no travel, no smoke generation.</li>
                <li>Candidates can be certified any time of the year, in any weather.</li>
            </ul>
        </div>

        <div class="public-card">
            <h3>Requirements</h3>
            <ul>
                <li>Clients purchase the required model VR headset(s).</li>
                <li>A Method 9 lecture course is required for candidates without a current lecture certificate.</li>
                <li>Payment arrangements are required before enrollment.</li>
            </ul>
        </div>

        <p><strong>Pricing:</strong> $250 per enrollment. &nbsp;&nbsp;<strong>Lecture course:</strong> $50 per enrollment.</p>

        <div class="caa-promo" style="border-radius:0.375rem; padding:2rem; margin:2rem 0; text-align:center;">
            <h2 style="margin-bottom:0.5rem;">Ready to Get Started with VirtualOpacity?</h2>
            <p style="margin-bottom:1.25rem;">Create your company account to enroll employees, track certifications, and manage everything from one place.</p>
            <a href="{{ route('account.register') }}" class="btn-white">Become a Client &raquo;</a>
        </div>

        <div style="border-top:1px solid #e5e7eb; padding-top:1.25rem;">
            <p class="hint" style="text-transform:uppercase; letter-spacing:0.05em; font-weight:700; margin-bottom:0.75rem;">Resources</p>
            <ul>
                <li><a href="https://www.govinfo.gov/content/pkg/FR-2024-01-25/pdf/2024-01495.pdf" target="_blank" rel="noopener">EPA Method 9 ALT-152A document &raquo;</a></li>
                <li><a href="https://compliance-assurance.com/PDFs/EPA-Approval-Letter-CAA-ALT-152.pdf" target="_blank" rel="noopener">CAA Approval Document &raquo;</a></li>
                <li><a href="https://compliance-assurance.com/PDFs/129036XX-VR-FAQ.pdf" target="_blank" rel="noopener">VirtualOpacity FAQs &raquo;</a></li>
            </ul>
        </div>
    </div>
@endsection
