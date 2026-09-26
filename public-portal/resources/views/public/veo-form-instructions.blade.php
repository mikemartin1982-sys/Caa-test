@extends('layouts.app')

@section('title', 'Compliance Assurance Method 9 Form Instructions - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "Method 9 Form Instructions," last of the
    four VEO Services sub-pages -- completes that dropdown. Real,
    honest departure: "Self-Paced Lecture course" mention kept as
    plain text, link removed -- online-self-paced-lecture.php doesn't
    exist. The in-page anchor links (#company-info, etc.) are real,
    self-contained same-page jumps and need no backend route at all.
--}}
@section('content')
    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>EPA Method 9 Form Instructions</h1>
            <p class="public-page-subhead">How to complete a Method 9 observation form</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

            <div style="width:500px; max-width:100%; flex-shrink:0;">
                <div style="position:relative; width:100%; padding-top:56.25%;">
                    <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                            src="https://www.youtube.com/embed/V48Vyhdzuio?list=PL9a-hqaWIK_J9fG0XMP3zfhQaeySK7vZN"
                            title="How to complete the EPA Method 9 observation form"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </div>
                <p style="margin-top:0.75rem; color:#b82027;">This is a <strong>series of videos</strong> that will play in order of appearance on the form.</p>
                <p style="margin-top:-0.5rem;">Individual videos are available below.</p>
            </div>

            <div style="flex:1; min-width:320px;">
                <p>
                    Completing the EPA Method 9 visible emissions observation (VEO) form correctly is
                    a critical step in every field opacity reading. This series of videos walks through
                    each field on the VEO form, explaining what information is required and how to
                    record it accurately.
                </p>
                <p>
                    Topics covered include observer and facility identification, source and unit
                    description, date and time documentation, sky and background conditions, wind
                    direction, observer positioning, and the 15-second opacity readings that make up
                    the observation set.
                </p>
                <p>
                    Whether preparing for an upcoming stack test, brushing up before recertification,
                    or training new observers, this video series provides a clear, practical reference
                    for producing a complete and accurate Method 9 observation form.
                </p>
                <p><strong>Want more information?</strong> Use CAA's Self-Paced Lecture course.</p>
            </div>

        </div>
    </div>

    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:0 2rem 2rem;">
        <h2 id="veo-form-videos" style="font-weight:700; color:#005da0;">Watch Method 9 Form Videos Individually</h2>

        <div id="form-overview" style="display:flex; gap:2.5rem; flex-wrap:wrap; margin-top:2rem;">
            <div style="width:500px; max-width:100%; flex-shrink:0;">
                <div style="position:relative; width:100%; padding-top:56.25%;">
                    <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                            src="https://www.youtube.com/embed/V48Vyhdzuio"
                            title="How to Complete the EPA Method 9 Form: An Introduction"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </div>
            </div>
            <div style="flex:1; min-width:320px;">
                <h3 style="color:#b82027;">EPA Method 9 Form Overview Video</h3>
                <p>
                    This video introduces the Compliance Assurance video series and provides an
                    overview of the EPA Method 9 visible emissions observation form &mdash; its
                    purpose, the categories of opacity readings it addresses, and what to know before
                    getting started with it.
                </p>
                <p><a href="#company-info">Next: Company Info &amp; Equipment &raquo;</a></p>
            </div>
        </div>

        <div id="company-info" style="display:flex; gap:2.5rem; flex-wrap:wrap; margin-top:2.5rem;">
            <div style="width:500px; max-width:100%; flex-shrink:0;">
                <div style="position:relative; width:100%; padding-top:56.25%;">
                    <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                            src="https://www.youtube.com/embed/c291RQnjuJE"
                            title="How to Complete the EPA Method 9 Form: Company Information and Process/Control Equipment Sections"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </div>
            </div>
            <div style="flex:1; min-width:320px;">
                <h3 style="color:#b82027;">Method 9 Observation Form - Company Info &amp; Equipment Video</h3>
                <p>
                    This video walks through the Company Information and Process/Control Equipment
                    sections of the EPA Method 9 visible emissions observation form, outlining what
                    each field requires and where to track down the information needed to complete it
                    accurately.
                </p>
                <p><a href="#emission-point">Next: Emission Point, Description &amp; Conditions &raquo;</a></p>
            </div>
        </div>

        <div id="emission-point" style="display:flex; gap:2.5rem; flex-wrap:wrap; margin-top:2.5rem;">
            <div style="width:500px; max-width:100%; flex-shrink:0;">
                <div style="position:relative; width:100%; padding-top:56.25%;">
                    <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                            src="https://www.youtube.com/embed/CkA1HhMVUVU"
                            title="How to fill in the EPA Method 9 Form: Emission Point, Emission Description, and Environmental Conditions"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </div>
            </div>
            <div style="flex:1; min-width:320px;">
                <h3 style="color:#b82027;">Visible Emissions Observation Form Video &mdash; Emission Point, Description &amp; Conditions</h3>
                <p>
                    Three sections of the EPA Method 9 form deal with where you're observing, what
                    you're observing, and the conditions around you when you observe &mdash; Emission
                    Point, Emission Description, and Environmental Conditions. This video breaks down
                    each one and explains how to fill them in accurately.
                </p>
                <p><a href="#source-sketch">Next: Source Sketch Section &raquo;</a></p>
            </div>
        </div>

        <div id="source-sketch" style="display:flex; gap:2.5rem; flex-wrap:wrap; margin-top:2.5rem;">
            <div style="width:500px; max-width:100%; flex-shrink:0;">
                <div style="position:relative; width:100%; padding-top:56.25%;">
                    <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                            src="https://www.youtube.com/embed/Fl-fhOKHvN4"
                            title="How to complete EPA Method 9 Form: Source Sketch Section"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </div>
            </div>
            <div style="flex:1; min-width:320px;">
                <h3 style="color:#b82027;">Method 9 Form - Source Sketch Section Video</h3>
                <p>
                    The Source Sketch section of the EPA Method 9 form calls for more than a simple
                    drawing &mdash; it has to document precise details about your observation setup.
                    This video breaks down what needs to appear in the sketch and how to fill it out
                    correctly.
                </p>
                <p><a href="#additional-info">Next: Additional Info, Form Number &amp; Observer &raquo;</a></p>
            </div>
        </div>

        <div id="additional-info" style="display:flex; gap:2.5rem; flex-wrap:wrap; margin-top:2.5rem;">
            <div style="width:500px; max-width:100%; flex-shrink:0;">
                <div style="position:relative; width:100%; padding-top:56.25%;">
                    <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                            src="https://www.youtube.com/embed/oCX-dqmaHPE"
                            title="How to Complete the EPA Method 9 Form: Additional Information, Form Number, and Observer Sections"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </div>
            </div>
            <div style="flex:1; min-width:320px;">
                <h3 style="color:#b82027;">VEO Form: Additional Information, Form Number &amp; Observer Video</h3>
                <p>
                    This video covers the Additional Information, Form Number, and Observer sections
                    of the EPA Method 9 visible emissions observation form, breaking down what each
                    field is asking for and where to find the corresponding information.
                </p>
                <p><a href="#observation-data">Next: Observation Data &raquo;</a></p>
            </div>
        </div>

        <div id="observation-data" style="display:flex; gap:2.5rem; flex-wrap:wrap; margin-top:2.5rem;">
            <div style="width:500px; max-width:100%; flex-shrink:0;">
                <div style="position:relative; width:100%; padding-top:56.25%;">
                    <iframe style="position:absolute; inset:0; width:100%; height:100%; border-radius:0.375rem; border:1px solid #e5e7eb;"
                            src="https://www.youtube.com/embed/jZ5Pfx2WYgU"
                            title="Complete the Observation Data Section of the EPA Method 9 Form"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </div>
            </div>
            <div style="flex:1; min-width:320px;">
                <h3 style="color:#b82027;">How to Complete the Observation Data Section of the EPA Method 9 Form</h3>
                <p>
                    This video covers the Observation Data section of the EPA Method 9 visible
                    emissions evaluation form, including how to properly record observations and
                    determine the average opacity reading.
                </p>
            </div>
        </div>

    </div>
@endsection
