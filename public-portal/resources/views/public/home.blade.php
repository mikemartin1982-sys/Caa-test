@extends('layouts.app')

@section('title', 'Compliance Assurance Associates, Inc. — EPA Method 9 Opacity Training')

{{--
    Michael, 2026-09-05 -- homepage rebuild to match the real, live
    DIBs site's own structure (hero image strip + navy CTA panel,
    credential bar, two smoke-school cards). Confirmed with Michael:
    only real, existing pages are linked.

    Michael, 2026-09-27 -- the rest of the live home page added now that
    its destination pages exist: all four credential-bar stats, the
    "Visible Emission Support Services" section, and "Find Smoke Schools
    by State". Departure from the live source: the live state links go
    to per-state landing pages (smoke-school/Texas/) that don't exist
    here, so each opens the Training Map with that state selected
    (training-map?state=TX). The live section's faint trailer background
    photo (Method9-smoke-school-trailer.jpg) isn't available, so that
    section uses the plain light-grey background.

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
                <div class="home-stats">
                    @foreach ([['115,000+', 'Observers Certified'], ['Since 2001', 'Trusted Smoke School Leader'], ['ALT-152A', 'VR Smoke School'], ['100% Digital', 'Method 9 Training Records']] as [$stat, $label])
                        <div>
                            <p style="color:#ffffff; font-size:1.5rem; font-weight:700; margin:0; line-height:1.1;">{{ $stat }}</p>
                            <p style="color:#b0c8e0; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; margin-top:0.25rem;">{{ $label }}</p>
                        </div>
                    @endforeach
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

    <!-- VISIBLE EMISSION SUPPORT SERVICES -->
    <section style="background:#ffffff; padding:2rem 0;">
        <div style="max-width:1280px; margin:0 auto; padding:0 1.5rem;">
            <h2 style="text-align:center; color:#005da0; font-size:1.75rem; margin-bottom:1.5rem;">Visible Emission Support Services</h2>
            <p style="font-size:1.05rem; color:#444; line-height:1.7; margin-bottom:1.5rem;">
                Our team has over 100 years of combined experience in visible emissions observations (VEO).
                Whether you need expert testimony, compliance planning, or field opacity readings &mdash;
                CAA has you covered.
            </p>
            <div class="home-support-row">
                <div style="flex:1;">
                    @foreach ([
                        ['public.veo-expertise', 'Air Quality Notice of Violations', 'Expert VEO support for NOV response and air quality disputes.', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['public.veo-services-compliance-plans', 'Clean Air Compliance Plans', 'Custom compliance monitoring and planning for your facility.', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['public.veo-readings', 'Opacity Readings', 'Professional Method 9 and Method 22 field opacity readings.', 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
                    ] as [$route, $title, $blurb, $icon])
                        <a href="{{ route($route) }}" class="home-service-card">
                            <span class="home-service-icon">
                                <svg width="20" height="20" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            </span>
                            <span>
                                <strong style="display:block; color:#005da0; font-size:1rem; margin-bottom:0.2rem;">{{ $title }}</strong>
                                <span style="color:#666; font-size:0.9rem;">{{ $blurb }}</span>
                            </span>
                        </a>
                    @endforeach
                    <a href="{{ route('public.professional-services') }}" class="btn-primary" style="display:inline-block; margin-top:0.75rem;">All VEO Services&nbsp;&raquo;</a>
                </div>
                <a href="{{ route('public.professional-services') }}" style="flex:1; border-radius:0.5rem; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.12);">
                    <img src="/images/home-page/visible-emissions-support-services.jpg" alt="Air quality visible emissions support services" style="width:100%; height:auto; display:block;">
                </a>
            </div>
        </div>
    </section>

    <!-- FIND SMOKE SCHOOLS BY STATE -- each state opens the Training Map with that state selected -->
    <section style="background-color:#f7f9fb; padding:2rem 0;">
        <div style="max-width:1280px; margin:0 auto; padding:0 1.5rem;">
            <h2 style="text-align:center; color:#005da0; font-size:1.75rem; margin-bottom:1.5rem;">Find Smoke Schools by State</h2>
            <div class="home-states">
                @foreach (\App\Http\Controllers\PublicCalendarController::US_STATES as $code => $name)
                    <a href="{{ route('public.map', ['state' => $code]) }}" title="Smoke school training in {{ $name }}">{{ $name }}</a>
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
    .home-stats { display: grid; grid-template-columns: repeat(4, 1fr); text-align: center; }
    .home-stats > div { padding: 0.5rem 1rem; border-right: 1px solid rgba(255,255,255,0.2); }
    .home-stats > div:last-child { border-right: none; }
    .home-support-row { display: flex; gap: 3rem; align-items: flex-start; }
    .home-service-card { display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; margin-bottom: 1rem; border: 1px solid #f0f0f0; border-radius: 0.5rem; text-decoration: none; transition: border-color 0.15s, box-shadow 0.15s; }
    .home-service-card:hover { border-color: #b82027; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .home-service-icon { width: 2.5rem; height: 2.5rem; flex-shrink: 0; background: #b82027; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
    .home-states { columns: 5; column-gap: 20px; }
    .home-states a { display: block; margin-bottom: 8px; break-inside: avoid; }
    @media (max-width: 768px) {
        .home-stats { grid-template-columns: repeat(2, 1fr); }
        .home-stats > div:nth-child(2) { border-right: none; }
        .home-support-row { flex-direction: column; gap: 1.5rem; }
        .home-states { columns: 2; }
    }
</style>
@endpush
