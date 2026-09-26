@extends('layouts.app')

@section('title', 'FAQs About Compliance Assurance Smoke Schools - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "FAQs," third real sub-page in the About
    fork. Real, honest departures: several links in the real, original
    source point out to the live compliance-assurance.com with
    absolute URLs (training-map.php, certs.php, certs-email-id.php,
    digital-student.php) -- unlike every other page built so far,
    which used relative links throughout. Since we already have real,
    working equivalents for all four, routed to our own
    public.map / public.certs.lookup / public.certs.find-student-number
    / public.digital-student instead of linking out externally.
    "resources-videos.php#inperson" has no real equivalent here at
    all yet (the Resources dropdown doesn't exist) -- kept as plain
    text, link removed. No collapse/toggle in the real, original
    source for this page -- all seven Q&As are always visible, so
    built the same way here.

    Nav wiring (app.blade.php) intentionally deferred per Michael --
    batching the whole About-section nav update until all six real
    sub-pages exist.
--}}
@section('content')
    <div class="public-content" style="max-width:1200px; margin:0 auto; padding:2rem; background-color:#f7f9fb;">
        <div class="public-page-header">
            <h1>CAA Smoke Schools FAQs</h1>
            <p class="public-page-subhead">Commonly asked questions about CAA smoke schools</p>
        </div>

        <div style="display:flex; gap:2rem; flex-wrap:wrap; margin-bottom:1.25rem;">

            <div style="width:260px; flex-shrink:0;">
                <img src="/images/faqs-about-smoke-schools.jpg" alt="Frequently asked questions about CAA opacity schools" style="width:100%; border-radius:0.375rem; border:1px solid #e5e7eb;">
                <p style="font-size:0.8rem; margin-top:0.75rem;">
                    If you still have questions after reading our FAQs, please call CAA at
                    <a href="tel:+19013819960">901-381-9960</a>.
                </p>
            </div>

            <div class="public-card" style="flex:1; min-width:320px; margin-bottom:0;">
                <h3>1. Does CAA offer a VR smoke school?</h3>
                <p>
                    Yes! CAA offers <a href="{{ route('public.vr-smoke-school') }}">VirtualOpacity&reg;</a>,
                    an EPA-approved virtual reality (VR) smoke school that allows Method 9 candidates to
                    complete their entire certification training online. VirtualOpacity&reg; is approved
                    under EPA Alternative Method ALT-152A, meaning certifications earned through the
                    platform are fully recognized for compliance purposes.
                </p>
                <p>
                    Students can train at their own pace, on their own schedule, with no travel required.
                    All you need is a supported Meta Quest VR headset. Visit our
                    <a href="{{ route('public.vr-smoke-school') }}">VirtualOpacity&reg; page</a> to learn
                    more or get started.
                </p>
            </div>

        </div>

        <div style="display:flex; flex-direction:column; gap:1.25rem;">

            <div class="public-card" style="margin-bottom:0;">
                <h3>2. Does CAA offer an in-person smoke school?</h3>
                <p>
                    To find an in-person smoke school near you, visit
                    <a href="{{ route('public.map') }}">our training map page</a>. This will display a
                    map of the United States, click on the state you wish to attend a school to bring up
                    a list of schools for that state.
                </p>
                <p>
                    If you don't see an in-person smoke school in your area, CAA will consider
                    establishing a public or private smoke school in your area or at your facility.
                    Please <a href="mailto:client@compliance-assurance.com?Subject=Interest in Smoke school Location">email CAA</a>
                    to request a smoke school in your area or at your facility.
                </p>
                <p>
                    Or, try CAA's <a href="{{ route('public.vr-smoke-school') }}">VirtualOpacity&reg; VR Smoke School</a>
                    &mdash; you can complete your entire Method 9 training online.
                </p>
            </div>

            <div class="public-card" style="margin-bottom:0;">
                <h3>3. How do I enroll for smoke school?</h3>
                <p>Smoke school enrollment can be completed in the following ways:</p>
                <ul>
                    <li>You or your company's compliance manager can log in to your existing CAA account and enroll your employee(s) via the website.</li>
                    <li>If you would like to become a customer, use our <a href="{{ route('account.register') }}">new client request page</a>.</li>
                    <li>If you encounter problems creating a client account or with self-enrollment, please call <a href="tel:+19013819960">901-381-9960</a> for assistance.</li>
                </ul>
            </div>

            <div class="public-card" style="margin-bottom:0;">
                <h3>4. How can I get proof of my certification?</h3>
                <p>
                    If you are logged into our client portal, use the link under
                    <strong>Existing Clients &mdash; Find/Print Certification</strong>. You will need to
                    know your employee number.
                </p>
                <p>
                    If you are a student, visit <a href="{{ route('public.certs.lookup') }}">our certification page</a>.
                </p>
            </div>

            <div class="public-card" style="margin-bottom:0;">
                <h3>5. How can I find my employee number?</h3>
                <p>
                    Visit <a href="{{ route('public.certs.find-student-number') }}">our student number retrieval page</a>
                    to look up your employee number.
                </p>
            </div>

            <div class="public-card" style="margin-bottom:0;">
                <h3>6. Do you guarantee a Method 9 certification?</h3>
                <p>
                    Our smoke schools cannot guarantee an EPA Method 9 certification, but we will work
                    with you to help you achieve certification. If a student attempts a certification
                    test and fails to certify, CAA does not refund the class cost.
                </p>
            </div>

            <div class="public-card" style="margin-bottom:0;">
                <h3>7. What should I expect if I have never attended an in-person smoke school?</h3>
                <p>
                    General information on what you need to know before attending a CAA in-person smoke
                    school can be found <a href="{{ route('public.digital-student') }}">here &raquo;</a>
                </p>
                <p>You can learn about our digital field certification process here.</p>
            </div>

        </div>
    </div>
@endsection
