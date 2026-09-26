@extends('layouts.app')

@section('title', 'Why Choose Compliance Assurance for Your Smoke School Training - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "Why Choose Compliance Assurance," second
    real sub-page in the About fork. Real, honest departures:
    "digital field certification process" and "VEO-APP" mentions kept
    as plain text, links removed -- digital.php and veo-app.php don't
    exist. The bottom promo bar's CTA maps to our own, real
    account.register instead of the dead become-a-client.php.

    Confirmed with Michael: the eight collapsible toggle sections are
    kept as real, functioning collapse/expand (not flattened to
    always-visible cards like the earlier About/VR pages) -- eight
    full sections shown at once would make this page far too long.
    Same plain vanilla JS as the real, original source (no framework
    dependency at all), just reused directly in this Blade view.

    Nav wiring (app.blade.php) intentionally deferred per Michael --
    batching the whole About-section nav update until all six real
    sub-pages exist.
--}}
@section('content')
    <div class="public-content" style="max-width:1200px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Why Choose Compliance Assurance Associates?</h1>
            <p class="public-page-subhead">We don't tell you the answers &mdash; we teach you to read the answers.</p>
        </div>

        <p style="max-width:720px; margin-bottom:2.5rem;">
            Compliance Assurance (CAA) staff knows the art and the science behind visible emissions
            observations (VEO). We understand what it takes to be certified for VEO, and why it is
            important to understand the concepts behind visible emissions observations. Our opacity
            experts provide <a href="{{ route('public.professional-services') }}">VEO professional services</a>,
            including compliance plans and opacity readings.
        </p>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

            <div style="width:45%; min-width:280px; flex-shrink:0;">
                <a href="/images/what-makes-CAA-unique.jpg" target="_blank" rel="noopener">
                    <img src="/images/what-makes-CAA-unique.jpg" alt="What makes CAA a better smoke school" style="width:100%; border-radius:0.375rem; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                </a>
            </div>

            <div style="flex:1; min-width:320px;">
                <h3 style="color:#b82027;">What Makes CAA a Better Smoke School Provider?</h3>

                <div class="wc-toggle">
                    <button type="button" onclick="wcToggle('online-cert', 'online-cert-icon')" class="wc-toggle-btn">
                        <span>VR Smoke School is 100% Online</span>
                        <span id="online-cert-icon" class="wc-toggle-icon">+</span>
                    </button>
                    <div id="online-cert" class="wc-toggle-body" style="display:none;">
                        <p>
                            The introduction of <a href="{{ route('public.vr-smoke-school') }}">virtual reality (VR) Method 9 training</a>
                            enables field certification to be performed using a VR platform. It's
                            timesaving, efficient, and convenient.
                        </p>
                        <p>Many smoke readers can complete certification in about 15 minutes.</p>
                    </div>
                </div>

                <div class="wc-toggle">
                    <button type="button" onclick="wcToggle('fast-schools', 'fast-schools-icon')" class="wc-toggle-btn">
                        <span>Our In-Person Smoke Schools Are <em>Fast</em></span>
                        <span id="fast-schools-icon" class="wc-toggle-icon">+</span>
                    </button>
                    <div id="fast-schools" class="wc-toggle-body" style="display:none;">
                        <p>
                            The digital field certification process shortens the time students are away
                            from work. Students attend a smoke school and certify on their phone.
                            Everything is digital: smoke school sign-in, test runs, grading, and
                            certification proof.
                        </p>
                        <p>Most smoke schools are completed in one day. Most test runs are completed in under an hour.</p>
                    </div>
                </div>

                <div class="wc-toggle">
                    <button type="button" onclick="wcToggle('lecture', 'lecture-icon')" class="wc-toggle-btn">
                        <span>Self-Paced Lecture Available 24/7</span>
                        <span id="lecture-icon" class="wc-toggle-icon">+</span>
                    </button>
                    <div id="lecture" class="wc-toggle-body" style="display:none;">
                        <p>
                            CAA's self-paced lecture is convenient for students and your organization.
                            Students can take the course at their convenience and at their own pace.
                        </p>
                        <p>The self-paced lecture is TCEQ-approved, has comprehensive resources, and has garnered positive feedback from students.</p>
                    </div>
                </div>

                <div class="wc-toggle">
                    <button type="button" onclick="wcToggle('technology', 'technology-icon')" class="wc-toggle-btn">
                        <span>Technology and Innovation</span>
                        <span id="technology-icon" class="wc-toggle-icon">+</span>
                    </button>
                    <div id="technology" class="wc-toggle-body" style="display:none;">
                        <p>
                            CAA's use of technology for visible emissions training enables your
                            organization to administer employees, enrollments, and maintain a digital
                            library of certifications. CAA's VEO-APP reduces the complexity of Title V
                            requirements and allows for comprehensive records of stacks, facilities,
                            and readings.
                        </p>
                    </div>
                </div>

                <div class="wc-toggle">
                    <button type="button" onclick="wcToggle('digital-records', 'digital-records-icon')" class="wc-toggle-btn">
                        <span>Digital Records Create Defensible Certifications</span>
                        <span id="digital-records-icon" class="wc-toggle-icon">+</span>
                    </button>
                    <div id="digital-records" class="wc-toggle-body" style="display:none;">
                        <ul class="wc-check-list">
                            <li>Field and certification records available online 24/7.</li>
                            <li>Certifications are supported by a digital chain of custody.</li>
                            <li>Digital field records include student test run results, photo ID, and signature.</li>
                            <li>Smoke trailer data stored digitally.</li>
                            <li>Self-paced lecture data available per student, including questions, answers, and pages visited.</li>
                        </ul>
                    </div>
                </div>

                <div class="wc-toggle">
                    <button type="button" onclick="wcToggle('expertise', 'expertise-icon')" class="wc-toggle-btn">
                        <span>Knowledge and Expertise</span>
                        <span id="expertise-icon" class="wc-toggle-icon">+</span>
                    </button>
                    <div id="expertise" class="wc-toggle-body" style="display:none;">
                        <p>
                            With over 60 years of combined experience, CAA personnel know the art and
                            the science behind visible emissions observations (VEO). We understand what
                            it takes to be certified for VEO, and why it is important to
                            <strong>understand the concepts behind the VEO observations.</strong>
                        </p>
                        <p>
                            For two decades, CAA has taught smoke school students the principles behind
                            opacity training, <em>not just how to pass a run.</em> We teach. We train.
                            We never provide hints. By teaching concepts, the certification process is
                            made easy, and the observations definitive and defensible.
                        </p>
                    </div>
                </div>

                <div class="wc-toggle">
                    <button type="button" onclick="wcToggle('student-benefits', 'student-benefits-icon')" class="wc-toggle-btn">
                        <span>In-Person Smoke School Student Benefits</span>
                        <span id="student-benefits-icon" class="wc-toggle-icon">+</span>
                    </button>
                    <div id="student-benefits" class="wc-toggle-body" style="display:none;">
                        <ul class="wc-check-list">
                            <li>Digital technology eliminates the majority of waiting time. Stable smoke, reliable smoke generators, and rapid setup further decrease student wait time.</li>
                            <li>Digital grading provides students with immediate feedback on incorrect answers.</li>
                            <li>An online, self-paced lecture course provides convenience and ensures an understanding of underlying concepts.</li>
                            <li>Field managers (proctors) at every class ensure testing integrity and smooth class operation.</li>
                            <li>Immediate Method 9 certification proof is provided; certifications are available online 24/7.</li>
                            <li>No residuals or dust are left at our smoke school sites.</li>
                        </ul>
                    </div>
                </div>

                <div class="wc-toggle">
                    <button type="button" onclick="wcToggle('manager-benefits', 'manager-benefits-icon')" class="wc-toggle-btn">
                        <span>VEO Manager Benefits</span>
                        <span id="manager-benefits-icon" class="wc-toggle-icon">+</span>
                    </button>
                    <div id="manager-benefits" class="wc-toggle-body" style="display:none;">
                        <ul class="wc-check-list">
                            <li>Virtual smoke school functionality, in-person digital field certification, and the online visible emissions course result in less employee time away from work.</li>
                            <li>Email reminders prevent certification lapse.</li>
                            <li>Comprehensive smoke school data and lecture results are available online.</li>
                            <li>CAA's VEO-APP streamlines Title V requirements, provides digital records of opacity observations, and manages Method 9 certification records and readers.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <div class="caa-promo" style="border-radius:0.375rem; padding:2rem; margin-top:2.5rem; text-align:center;">
            <h2 style="margin-bottom:0.5rem;">Become a Compliance Assurance Client</h2>
            <p style="margin-bottom:1.25rem;">Team with the most innovative and efficient smoke school.</p>
            <a href="{{ route('account.register') }}" class="btn-white">Get Started &raquo;</a>
        </div>
    </div>

    <style>
        .wc-toggle { border: 1px solid #e5e7eb; border-radius: 0.375rem; margin-bottom: 1rem; overflow: hidden; }
        .wc-toggle-btn {
            width: 100%; display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem; background-color: #f7f9fb; border: none; text-align: left;
            cursor: pointer; font-family: 'Montserrat', sans-serif; font-size: 1rem; font-weight: 600;
            color: #005da0;
        }
        .wc-toggle-btn:hover { background-color: #f0f4f8; }
        .wc-toggle-icon { color: #b82027; font-size: 1.25rem; font-weight: 700; line-height: 1; flex-shrink: 0; margin-left: 1rem; }
        .wc-toggle-body { padding: 0.75rem 1.25rem 1.25rem; }
        .wc-check-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; }
        .wc-check-list li { padding-left: 1.5rem; position: relative; }
        .wc-check-list li::before { content: "\2713"; position: absolute; left: 0; color: #b82027; font-weight: 700; }
    </style>

    <script>
        function wcToggle(contentId, iconId) {
            const content = document.getElementById(contentId);
            const icon = document.getElementById(iconId);
            const isHidden = content.style.display === 'none';
            content.style.display = isHidden ? 'block' : 'none';
            icon.textContent = isHidden ? '\u2212' : '+';
        }
    </script>
@endsection
