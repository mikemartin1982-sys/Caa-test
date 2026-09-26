@extends('layouts.app')

@section('title', 'Digital Certification Information for Smoke School Students - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-05 -- "New Student Smoke School Intro," Existing
    Clients dropdown, in order per Michael. Real, honest departures:
    - "Digital Certification introduction page" (digital.php) kept as
      plain text, link removed -- that page doesn't exist here.
    - "Know your student record number" links directly to our own,
      real, already-built public.certs.find-student-number route
      (replacing the live source's own certs-email-id.php reference).
    - The brochure PDF link points at the real, live DIBs file for now
      (matching the same approach as the VR page's own resource PDFs)
      -- a real, external document, not an internal page.
--}}
@section('content')
    <div class="public-content" style="max-width:1000px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Getting Ready for Smoke School Field Certification</h1>
            <p class="public-page-subhead">What to know before you arrive at smoke school</p>
            <p class="hint" style="margin-top:0.5rem;">U.S. Patent No. D968,440 S</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

            <div style="width:320px; flex-shrink:0;">
                <a href="https://compliance-assurance.com/PDFs/getting-ready-for-digital-smoke-school.pdf" target="_blank" rel="noopener">
                    <img src="/images/getting-ready-for-digital-smoke-school.jpg" alt="No contact smoke school digital certification" style="width:100%; border-radius:0.375rem;">
                </a>
                <p class="hint" style="margin-top:0.5rem;">Click on image to view or download PDF.</p>
                <p style="font-style:italic;">Arriving prepared at the smoke school helps speed up the sign-in process and decreases wait times for all students.</p>
                <p>Questions? Call us at <a href="tel:+1-901-381-9960">901-381-9960</a>.</p>
            </div>

            <div style="flex:1; min-width:320px;">
                <p>
                    CAA's patented digital certification process expedites field certification and
                    offers a no-contact, paperless smoke school.
                </p>

                <div style="position:relative; width:100%; max-width:520px; padding-top:56.25%; margin-bottom:1.5rem;">
                    <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                            src="https://www.youtube.com/embed/nutmiUN4yy0"
                            title="Getting ready for digital smoke school certification"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                </div>

                <h3>Come prepared for smoke school</h3>

                <h4>Know your student record number</h4>
                <p>You can quickly retrieve your student record number by visiting <a href="{{ route('public.certs.find-student-number') }}">this web page &raquo;</a></p>

                <h4>Ensure your smoke school records are up to date</h4>
                <p>If your contact information has changed, or you require a name change, please <a href="tel:+1-901-381-9960">call the CAA office at 901-381-9960</a> before attending smoke school.</p>
            </div>

        </div>
    </div>
@endsection
