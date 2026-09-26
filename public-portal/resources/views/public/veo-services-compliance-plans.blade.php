@extends('layouts.app')

@section('title', 'Air Quality Compliance Plans and Services - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- first of the four VEO Services sub-pages,
    forking out from professional-services.php. Real, honest
    departure: the flyer PDF links directly to the real, live DIBs
    file for now (matching the same approach as the VR page's own
    resource PDFs) -- a real, external document, not an internal page.
--}}
@section('content')
    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Air Quality Compliance Plans</h1>
            <p class="public-page-subhead">Compliance plan development, updates, and consultation</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">

            <div style="width:300px; flex-shrink:0;">
                <a href="https://compliance-assurance.com/PDFs/CAA-Compliance-plan-flyer.pdf" target="_blank" rel="noopener">
                    <img src="/images/CAA-Compliance-plan-flyer.jpg" alt="Clean Air Act compliance plans" style="width:100%; border-radius:0.375rem;">
                </a>
                <p><a href="https://compliance-assurance.com/PDFs/CAA-Compliance-plan-flyer.pdf" target="_blank" rel="noopener">Read flyer &raquo;</a></p>
            </div>

            <div style="flex:1; min-width:320px;">
                <p>
                    It's important to choose a compliance partner who understands what is required to
                    develop and maintain a compliant air quality program. Compliance Assurance
                    Associates, Inc. (CAA)'s experienced staff understands the regulations, emission
                    types, emission control equipment, VEO readings, and record keeping required to
                    maintain Clean Air Act compliance.
                </p>
                <p>
                    CAA can assist your organization in developing a robust air quality compliance
                    plan and help maintain compliance.
                </p>
                <p>
                    Additionally, CAA provides consultation on difficult compliance issues and
                    opacity readings.
                </p>
                <p>
                    For more information, contact <a href="mailto:joe.spivey@compliance-assurance.com">Joe Spivey</a>.
                </p>
            </div>

        </div>
    </div>
@endsection
