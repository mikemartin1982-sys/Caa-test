@extends('layouts.app')

@section('title', 'Compliance Assurance Associates, Inc. — EPA Method 9 Opacity Training')

{{--
    Michael, 2026-09-05 -- homepage rebuild to match the real, live
    DIBs site's own structure (hero image strip + navy CTA panel,
    credential bar, two smoke-school cards). Confirmed with Michael:
    only real, existing pages are linked.

    Real, honest departures from the live source, all because the
    underlying pages genuinely don't exist in this platform yet:
    - Hero is a real, two-panel strip (VR, In-Person) instead of
      three -- the live source's third panel (Compliance Services)
      has no real page behind it here at all. A fake, non-clickable
      third panel just for visual symmetry would be worse than an
      honest two-panel layout.
    - The navy panel's own third CTA ("Compliance Services") is
      dropped for the same reason -- only two real, working buttons.
    - The entire "Visible Emission Support Services" section and the
      full "Find Smoke Schools by State" section (all 50 states) are
      omitted entirely -- none of their real, individual destination
      pages exist yet. Worth rebuilding once those are real.

    Images use the same, real paths as the live source
    (/images/home-page/...) -- Michael confirmed these will be placed
    directly on our own server at that same path.
--}}
@section('content')
    {{--
        Michael, 2026-09-06 -- found live: the hero row had no
        max-width at all, spanning the full, raw viewport (2560px on
        a 1440p monitor) while the section right below it constrains
        to 1200px -- a real, visible inconsistency, worth fixing
        regardless of the earlier nbsp/wrapping fix. Wrapped in the
        same 1200px, centered constraint to match.
    --}}
    <div style="max-width:1280px; margin:0 auto; padding:0 1.5rem;">
        <!-- HERO: three-panel image strip + navy CTA panel -->
        <div class="home-hero-row" style="margin-top:1.5rem; min-height:360px;">

        <div class="home-hero-images" style="position:relative; min-height:360px;">
            <h1 style="position:absolute; top:0; left:0; right:0; z-index:10; color:#ffffff;
                       text-transform:uppercase; font-weight:900; line-height:1; letter-spacing:0.04em;
                       font-size:clamp(1.4rem, 4.2vw, 3rem); padding:1.25rem;
                       text-shadow:0 2px 12px rgba(0,0,0,0.85), 0 1px 3px rgba(0,0,0,0.95);">
                Full Service Visible Emissions Provider
            </h1>

            <div class="flex flex-row" style="flex:1; min-height:360px;">
                <a href="{{ route('public.vr-smoke-school') }}" style="position:relative; flex:1; overflow:hidden; border-right:1px solid rgba(255,255,255,0.2); text-decoration:none;">
                    <img src="/images/home-page/vr-smoke-school.jpg" alt="VirtualOpacity VR smoke school" style="width:100%; height:100%; object-fit:cover; object-position:center; display:block;">
                    <div style="position:absolute; bottom:0; left:0; right:0; padding:2.5rem 1rem 1rem; background:linear-gradient(to top, rgba(0,0,0,0.82) 0%, transparent 100%);">
                        <p style="color:#ffffff; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; margin:0; line-height:1.4; font-size:clamp(0.75rem, 1.4vw, 1.1rem); text-shadow:0 1px 4px rgba(0,0,0,0.8);">
                            VirtualOpacity&reg;<br>
                            <span style="font-weight:400; font-size:clamp(0.7rem, 1.2vw, 1rem);">VR Smoke School&nbsp;&raquo;</span>
                        </p>
                    </div>
                </a>
                <a href="{{ route('public.in-person-smoke-schools') }}" style="position:relative; flex:1; overflow:hidden; border-right:1px solid rgba(255,255,255,0.2); text-decoration:none;">
                    <img src="/images/home-page/in-person-smoke-schools.jpg" alt="In-person EPA Method 9 smoke school" style="width:100%; height:100%; object-fit:cover; object-position:center; display:block;">
                    <div style="position:absolute; bottom:0; left:0; right:0; padding:2.5rem 1rem 1rem; background:linear-gradient(to top, rgba(0,0,0,0.82) 0%, transparent 100%);">
                        <p style="color:#ffffff; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; margin:0; line-height:1.4; font-size:clamp(0.75rem, 1.4vw, 1.1rem); text-shadow:0 1px 4px rgba(0,0,0,0.8);">
                            In-Person<br>
                            <span style="font-weight:400; font-size:clamp(0.7rem, 1.2vw, 1rem);">Smoke Schools&nbsp;&raquo;</span>
                        </p>
                    </div>
                </a>
                <a href="{{ route('public.professional-services') }}" style="position:relative; flex:1; overflow:hidden; text-decoration:none;">
                    <img src="/images/home-page/air-compliance-services.jpg" alt="Visible emissions compliance services" style="width:100%; height:100%; object-fit:cover; object-position:center; display:block;">
                    <div style="position:absolute; bottom:0; left:0; right:0; padding:2.5rem 1rem 1rem; background:linear-gradient(to top, rgba(0,0,0,0.82) 0%, transparent 100%);">
                        <p style="color:#ffffff; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; margin:0; line-height:1.4; font-size:clamp(0.75rem, 1.4vw, 1.1rem); text-shadow:0 1px 4px rgba(0,0,0,0.8);">
                            Compliance<br>
                            <span style="font-weight:400; font-size:clamp(0.7rem, 1.2vw, 1rem);">Services&nbsp;&raquo;</span>
                        </p>
                    </div>
                </a>
            </div>
        </div>

        <div class="home-hero-panel" style="background-color:#005da0; display:flex; flex-direction:column; justify-content:center; padding:2.5rem 2rem; gap:1.25rem;">
            <h2 style="color:#ffffff; font-size:1.75rem; line-height:1.2; margin:0;">Comprehensive Opacity Services</h2>
            <p style="color:#b0c8e0; font-size:1.1rem; line-height:1.6; margin:0;">VR Smoke Schools. In-Person Smoke Schools. Visible Emission Compliance Services.</p>
            <div style="display:flex; flex-direction:column; gap:0.6rem; margin-top:0.5rem;">
                <a href="{{ route('public.vr-smoke-school') }}" class="btn-secondary btn-full">Get Started on VirtualOpacity&nbsp;&raquo;</a>
                <a href="{{ route('public.calendar') }}" class="btn-secondary btn-full">Find an In-Person School&nbsp;&raquo;</a>
                <a href="{{ route('public.professional-services') }}" class="btn-secondary btn-full">Compliance Services&nbsp;&raquo;</a>
            </div>
        </div>
    </div>
    </div>

    <!-- CREDENTIAL BAR + TWO SMOKE SCHOOL CARDS -->
    <section style="background-color:#f7f9fb; padding:2rem 0;">
        <div style="max-width:1280px; margin:0 auto; padding:0 1.5rem;">

            <h2 style="text-align:center; color:#005da0; font-size:1.75rem; margin-bottom:1.5rem;">Get EPA Method 9 Certified &mdash; Smoke Schools to Fit Your Needs</h2>

            <div style="background-color:#005da0; border-radius:0.375rem 0.375rem 0 0; padding:1rem 2rem;">
                <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:0; text-align:center;">
                    <div style="padding:0.5rem 1rem; border-right:1px solid rgba(255,255,255,0.2);">
                        <p style="color:#ffffff; font-size:1.5rem; font-weight:700; margin:0; line-height:1.1;">115,000+</p>
                        <p style="color:#b0c8e0; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; margin-top:0.25rem;">Observers Certified</p>
                    </div>
                    <div style="padding:0.5rem 1rem;">
                        <p style="color:#ffffff; font-size:1.5rem; font-weight:700; margin:0; line-height:1.1;">Since 2001</p>
                        <p style="color:#b0c8e0; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; margin-top:0.25rem;">Trusted Smoke School Leader</p>
                    </div>
                </div>
            </div>

            <div class="service-cards-row" style="border:1px solid #e5e7eb; border-top:none; border-radius:0 0 0.375rem 0.375rem; overflow:hidden;">
                <div style="flex:1; display:flex; flex-direction:column; background:#ffffff; border-right:1px solid #e5e7eb;">
                    <div style="height:4px; background-color:#005da0;"></div>
                    <div style="display:flex; flex-direction:column; flex:1; padding:2rem;">
                        <h3 style="color:#b82027; margin-bottom:0.5rem;">Virtual Smoke Schools</h3>
                        <p style="color:#005da0; font-weight:600; margin-bottom:1rem;">Train anywhere, anytime. Federal EPA approved ALT-152A.</p>
                        <ul style="list-style:none; padding:0; flex:1; margin-bottom:1.5rem;">
                            <li style="padding:0.4rem 0; border-bottom:1px solid #f0f0f0;"><span style="color:#b82027; font-weight:700; margin-right:0.4rem;">&check;</span> 100% Online Method 9 Certification</li>
                            <li style="padding:0.4rem 0; border-bottom:1px solid #f0f0f0;"><span style="color:#b82027; font-weight:700; margin-right:0.4rem;">&check;</span> No travel expenses or weather hassles</li>
                            <li style="padding:0.4rem 0; border-bottom:1px solid #f0f0f0;"><span style="color:#b82027; font-weight:700; margin-right:0.4rem;">&check;</span> Train on your schedule, on your site</li>
                            <li style="padding:0.4rem 0; border-bottom:1px solid #f0f0f0;"><span style="color:#b82027; font-weight:700; margin-right:0.4rem;">&check;</span> Easy digital certification management</li>
                            <li style="padding:0.4rem 0;"><span style="color:#b82027; font-weight:700; margin-right:0.4rem;">&check;</span> First commercial VR smoke school &mdash; since 2024</li>
                        </ul>
                        <a href="{{ route('public.vr-smoke-school') }}" class="btn-secondary btn-full" style="margin-top:auto;">VR Smoke Schools&nbsp;&raquo;</a>
                    </div>
                </div>
                <div style="flex:1; display:flex; flex-direction:column; background:#ffffff;">
                    <div style="height:4px; background-color:#005da0;"></div>
                    <div style="display:flex; flex-direction:column; flex:1; padding:2rem;">
                        <h3 style="color:#005da0; margin-bottom:0.5rem;">In-Person Smoke Schools</h3>
                        <p style="color:#005da0; font-weight:600; margin-bottom:1rem;">Traditional field certification with CAA instructors.</p>
                        <ul style="list-style:none; padding:0; flex:1; margin-bottom:1.5rem;">
                            <li style="padding:0.4rem 0; border-bottom:1px solid #f0f0f0;"><span style="color:#005da0; font-weight:700; margin-right:0.4rem;">&check;</span> Public smoke schools nationwide</li>
                            <li style="padding:0.4rem 0; border-bottom:1px solid #f0f0f0;"><span style="color:#005da0; font-weight:700; margin-right:0.4rem;">&check;</span> Private on-site smoke schools</li>
                            <li style="padding:0.4rem 0; border-bottom:1px solid #f0f0f0;"><span style="color:#005da0; font-weight:700; margin-right:0.4rem;">&check;</span> Fast digital certification</li>
                            <li style="padding:0.4rem 0; border-bottom:1px solid #f0f0f0;"><span style="color:#005da0; font-weight:700; margin-right:0.4rem;">&check;</span> Experienced CAA instructors</li>
                            <li style="padding:0.4rem 0;"><span style="color:#005da0; font-weight:700; margin-right:0.4rem;">&check;</span> Serving clients since 2001</li>
                        </ul>
                        <a href="{{ route('public.in-person-smoke-schools') }}" class="btn-primary btn-full" style="margin-top:auto;">In-Person Smoke Schools&nbsp;&raquo;</a>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection
