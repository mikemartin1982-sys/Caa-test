@extends('lecture.layout')

@section('title', 'Smoke School Online Visible Emissions Course')

{{--
    Michael, 2026-09-06 -- real home-base landing page, shown right after
    sign-in. Sidebar renders dynamically from the real $sections data
    returned by the Java side (migration 044's lecture_sections /
    lecture_pages / student progress) -- currently just the 10 real
    section names with no pages under any of them yet, since Michael is
    capturing real page content one section at a time. As pages get
    added, they'll appear here automatically -- nothing in this view is
    hardcoded to a specific section or page.

    Michael, 2026-09-07 -- three real design fixes to match the live
    course exactly: (1) the real, full-width blue page-title bar (see
    lecture.layout's own @yield('page-title')) -- this view previously
    only had a plain, small h1, not a real, distinct bar at all; (2)
    Terms and Conditions / Optimal Viewing Experience are now real,
    collapsible toggles using the same shared .lect-toggle /
    lectureToggle() component added for the Getting Started page,
    replacing the earlier, flattened always-visible version; (3) the
    plain h3 section headers ("View the Introduction Document," "Your
    Status...") are now blue, matching the live course.
--}}
@section('page-title', 'Home Base')

@section('sidebar')
    @include('lecture.sidebar', ['sections' => $sections, 'resources' => $resources, 'currentPageId' => null])
@endsection

@section('content')
    {{--
        Michael, 2026-09-07 -- found live: markPageRead() correctly
        redirects here with a real "status" flash message when a page's
        own real nextPageId is null (the section's last built page so
        far, section quiz not built yet) -- but nothing here ever
        actually displayed that message. From the student's side, this
        made "Next" look like it silently did nothing at all, when it
        was genuinely working as designed (there's no real next page to
        advance to yet).
    --}}
    @if (session('status'))
        <div style="background-color:#e1f0ff; color:#005da0; border:1px solid #b8dcff; border-radius:0.375rem; padding:0.9rem 1.25rem; margin-bottom:1.5rem; font-weight:600;">
            {{ session('status') }}
        </div>
    @endif

    <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

        <div style="flex:1; min-width:280px;">
            <h3 style="color:#005da0;">View the Introduction Document</h3>
            <p>
                To familiarize yourself with this course, please
                <a href="/PDFs/12902302-CAA-self-paced-lecture-instructions.pdf" target="_blank" rel="noopener">view this PDF &raquo;</a>
            </p>
            <img src="/images/lecture/home-base-image-1.jpg" alt="Online visible emissions training." style="max-width:100%; border-radius:0.375rem;">

            <h3 style="margin-top:1.5rem; color:#b82027;">Remember: <span style="color:#005da0; font-size:0.85em;">Before Starting the Course</span></h3>
            <p>Ensure that your environment is free of distractions and is conducive to learning.</p>
        </div>

        <div style="flex:1; min-width:280px;">
            <h3 style="color:#005da0;"><em>Your Status: Your Training/Quiz is in Progress</em></h3>
            <p>
                Your quiz progress is shown at the top of the left-side menu. Since you have not yet
                completed this self-paced lecture, you will have to proceed through the course
                material section-by-section.
            </p>

            <h3 style="color:#005da0;">The Training and Quiz are Self-Paced</h3>
            <p>
                The course is divided into topic sections with a quiz at the end of each section.
                You will take the section quiz after each section. You must pass the quiz for each
                section (at least 70% correct) before proceeding to the next section. You may stop
                the course at any time and come back to the course material or quiz at your
                convenience.
            </p>

            <div class="lect-toggle">
                <button type="button" onclick="lectureToggle('hb-terms', 'hb-terms-icon')" class="lect-toggle-btn">
                    <span>Terms and Conditions</span>
                    <span id="hb-terms-icon" class="lect-toggle-icon">+</span>
                </button>
                <div id="hb-terms" class="lect-toggle-body" style="display:none;">
                    <p style="margin-bottom:0;">
                        Please note that the Compliance Assurance Associates, Inc. (CAA) lecture course
                        provides general information; it does not provide legal or professional advice.
                        If you have a specific visible emissions issue you would like to inquire about,
                        please submit your question via the navigation bar and CAA will assist you.
                    </p>
                </div>
            </div>

            <div class="lect-toggle">
                <button type="button" onclick="lectureToggle('hb-viewing', 'hb-viewing-icon')" class="lect-toggle-btn">
                    <span>Optimal Viewing Experience</span>
                    <span id="hb-viewing-icon" class="lect-toggle-icon">+</span>
                </button>
                <div id="hb-viewing" class="lect-toggle-body" style="display:none;">
                    <p>
                        The lecture course is designed for viewing on desktop computers. However, it
                        will work on mobile devices.
                    </p>
                    <p style="margin-bottom:0;">
                        If you experience any issues with viewing the course, please
                        <a href="mailto:stephanie.mullaney@compliance-assurance.com?Subject=Online Course Issue">contact the webmaster</a>.
                        Include the type of device and browser you are using.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <div style="text-align:center; margin-top:2rem;">
        @if (! empty($sections[0]['pages'][0]['id']))
            <a href="{{ route('lecture.page', $sections[0]['pages'][0]['id']) }}" style="display:inline-block; padding:0.75rem 1.5rem; background-color:#b82027; color:#ffffff; font-weight:700; border-radius:0.375rem; text-decoration:none;">
                Introduction :: Getting Started &raquo;
            </a>
        @else
            <span style="display:inline-block; padding:0.75rem 1.5rem; background-color:#d1d5db; color:#999999; font-weight:700; border-radius:0.375rem;" title="Not built yet">
                Introduction :: Getting Started &raquo;
            </span>
        @endif
    </div>
@endsection
