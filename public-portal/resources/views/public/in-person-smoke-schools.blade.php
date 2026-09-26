@extends('layouts.app')

@section('title', "CAA's In-Person Smoke School Method 9 training and certification - Compliance Assurance Associates, Inc.")

{{--
    Michael, 2026-09-05 -- Smoke School Discovery pages, second of the
    series (all four now built: VR, In-Person here, Public, Private).

    Michael, 2026-09-05, later same day -- found live: this page's own
    layout had drifted noticeably from the real, live source (single
    column with the image stacked above the text, square buttons,
    centered promo bar) -- rebuilt to genuinely match the original's
    real structure: text/image side by side, pill-shaped CTAs, and a
    real, two-column promo bar (text left, button right).
--}}
@section('content')
    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>In-Person Smoke School Training</h1>
            <p class="public-page-subhead">Public and on-site Method 9 training</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap; align-items:flex-start;">

            <div style="flex:1; min-width:320px;">
                <p>
                    Compliance Assurance Associates, Inc. (CAA) offers public smoke schools and private,
                    on-site training. Choose the format that fits your organization's needs and schedule.
                </p>
                <p>
                    Hands-on training enables students to learn to read smoke in a live environment and
                    to receive coaching between runs.
                </p>

                <h3 style="color:#b82027;">Public Smoke Schools</h3>
                <p>
                    Using CAA's digital certification, our smoke schools eliminate the student waiting
                    experienced at other smoke schools. No waiting for sign-in, grading, or correcting
                    errors on paper. Certifications are issued immediately.
                </p>
                <a href="{{ route('public.public-smoke-schools') }}" class="btn-primary" style="border-radius:9999px; margin-bottom:2rem; display:inline-block;">Public School Info &raquo;</a>

                <h3 style="color:#b82027;">Private Smoke Schools</h3>
                <p>
                    CAA will come to your site on your schedule. Using CAA's digital certification,
                    student waiting is eliminated. The sign-in process and grading are done
                    electronically. Human error is reduced and students receive their certifications
                    immediately via email.
                </p>
                <a href="{{ route('public.private-smoke-schools') }}" class="btn-primary" style="border-radius:9999px; display:inline-block;">Private School Info &raquo;</a>
            </div>

            <div style="width:380px; max-width:100%; flex-shrink:0; border-radius:0.5rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.15);">
                <img src="/images/in-person-smoke-schools.jpg" alt="Hands-on smoke school training" style="width:100%; height:auto; display:block;">
            </div>

        </div>

        <div class="caa-promo" style="border-radius:0.375rem; padding:2rem; margin-top:2.5rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:1.5rem; flex-wrap:wrap; text-align:left;">
                <div>
                    <h3 style="margin-bottom:0.35rem;">Thinking about Virtual Smoke School Training?</h3>
                    <p style="margin:0;">CAA offers 100% online EPA Method 9 certification.</p>
                </div>
                <a href="{{ route('public.vr-smoke-school') }}" class="btn-white" style="border-radius:0.5rem; text-transform:uppercase; letter-spacing:0.06em; padding:1rem 2rem; flex-shrink:0;">Learn More &raquo;</a>
            </div>
        </div>
    </div>
@endsection
