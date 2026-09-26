@extends('layouts.app')

@section('title', 'For New Clients at Compliance Assurance Smoke Schools - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-05 -- "Prospective Clients" nav item, in order per
    Michael. Real, honest departures from the live source, all because
    the underlying pages genuinely don't exist here yet:
    - "digital field certification" and "online self-paced lecture
      program" mentions kept as plain text, links removed (matching
      the same approach already used on the Public Smoke Schools page)
      -- their real destination pages (digital.php,
      online-self-paced-lecture.php) don't exist.
    - The entire "Helpful Links" section is omitted -- 5 of its 6 real
      links point to pages that don't exist (veo-course-summary.php,
      fast-smoke-schools.php, digital-student.php, faqs.php,
      resources-for-VEO.php); the one real survivor ("Request a new
      client account") is already, redundantly shown as the primary
      CTA button in the left column.
--}}
@section('content')
    <div class="public-content" style="max-width:1000px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Prospective and New Smoke School Clients</h1>
            <p class="public-page-subhead">Welcome to Method 9 certification with Compliance Assurance</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

            <div style="width:280px; flex-shrink:0;">
                <img src="/images/public-smoke-school.jpg" alt="Opacity training for Method 9 and Method 22 VEO" style="width:100%; border-radius:0.375rem; box-shadow:0 1px 3px rgba(0,0,0,0.1); margin-bottom:1rem;">
                <a href="{{ route('account.register') }}" class="btn-primary btn-full">Create Client Account &raquo;</a>
            </div>

            <div style="flex:1; min-width:320px;">
                <p>
                    Compliance Assurance Associates, Inc. (CAA) is the industry leader in opacity
                    training. CAA is consistently at the forefront of technology and innovation for
                    smoke schools.
                </p>
                <p>
                    Our smoke schools feature exclusive digital field certification and an online
                    self-paced lecture program.
                </p>

                <p style="margin-bottom:0.5rem;">Your organization will benefit from:</p>
                <ul>
                    <li>Less employee time away from work.</li>
                    <li>Less waiting at smoke schools.</li>
                    <li>Comprehensive digital records of smoke school runs and certifications.</li>
                    <li>Email registration reminders.</li>
                </ul>
            </div>

        </div>

        <div class="caa-promo" style="border-radius:0.375rem; padding:2rem; margin-top:2rem; text-align:center;">
            <h2 style="margin-bottom:0.5rem;">We offer smoke schools throughout the United States</h2>
            <p style="margin-bottom:1.25rem;">Questions? Call CAA at 901-381-9960.</p>
            <a href="{{ route('public.calendar') }}" class="btn-white">Find a School Near You &raquo;</a>
        </div>
    </div>
@endsection
