@extends('layouts.app')

@section('title', "CAA's Smoke School Method 9 Training and Certification - Compliance Assurance Associates, Inc.")

{{--
    Michael, 2026-09-06 -- "About Compliance Assurance," first real
    sub-page in the About fork. Real, honest departures: the CTA grid
    keeps its real 2x2 shape -- "Lecture Course" (online-self-paced-
    lecture.php) doesn't exist, so it renders as a greyed
    .btn-primary-disabled tile rather than breaking the grid's
    symmetry by omitting it outright. The bottom "Why Choose
    Compliance Assurance" promo bar is omitted entirely -- its only
    real CTA (why-choose-compliance.php) doesn't exist either.

    Nav wiring (app.blade.php) intentionally deferred per Michael --
    batching the whole About-section nav update until all six real
    sub-pages exist, rather than touching the shared nav file once per
    page.
--}}
@section('content')
    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Method 9 Smoke School Training</h1>
            <p class="public-page-subhead">Opacity training on your schedule</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

            <div style="flex:1; min-width:320px;">
                <p>
                    Compliance Assurance Associates, Inc. (CAA) provides comprehensive Visible
                    Emission Evaluator Training and Certification programs &mdash; commonly called
                    smoke schools &mdash; in accordance with 40 CFR 60 Appendix A, Method 9, Method
                    22, 203A, 203B, and 203C.
                </p>

                <blockquote style="border-left:4px solid #b82027; padding-left:1.25rem; margin:1.5rem 0; font-style:italic;">
                    <p style="margin:0;">
                        CAA has provided <a href="{{ route('public.in-person-smoke-schools') }}">in-person smoke schools</a>
                        since 2001. CAA offered the first commercial
                        <a href="{{ route('public.vr-smoke-school') }}">100% online Method 9 field certification</a>
                        using virtual reality in 2024.
                    </p>
                </blockquote>

                <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:0.75rem; margin-bottom:1.5rem;">
                    <a href="{{ route('public.vr-smoke-school') }}" class="btn-primary" style="text-align:center; font-size:0.9rem;">VR Smoke Schools &raquo;</a>
                    <a href="{{ route('public.public-smoke-schools') }}" class="btn-primary" style="text-align:center; font-size:0.9rem;">Public Schools &raquo;</a>
                    <a href="{{ route('public.private-smoke-schools') }}" class="btn-primary" style="text-align:center; font-size:0.9rem;">Private Schools &raquo;</a>
                    <span class="btn-primary-disabled" style="text-align:center; font-size:0.9rem;" title="Not built yet">Lecture Course &raquo;</span>
                </div>

                <p>
                    No matter the training format, your organization's opacity training records are
                    100% digital and accessible when you need them.
                </p>

                <div class="public-card">
                    <h3>Our Philosophy</h3>
                    <p>
                        Our philosophy is simple &mdash; we want our students to fully understand the
                        underlying concepts of visible emissions observations. By teaching the
                        concepts, the certification process is made easy, and the observations
                        definitive and defensible. Our virtual and in-person programs are designed to
                        ensure student comprehension and develop skilled opacity readers.
                    </p>
                    <p>
                        Knowledgeable, reliable, and accurate visible emissions readers reduce your
                        compliance costs, protect the environment, avoid costly fines, and identify
                        operational issues before serious consequences occur.
                    </p>
                </div>

                <div class="public-card">
                    <h3>The Opacity Technology Leader</h3>
                    <p>CAA is the industry leader in technology:</p>
                    <ul>
                        <li>The first to provide Method 9 certification records online.</li>
                        <li>The first to provide digital field certification for no-contact smoke school field certification.</li>
                        <li>The first and only to provide virtual reality opacity training and certification.</li>
                        <li>The first and only to provide a 100% online solution for EPA Method 9 lecture and field certification.</li>
                    </ul>
                </div>
            </div>

            <div style="width:40%; min-width:280px; flex-shrink:0;">
                <img src="/images/smoke-schools-in-person-or-online.jpg" alt="Compliance Assurance Smoke Schools" style="width:100%; border-radius:0.375rem; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            </div>

        </div>
    </div>
@endsection
