@extends('layouts.app')

@section('title', 'Meet our Team - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "Meet Our Team," fourth real sub-page in
    the About fork. www.VEO-APP.com is a real, genuinely external
    domain (not part of our own platform, not a dead internal link),
    kept as-is. The bottom CTA maps to our own, real public.calendar
    route instead of the dead find-a-smoke-school.php.

    Nav wiring (app.blade.php) intentionally deferred per Michael --
    batching the whole About-section nav update until all six real
    sub-pages exist.
--}}
@section('content')
    <div class="public-content" style="max-width:1200px; margin:0 auto; padding:2rem; background-color:#f7f9fb;">
        <div class="public-page-header">
            <h1>The Compliance Assurance Team</h1>
            <p class="public-page-subhead">Knowledge. Experience. Training Expertise.</p>
        </div>

        <h3 style="padding-bottom:0.5rem; border-bottom:2px solid #005da0; margin-bottom:1.5rem;">Management Staff</h3>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:1.5rem; margin-bottom:2.5rem;">

            <div class="public-card" style="margin-bottom:0;">
                <div style="text-align:center; margin-bottom:1rem;">
                    <img src="/images/team-pictures/Joe-Spivey-Compliance-Assurance.jpg" alt="Joseph Spivey, President of Compliance Assurance" style="width:10rem; height:10rem; object-fit:cover; border-radius:0.375rem;">
                </div>
                <h3 style="margin-bottom:0.1rem;">Joseph Spivey</h3>
                <p class="hint" style="margin:0 0 0.5rem;">President &mdash; Field Monitor &amp; Operator</p>
                <p style="font-size:0.85rem; margin-bottom:1rem;">
                    Cell: 919-830-7682 &nbsp;&bull;&nbsp; <a href="mailto:Joe.Spivey@compliance-assurance.com">Email &raquo;</a>
                </p>
                <p>Joe Spivey studied Biology and Chemistry at the University of North Carolina at Wilmington and holds a B.A. in Chemistry and a B.S. in Biology.</p>
                <p>He has worked in the environmental field most of his adult life. Since 1989, Joe has provided Method 9 training for thousands of students. He is skilled at navigating facilities with compliance issues into compliance and assisting facility personnel in resolving issues with regulatory agencies in a non-adversarial way.</p>
                <p>Joe provides support for Notice of Violations; he is the most knowledgeable technical expert in Method 9 currently practicing in the U.S. and is qualified to do all aspects of Method 9 training, including defense of readings and challenging reading observations in court.</p>
                <p>Joe has spearheaded the development of <a href="http://www.VEO-APP.com" target="_blank" rel="noopener">www.VEO-APP.com</a> and has made significant contributions to its development. The VEO-APP improves the administrative processes of visible emissions requirements and of keeping multiple facilities in compliance.</p>
            </div>

            <div class="public-card" style="margin-bottom:0;">
                <div style="text-align:center; margin-bottom:1rem;">
                    <img src="/images/team-pictures/derek-mason-compliance-assurance.jpg" alt="Derek Mason, Vice President Operations" style="width:10rem; height:10rem; object-fit:cover; border-radius:0.375rem;">
                </div>
                <h3 style="margin-bottom:0.1rem;">Derek Mason</h3>
                <p class="hint" style="margin:0 0 0.5rem;">Vice President, Operations &mdash; Field Monitor &amp; Operator</p>
                <p style="font-size:0.85rem; margin-bottom:1rem;">
                    Cell: 256-603-9456 &nbsp;&bull;&nbsp; <a href="mailto:derek.mason@compliance-assurance.com">Email &raquo;</a>
                </p>
                <p>Derek joined CAA in 2019 with a background in technical and managerial experience. He manages overall company operations, including field operations, logistics, and smoke school program delivery.</p>
                <p>Derek oversees EPA Method 9 product development, management, and support, and serves as a senior consultant for review of industry documentation regarding compliance and enforcement. He works closely with prospective and current customers to ensure their training needs are met.</p>
            </div>

        </div>

        <h3 style="padding-bottom:0.5rem; border-bottom:2px solid #005da0; margin-bottom:1.5rem;">Office and Field Staff</h3>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1.25rem; margin-bottom:2.5rem;">

            <div class="public-card" style="margin-bottom:0; padding:0; overflow:hidden; display:flex; flex-direction:column;">
                <img src="/images/team-pictures/chasity-miranda-Compliance-Assurance.jpg" alt="Chasity Miranda, Office Administrator" style="width:100%; height:11rem; object-fit:cover;">
                <div style="padding:0.9rem; flex:1; display:flex; flex-direction:column;">
                    <p style="color:#005da0; font-weight:600; font-size:0.9rem; margin:0 0 0.15rem;">Chasity Miranda</p>
                    <p class="hint" style="font-size:0.75rem; margin:0 0 0.5rem;">Office Administrator &amp; Human Resources</p>
                    <p style="font-size:0.75rem; margin:0 0 0.5rem;">901-381-9960 Ext.&nbsp;1</p>
                    <a href="mailto:Chasity.Miranda@compliance-assurance.com" style="font-size:0.75rem; margin-top:auto;">Email &raquo;</a>
                </div>
            </div>

            <div class="public-card" style="margin-bottom:0; padding:0; overflow:hidden; display:flex; flex-direction:column;">
                <img src="/images/team-pictures/David-Crockett-Compliance-Assurance.jpg" alt="David Crockett, Shop Coordinator" style="width:100%; height:11rem; object-fit:cover;">
                <div style="padding:0.9rem; flex:1; display:flex; flex-direction:column;">
                    <p style="color:#005da0; font-weight:600; font-size:0.9rem; margin:0 0 0.15rem;">David Crockett</p>
                    <p class="hint" style="font-size:0.75rem; margin:0 0 0.5rem;">Shop Coordinator<br>Field Monitor &amp; Operator</p>
                    <a href="mailto:david.crockett@compliance-assurance.com" style="font-size:0.75rem; margin-top:auto;">Email &raquo;</a>
                </div>
            </div>

            <div class="public-card" style="margin-bottom:0; padding:0; overflow:hidden; display:flex; flex-direction:column;">
                <img src="/images/team-pictures/justin-gray-compliance-assurance.jpg" alt="Justin Gray, Field Monitor" style="width:100%; height:11rem; object-fit:cover;">
                <div style="padding:0.9rem; flex:1; display:flex; flex-direction:column;">
                    <p style="color:#005da0; font-weight:600; font-size:0.9rem; margin:0 0 0.15rem;">Justin Gray</p>
                    <p class="hint" style="font-size:0.75rem; margin:0 0 0.5rem;">Field Monitor &amp; Operator</p>
                    <a href="mailto:justin.gray@compliance-assurance.com" style="font-size:0.75rem; margin-top:auto;">Email &raquo;</a>
                </div>
            </div>

            <div class="public-card" style="margin-bottom:0; padding:0; overflow:hidden; display:flex; flex-direction:column;">
                <img src="/images/team-pictures/Michael-Martin-Compliance-Assurance.jpg" alt="Michael Martin, Regional Logistics Coordinator" style="width:100%; height:11rem; object-fit:cover;">
                <div style="padding:0.9rem; flex:1; display:flex; flex-direction:column;">
                    <p style="color:#005da0; font-weight:600; font-size:0.9rem; margin:0 0 0.15rem;">Michael Martin</p>
                    <p class="hint" style="font-size:0.75rem; margin:0 0 0.5rem;">Regional Logistics Coordinator<br>Field Monitor &amp; Operator</p>
                    <a href="mailto:michael.martin@compliance-assurance.com" style="font-size:0.75rem; margin-top:auto;">Email &raquo;</a>
                </div>
            </div>

            <div class="public-card" style="margin-bottom:0; padding:0; overflow:hidden; display:flex; flex-direction:column;">
                <img src="/images/team-pictures/rebecca-walker-compliance-assurance.jpg" alt="Rebecca Walker, Logistics Coordinator" style="width:100%; height:11rem; object-fit:cover;">
                <div style="padding:0.9rem; flex:1; display:flex; flex-direction:column;">
                    <p style="color:#005da0; font-weight:600; font-size:0.9rem; margin:0 0 0.15rem;">Rebecca Walker</p>
                    <p class="hint" style="font-size:0.75rem; margin:0 0 0.5rem;">Logistics Coordinator<br>Field Monitor &amp; Operator</p>
                    <a href="mailto:rebecca.walker@compliance-assurance.com" style="font-size:0.75rem; margin-top:auto;">Email &raquo;</a>
                </div>
            </div>

        </div>

        <div class="caa-promo" style="border-radius:0.375rem; padding:1.5rem 2rem; display:flex; align-items:center; justify-content:space-between; gap:1.5rem; flex-wrap:wrap;">
            <div>
                <h3 style="color:#ffffff; margin-bottom:0.25rem;">Call us today at 901-381-9960.</h3>
                <p style="margin:0;">We're here to answer your questions and help you maintain your Method 9 certification.</p>
            </div>
            <a href="{{ route('public.calendar') }}" class="btn-white" style="flex-shrink:0; white-space:nowrap;">Find a Smoke School &raquo;</a>
        </div>
    </div>
@endsection
