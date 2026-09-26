@extends('layouts.app')

@section('title', 'Public Smoke School Training Options - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-05 -- Smoke School Discovery pages, third of the
    series.

    Michael, 2026-09-05, later same day -- found live: the two real
    images and the "Students: How to Prepare" CTA were originally
    omitted because they pointed at /digital-student.php, which didn't
    exist yet at the time -- that page now genuinely exists
    (public.digital-student), so both are restored here.
--}}
@section('content')
    <div class="public-content" style="max-width:1000px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Public Smoke School Field Training</h1>
            <p class="public-page-subhead">Fast. Efficient. High-quality opacity training.</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

            <div style="width:320px; flex-shrink:0; display:flex; flex-direction:column; gap:1rem;">
                <img src="/images/method-9-certification-on-phone.jpg" alt="Public opacity training classes for Method 9 and Method 22 VEO" style="width:100%; border-radius:0.375rem; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <img src="/images/digital-cert-overview.jpg" alt="Method 9 and Method 22 certification on a phone" style="width:100%; border-radius:0.375rem; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <a href="{{ route('public.digital-student') }}" class="btn-primary btn-full" style="line-height:1.3;">
                    Students<br>
                    <span style="font-weight:400; font-size:0.85rem;">How to Prepare for Digital Smoke School</span>
                </a>
            </div>

            <div style="flex:1; min-width:320px;">
                <p>
                    Compliance Assurance Associates, Inc. (CAA) opacity training includes a lecture component
                    and a field component. You may enroll for one or both components.
                </p>
                <p>
                    Our field certification smoke schools are designed to be completed in less than a half
                    day, including calibration, practice, test, and administration.
                </p>

                <h3>Our Smoke Schools Are FAST</h3>
                <p>
                    CAA offers exclusive digital field certification that allows students to certify on a
                    mobile device, typically on their own phone.
                </p>

                <h3>Your Employees Benefit</h3>
                <p>
                    Less waiting &mdash; no waiting to sign in, no waiting for grading, no waiting for
                    certification proof. Lecture requirements are completed through an online self-paced course.
                </p>

                <h3>Your Organization Benefits</h3>
                <p>
                    Less time away from work with fewer work coverage hassles. Digital certification results
                    in comprehensive records for defensible readings &mdash; test run data, identification
                    proof, and certifications.
                </p>
            </div>

        </div>

        <div class="caa-promo" style="border-radius:0.375rem; padding:2rem; margin-top:2rem; text-align:center;">
            <h2 style="margin-bottom:0.5rem;">We offer smoke schools throughout the United States.</h2>
            <p style="margin-bottom:1.25rem;">Questions? Call CAA at 901-381-9960.</p>
            <a href="{{ route('public.calendar') }}" class="btn-white">Find a School Near You &raquo;</a>
        </div>
    </div>
@endsection
