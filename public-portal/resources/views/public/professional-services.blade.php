@extends('layouts.app')

@section('title', 'VEO Professional Services Offered by Compliance Assurance - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "Compliance Services" / "VEO Services",
    unlocks the homepage's own remaining hero panel and navy-panel CTA
    once built. Real, honest departure: all four service cards' own
    real "Learn more" links (veo-services-compliance-plans.php,
    veo-expertise.php, veo-readings.php, veo-form-instructions.php)
    point at pages that don't exist here yet -- kept the real,
    genuinely valuable descriptive content on each card, dropped only
    the dead links, matching the same approach used throughout this
    series rather than omitting the cards themselves.
--}}
@section('content')
    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Visible Emissions Professional Services</h1>
            <p class="public-page-subhead">Expert visible emissions support for organizational compliance needs</p>
        </div>

        <p>
            Compliance Assurance Associates, Inc. (CAA) is an expert in 40 CFR 60 Appendix A,
            Method 9, Method 22, 203A, 203B, and 203C. With over 60 years of combined experience
            in the opacity industry, CAA is equipped to provide a wide range of services related to VEO.
        </p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem; margin-top:1.5rem;">

            <div class="public-card">
                <h3>Compliance Support</h3>
                <p>
                    CAA's air quality compliance experts assist organizations in developing new
                    compliance plans, as well as evaluating and updating existing compliance efforts.
                    CAA's staff helps organizations navigate the complex Clean Air Act regulations
                    and establish a practical air quality compliance plan.
                </p>
                <a href="{{ route('public.veo-services-compliance-plans') }}" class="btn-primary">View compliance plans &raquo;</a>
            </div>

            <div class="public-card">
                <h3>Opacity Law-Related Issues</h3>
                <p>
                    CAA is available for consultation on issues related to Notices of Violation (NOV).
                    CAA's expert VEO staff can review the NOV, opacity observations, and related
                    documentation to assist in building a defensible response.
                </p>
                <a href="{{ route('public.veo-expertise') }}" class="btn-primary">Request VEO expertise &raquo;</a>
            </div>

            <div class="public-card">
                <h3>Visible Emissions Readings</h3>
                <p>
                    CAA's certified observers conduct on-site visible emissions observations in
                    accordance with EPA Method 9, Method 22, and related methods. CAA provides
                    thorough documentation and reporting to support permit compliance and regulatory
                    recordkeeping requirements.
                </p>
                <a href="{{ route('public.veo-readings') }}" class="btn-primary">Learn about VEO readings &raquo;</a>
            </div>

            <div class="public-card">
                <h3>EPA Method 9 Form Instructions</h3>
                <p>
                    Completing Method 9 forms correctly is critical to maintaining defensible opacity
                    records. CAA's opacity experts guide observers through the process of accurately
                    completing Method 9 field data forms in accordance with EPA requirements.
                </p>
                <a href="{{ route('public.veo-form-instructions') }}" class="btn-primary">View form instructions &raquo;</a>
            </div>

        </div>

        <div class="caa-promo" style="border-radius:0.375rem; padding:1.5rem 2rem; margin-top:2rem; display:flex; align-items:center; justify-content:space-between; gap:1.5rem; flex-wrap:wrap;">
            <p style="margin:0;">
                Whether the choice is in-person, virtual reality, or private smoke school training,
                CAA handles the administration to keep observers certified.
            </p>
            <a href="{{ route('public.calendar') }}" class="btn-white" style="flex-shrink:0; white-space:nowrap;">Find a smoke school &raquo;</a>
        </div>
    </div>
@endsection
