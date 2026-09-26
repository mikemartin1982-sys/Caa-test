@extends('layouts.app')

@section('title', 'VEO Readings by Professionals in the Smoke School Industry - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "VEO Readings," third of the four VEO
    Services sub-pages. Real, honest departure: the bottom promo bar's
    only real CTA (why-choose-compliance.php) doesn't exist yet -- no
    "About" section page has been built -- so that whole section is
    omitted, matching the same approach used on the VEO Expertise page.
--}}
@section('content')
    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Method 9 Visible Emissions Reading Services</h1>
            <p class="public-page-subhead">Professional visible emissions readings and consultation by opacity experts</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

            <div style="width:380px; max-width:100%; flex-shrink:0;">
                <img src="/images/VEO-readings-by-experts.jpg" alt="Compliance Assurance performs VEO readings" style="width:100%; border-radius:0.375rem;">
            </div>

            <div style="flex:1; min-width:320px;">
                <p>
                    The opacity experts at Compliance Assurance Associates, Inc. (CAA) perform visible
                    emissions observations (VEO) readings for companies requiring assistance. CAA staff
                    have decades of experience in VEO and can perform hard-to-perform visible emissions
                    readings. Services also include consultation on problem readings, air quality
                    compliance, and more.
                </p>
                <p>
                    Please contact CAA at <a href="tel:901-381-9960">901-381-9960</a>.
                </p>
            </div>

        </div>
    </div>
@endsection
