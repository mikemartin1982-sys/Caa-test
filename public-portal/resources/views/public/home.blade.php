@extends('layouts.app')

@section('title', 'Compliance Assurance Associates, Inc. — EPA Method 9 Opacity Training')

@push('styles')
<style>
    .hero { text-align: center; padding: 2rem 0 3rem; }
    .hero h1 { font-size: 2rem; margin-bottom: 0.75rem; }
    .hero p.lede { font-size: 1.15rem; color: #444444; max-width: 640px; margin: 0 auto 1.5rem; }
    .hero .cta-row a { margin: 0 0.5rem; }

    .stats-bar { display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap; padding: 2rem 0; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; margin-bottom: 3rem; }
    .stats-bar .stat { text-align: center; }
    .stats-bar .stat .figure { font-size: 1.75rem; font-weight: 700; color: #005da0; }
    .stats-bar .stat .label { font-size: 0.85rem; color: #444444; }

    .service-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 3rem; }
    @media (max-width: 700px) { .service-cards { grid-template-columns: 1fr; } }
    .service-card { border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; }
    .service-card h3 { margin-bottom: 0.5rem; }
    .service-card ul { padding-left: 1.25rem; margin-bottom: 1.25rem; }

    .promo-cta { border-radius: 0.5rem; text-align: center; margin-bottom: 1rem; }
</style>
@endpush

@section('content')
    <section class="hero">
        <h1>Full Service Visible Emissions Provider</h1>
        <p class="lede">VR Smoke Schools. In-Person Smoke Schools. Visible Emission Compliance Services.</p>
        <div class="cta-row">
            <a href="{{ route('public.become-a-client') }}" class="btn-primary">Get Started on VirtualOpacity &raquo;</a>
            <a href="{{ route('public.calendar') }}" class="btn-secondary">Find an In-Person School &raquo;</a>
        </div>
    </section>

    <section class="stats-bar">
        <div class="stat">
            <div class="figure">115,000+</div>
            <div class="label">Observers Certified</div>
        </div>
        <div class="stat">
            <div class="figure">Since 2001</div>
            <div class="label">Trusted Smoke School Leader</div>
        </div>
        <div class="stat">
            <div class="figure">ALT-152A</div>
            <div class="label">VR Smoke School</div>
        </div>
        <div class="stat">
            <div class="figure">100% Digital</div>
            <div class="label">Method 9 Training Records</div>
        </div>
    </section>

    <h2 style="text-align:center; margin-bottom:1.5rem;">Get EPA Method 9 Certified &mdash; Smoke Schools to Fit Your Needs</h2>

    <div class="service-cards">
        <div class="service-card">
            <h3 class="text-blue">Virtual Smoke Schools</h3>
            <p>Train anywhere, anytime. Federal EPA approved ALT-152A.</p>
            <ul>
                <li>100% online Method 9 certification</li>
                <li>No travel expenses or weather hassles</li>
                <li>Train on your schedule, on your site</li>
                <li>Easy digital certification management</li>
            </ul>
            <a href="{{ route('public.become-a-client') }}" class="btn-primary">VR Smoke Schools &raquo;</a>
        </div>
        <div class="service-card">
            <h3 class="text-blue">In-Person Smoke Schools</h3>
            <p>Traditional field certification with CAA instructors.</p>
            <ul>
                <li>Public smoke schools nationwide</li>
                <li>Private on-site smoke schools</li>
                <li>Fast digital certification</li>
                <li>Experienced CAA instructors</li>
            </ul>
            <a href="{{ route('public.calendar') }}" class="btn-primary">In-Person Smoke Schools &raquo;</a>
        </div>
    </div>

    <div class="caa-promo promo-cta">
        <h2>Ready to Get Certified?</h2>
        <p>Find a public smoke school near you, or reach out about a private on-site session.</p>
        <a href="{{ route('public.calendar') }}" class="btn-white">Find a School &raquo;</a>
        <a href="{{ route('public.become-a-client') }}" class="btn-white">Become a Client &raquo;</a>
    </div>
@endsection
