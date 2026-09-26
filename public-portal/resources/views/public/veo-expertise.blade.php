@extends('layouts.app')

@section('title', 'Law-Related Services for Method 9, Method 22 Certification - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "VEO Law-Related Services," second of the
    four VEO Services sub-pages. Has a real inquiry form, kept per
    Michael -- same real pattern as the Private Smoke School inquiry
    form: real Mailable, no database record at all (staff already
    have real templates to respond manually), sent to
    client@compliance-assurance.com, honeypot instead of replicating
    the real source's own MD5-based captcha system (matching the same
    "simple honeypot is sufficient for now" approach Michael already
    confirmed for the earlier form).

    Real, honest departures: the "NOV and observation/documentation
    support" list item keeps its real text but drops the dead link
    (litigation-assistance.php doesn't exist); the entire bottom promo
    bar is omitted -- its only real CTA also points at
    litigation-assistance.php, and the lead-in text has no standalone
    value without a working link.
--}}
@section('content')
    <div class="public-content" style="max-width:1100px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Law-Related Services for Opacity and EPA Method 9</h1>
            <p class="public-page-subhead">Our visible emissions experts assist in air quality compliance issues</p>
        </div>

        <div style="display:flex; gap:2.5rem; flex-wrap:wrap; align-items:flex-start;">

            <div style="width:420px; max-width:100%; flex-shrink:0; border:1px solid #e5e7eb; border-radius:0.375rem; overflow:hidden;">
                <div style="background-color:#005da0; padding:1.25rem 1.5rem;">
                    <h3 style="color:#ffffff; margin:0;">Request for VEO expertise</h3>
                </div>
                <div style="padding:1.5rem;">

                    @if ($errors->any())
                        <div class="admin-form-errors" style="margin-bottom:1rem;">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('public.veo-expertise.submit') }}">
                        @csrf

                        {{-- Honeypot -- genuinely hidden from real users, left for a bot to fill in. --}}
                        <div style="position:absolute; left:-9999px;" aria-hidden="true">
                            <label for="website">Website</label>
                            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div style="display:flex; gap:0.75rem;">
                            <div class="public-field" style="flex:1;">
                                <label for="fname">First Name</label>
                                <input type="text" id="fname" name="fname" value="{{ old('fname') }}" required>
                            </div>
                            <div class="public-field" style="flex:1;">
                                <label for="lname">Last Name</label>
                                <input type="text" id="lname" name="lname" value="{{ old('lname') }}" required>
                            </div>
                        </div>
                        <div class="public-field">
                            <label for="company">Company</label>
                            <input type="text" id="company" name="company" value="{{ old('company') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="state">State</label>
                            <input type="text" id="state" name="state" value="{{ old('state') }}">
                        </div>
                        <div class="public-field">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="emailconfirm">Confirm Email</label>
                            <input type="email" id="emailconfirm" name="emailconfirm" value="{{ old('emailconfirm') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="help">What type of assistance do you need?</label>
                            <input type="text" id="help" name="help" value="{{ old('help') }}" required>
                        </div>
                        <div class="public-field">
                            <label for="phone">Phone (optional)</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                        </div>

                        <button type="submit" class="btn-secondary btn-full">Submit &raquo;</button>
                    </form>
                </div>
            </div>

            <div style="flex:1; min-width:320px;">
                <p>
                    Compliance Assurance Associates, Inc. (CAA) visible emissions experts can assist
                    your company with matters related to air quality compliance.
                </p>
                <p>CAA provides the following VEO services:</p>
                <ul>
                    <li>Notice of Violation (NOV) and observation/documentation support.</li>
                    <li>Expert witness for defending certifications.</li>
                    <li><a href="{{ route('public.veo-services-compliance-plans') }}">Compliance monitoring plans</a>.</li>
                </ul>

                <div class="public-card">
                    <h3>How we've helped clients</h3>

                    <p style="font-weight:600; margin-bottom:0.25rem;">Ship's Captain Arrested for Alleged Opacity Violation</p>
                    <p>
                        Idling engines in the heat of a San Diego summer landed a ship's captain in jail
                        after he refused to discontinue the operation. The police were called and
                        contacted the local air pollution control authority to perform a visible emission
                        reading on the ship's emissions. The ship's owners retained legal counsel who
                        hired CAA to review the opacity observation documentation, including several
                        photographs.
                    </p>
                    <p>
                        The ship had a twin stack system, and from the observation point the reader did
                        not have a clear view to ensure that they were looking through only one diameter,
                        and the sun angle and wind were not ideal. CAA's findings created reasonable
                        doubt, and the ship's captain was released within days of consulting with CAA.
                    </p>

                    <p style="font-weight:600; margin-bottom:0.25rem;">Cement Storage &mdash; Batch Plant Facility</p>
                    <p>
                        Neighbor complaints resulted in a local agency visiting a cement batch plant and
                        issuing a notice of violation for numerous items, including housekeeping issues,
                        fugitive dust, and a specific opacity violation. The opacity violation was
                        $32,500, and the other violations totaled $32,500, resulting in fines of $65,000.
                    </p>
                    <p>
                        The core issue of the neighbor's complaint was the opacity violation. CAA
                        reviewed the agency's opacity reading and ascertained that the sun was not in
                        the 140-degree sector behind the observer's back. The opacity violation was
                        voided. The facility was able to negotiate a settlement which did not admit
                        guilt and paid $650 for the opacity violation.
                    </p>

                    <p style="font-weight:600; margin-bottom:0.25rem;">Using Photographs</p>
                    <p>
                        A grain loading facility had numerous photographs attempting to show the dust
                        levels greatly exceeded the opacity limits. Compliance Assurance Associates,
                        Inc. provided consultation services explaining photos could not be used as
                        evidence of opacity levels but could be used to show the context such that dust
                        is blowing over the property boundary/line. The case was settled between the
                        complainant and the facility in an undisclosed and sequestered manner.
                    </p>
                </div>
            </div>

        </div>
    </div>
@endsection
