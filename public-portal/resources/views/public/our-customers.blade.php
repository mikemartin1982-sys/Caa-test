@extends('layouts.app')

@section('title', 'Who uses Compliance Assurance for Smoke School Training - Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-06 -- "Our Clients," fifth real sub-page in the
    About fork. Simplest page in this fork so far -- a plain,
    three-column list of client company names, no links or form in
    the real, original source at all.

    Nav wiring (app.blade.php) intentionally deferred per Michael --
    batching the whole About-section nav update until all six real
    sub-pages exist.
--}}
@section('content')
    <div class="public-content" style="max-width:1200px; margin:0 auto; padding:2rem;">
        <div class="public-page-header">
            <h1>Compliance Assurance Smoke School Clients</h1>
            <p class="public-page-subhead">CAA's Method 9 training is trusted by over 2,000 organizations. Here are a few</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:0 2.5rem;">

            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.4rem;">
                <li>ACME Brick Co.</li>
                <li>Air Products and Chemicals</li>
                <li>Alamo Cement Co.</li>
                <li>Alcoa, Inc.</li>
                <li>Alliant Energy</li>
                <li>American Electric Power</li>
                <li>Anheuser-Busch</li>
                <li>Archer Daniels Midland</li>
                <li>Arkansas Glass Container</li>
                <li>Arkema, Inc.</li>
                <li>Ash Grove Cement</li>
                <li>Ashley Furniture, Inc.</li>
                <li>BAH</li>
                <li>BASIC</li>
                <li>Bastrop Energy Center</li>
                <li>Baylor University</li>
                <li>Birmingham Steel</li>
                <li>BOC Linde Gas</li>
                <li>BP AMOCO</li>
                <li>Buzzi Unicem USA, Inc.</li>
                <li>Capitol Cement Corp.</li>
                <li>Cargill, Inc.</li>
                <li>Carpenter Co</li>
                <li>Carpenter Company</li>
                <li>Celanese Corp.</li>
                <li>CEMEX</li>
            </ul>

            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.4rem;">
                <li>Chemicals, Inc.</li>
                <li>Chevron Phillips Chemical Co.</li>
                <li>Claymont Steel Co.</li>
                <li>CMC Steel</li>
                <li>Cox Interiors, Inc.</li>
                <li>CPS Energy</li>
                <li>DuPont</li>
                <li>Eastman Chemical Co.</li>
                <li>ESTERLNE Defense</li>
                <li>Ethyl Corp.</li>
                <li>Garland Power Co.</li>
                <li>Georgia-Pacific</li>
                <li>International Paper Co.</li>
                <li>INVISTA</li>
                <li>JL French</li>
                <li>Johnson Controls</li>
                <li>Kingsford Products Co.</li>
                <li>Lafarge Corp.</li>
                <li>Lockheed Martin Corp.</li>
                <li>LONE STAR Steel Co.</li>
                <li>Luminant</li>
                <li>Lyondell Industries</li>
                <li>METCO</li>
                <li>Michelin USA</li>
                <li>National Gypsum Co.</li>
                <li>NRG Energy, Inc.</li>
                <li>Occidental Chemical</li>
                <li>Owens Corning</li>
            </ul>

            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.4rem;">
                <li>Quikrete Cement &amp; Concrete</li>
                <li>Redstone Arsenal</li>
                <li>Riceland Foods, Inc.</li>
                <li>Roanoke Cement</li>
                <li>SES Engineering Co.</li>
                <li>SHAW Environmental Group</li>
                <li>Solae Co.</li>
                <li>Solutia, Inc.</li>
                <li>Spectra Energy</li>
                <li>SRI</li>
                <li>Sunoco, Inc.</li>
                <li>TAMKO Building Products</li>
                <li>TCEQ</li>
                <li>Temple-Inland, Inc.</li>
                <li>TOTAL Petrochemicals</li>
                <li>TYCO Valves</li>
                <li>United Refining Co.</li>
                <li>United States Gypsum Co.</li>
                <li>US Army</li>
                <li>US Department of Energy</li>
                <li>Veoliaes</li>
                <li>VULCAN Materials Co.</li>
                <li>Weston Solutions</li>
                <li>Westward</li>
                <li>Wisconsin Public Service</li>
                <li>Yahara Materials, Inc.</li>
            </ul>

        </div>
    </div>
@endsection
