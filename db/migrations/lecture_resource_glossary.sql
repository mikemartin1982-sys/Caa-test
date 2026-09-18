-- ============================================================================
-- Lecture content seed: Resources -- Glossary
-- Reference: Michael, 2026-09-07 -- real content capture. Tabs kept
-- intact (real, working tab groups, not simplified to toggles), matching
-- Michael's explicit confirmation.
--
-- Real, genuine bug found and fixed: the live source's "Luminous" entry
-- has a malformed closing tag ("</div" missing its own ">") immediately
-- followed by a stray extra "</div>" -- together these incorrectly close
-- the tab-container early, nesting "Method 9" inside Luminous's own tab
-- group instead of being its own, separate sibling term. Fixed by
-- properly closing Luminous and starting Method 9 as a real sibling.
--
-- Real, honest link decisions: internal glossary self-references
-- (e.g. Method 9''s own link to #prom) kept as real, working anchors
-- within this same page. All external links (Wikipedia, EPA.gov)
-- kept as-is. The one dead internal link (NSPS''s own "Resources" tab
-- linking to compliance-assurance.com/lecture_certification/glossary.php,
-- i.e. itself) removed, text kept.
-- ============================================================================

INSERT INTO lecture_resource_pages (title, slug, content, order_index)
VALUES (
    'Glossary',
    'glossary',
    '<h1>Visible Emissions Glossary</h1>
<p>Definitions in the glossary are referenced throughout the lecture course and indicated by <span style="color:#b82027; font-weight:700;">bold red text.</span></p>

<h4>40 CFR 60 Appendix A Reference Method 9</h4>
<a id="cfr60"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="cfr60-def-btn" class="lect-tab-btn is-active" data-tab-group="cfr60" onclick="lectureTab(''cfr60'', ''cfr60-def'')">Definition</button></li>
    </ul>
    <div id="cfr60-def" class="lect-tab-panel is-active" data-tab-group="cfr60">
        <p>40 CFR 60 Appendix A is one of the EPA (federal) regulations that sets emission standards for U.S. industries. Section 111 of the Clean Air Act required the EPA to develop standards for controlling air pollution. The regulation title represents:</p>
        <ul>
            <li>Title 40: Protection of the environment</li>
            <li>CFR: Code of Federal Regulations</li>
            <li>Part 60: Standards of performance for new and modified stationary sources</li>
            <li>Appendix A: Test methods</li>
        </ul>
    </div>
</div>

<h4>Baghouse</h4>
<a id="baghouse"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="baghouse-def-btn" class="lect-tab-btn is-active" data-tab-group="baghouse" onclick="lectureTab(''baghouse'', ''baghouse-def'')">Definition</button></li>
    </ul>
    <div id="baghouse-def" class="lect-tab-panel is-active" data-tab-group="baghouse">
        <p>Baghouses are air pollution control devices that collect dust/particulate matter or gas before releasing emissions. Baghouses are also referred to as baghouse filters, bag filters, or fabric filters. They are found in power plants, steel mills, pharmaceutical producers, chemical producers, and other industrial companies. Properly designed baghouses effectively collect particulate matter, usually achieving a collection efficiency of 99% or better.</p>
        <p style="font-size:0.8rem;">Source: <a href="https://en.wikipedia.org/wiki/Baghouse" target="_blank" rel="noopener">Wikipedia</a></p>
    </div>
</div>

<h4>Bias</h4>
<a id="bias"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="bias-def-btn" class="lect-tab-btn is-active" data-tab-group="bias" onclick="lectureTab(''bias'', ''bias-def'')">Definition</button></li>
    </ul>
    <div id="bias-def" class="lect-tab-panel is-active" data-tab-group="bias">
        <p>For opacity readings, there are positive and negative biases. A positive bias is when an opacity reading exceeds the actual opacity. Positive biases typically occur due to the wrong position of the sun or the observer during readings, or on very bright days. Negative biases arise when the observation is performed with low-contrast backgrounds.</p>
    </div>
</div>

<h4>Clean Air Act</h4>
<a id="clean"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="clean-def-btn" class="lect-tab-btn is-active" data-tab-group="clean" onclick="lectureTab(''clean'', ''clean-def'')">Definition</button></li>
        <li><button type="button" id="clean-epa-btn" class="lect-tab-btn" data-tab-group="clean" onclick="lectureTab(''clean'', ''clean-epa'')">EPA Resources</button></li>
        <li><button type="button" id="clean-before-btn" class="lect-tab-btn" data-tab-group="clean" onclick="lectureTab(''clean'', ''clean-before'')">Before the Clean Air Act</button></li>
    </ul>
    <div id="clean-def" class="lect-tab-panel is-active" data-tab-group="clean">
        <p>The Clean Air Act (CAA) of 1970 is a comprehensive federal law that defines and regulates the amount of air emissions from stationary and mobile sources. Amendments were made to the law in 1977 and 1990. In the visible emissions industry, the Clean Air Act Compliance Monitoring program enforces the government emissions guidelines.</p>
        <p>The original CAA of 1970 established six (6) pollutants for which EPA would establish National Ambient Air Quality Standards (NAAQS). Method 9 and other methods had not been defined yet; industry had flexibility to meet the requirements. The pollutants were:</p>
        <ul>
            <li>Lead</li>
            <li>Carbon monoxide (CO)</li>
            <li>Nitrogen Oxide (NOx)</li>
            <li>Ozone</li>
            <li>Particulate matter with diameters of 10 micrometers or less (PM 10)</li>
            <li>Particulate matter with diameters of 2.5 micrometers or less (PM 2.5)</li>
            <li>Sulfur dioxide (SOx)</li>
        </ul>
        <p>The presence and regulation of these pollutants have evolved since 1970. Lead was eliminated by switching to unleaded gasoline. Additionally, particulate matter size standards were decreased because the capability to control emissions has increased. Emissions greater than 2.5 microns have been eliminated, while those below 2.5 microns are limited.</p>
        <p>Particulate matter is the cause of most visible emissions.</p>
    </div>
    <div id="clean-epa" class="lect-tab-panel" data-tab-group="clean">
        <h4>EPA Resources</h4>
        <ul>
            <li><a href="https://www.epa.gov/laws-regulations/summary-clean-air-act" target="_blank" rel="noopener">Summary of the Clean Air Act</a></li>
            <li><a href="https://www.epa.gov/compliance/clean-air-act-caa-compliance-monitoring" target="_blank" rel="noopener">Compliance Monitoring</a></li>
        </ul>
    </div>
    <div id="clean-before" class="lect-tab-panel" data-tab-group="clean">
        <h4>Birmingham, AL in the 1960s before the Clean Air Act</h4>
        <img src="/images/lecture/glossary/birmingham-1.jpg" alt="Pollution before the Clean Air Act" style="max-width:100%; border-radius:0.375rem; margin-bottom:0.5rem;">
        <img src="/images/lecture/glossary/birmingham-2.jpg" alt="US Air before the Clean Air Act" style="max-width:100%; border-radius:0.375rem; margin-bottom:0.5rem;">
        <img src="/images/lecture/glossary/birmingham-3.jpg" alt="Pollution before the EPA and Method 9" style="max-width:100%; border-radius:0.375rem;">
    </div>
</div>

<h4>Environmental Protection Agency (EPA)</h4>
<a id="epa"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="epa-def-btn" class="lect-tab-btn is-active" data-tab-group="epa" onclick="lectureTab(''epa'', ''epa-def'')">Definition</button></li>
        <li><button type="button" id="epa-links-btn" class="lect-tab-btn" data-tab-group="epa" onclick="lectureTab(''epa'', ''epa-links'')">Links</button></li>
    </ul>
    <div id="epa-def" class="lect-tab-panel is-active" data-tab-group="epa">
        <p>The EPA is an independent executive agency of the United States federal government tasked with environmental protection matters.</p>
    </div>
    <div id="epa-links" class="lect-tab-panel" data-tab-group="epa">
        <p><a href="https://en.wikipedia.org/wiki/United_States_Environmental_Protection_Agency" target="_blank" rel="noopener">Wikipedia page about EPA</a></p>
        <p><a href="https://www.epa.gov/" target="_blank" rel="noopener">EPA website</a></p>
        <p><a href="https://www.epa.gov/clean-air-act-overview" target="_blank" rel="noopener">EPA Clean Air Act</a></p>
        <p><a href="https://www.epa.gov/emc/method-9-visual-opacity" target="_blank" rel="noopener">EPA Method 9</a></p>
    </div>
</div>

<h4>Fugitive Emissions</h4>
<a id="fugitive"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="fug-def-btn" class="lect-tab-btn is-active" data-tab-group="fug" onclick="lectureTab(''fug'', ''fug-def'')">Definition</button></li>
        <li><button type="button" id="fug-links-btn" class="lect-tab-btn" data-tab-group="fug" onclick="lectureTab(''fug'', ''fug-links'')">Links</button></li>
    </ul>
    <div id="fug-def" class="lect-tab-panel is-active" data-tab-group="fug">
        <p>Fugitive emissions are non-stack emissions that escape during material transfer, from buildings containing a process, or directly from process equipment. Some examples include dust from unpaved roads, dust from grinding, crushing, and sandblasting operations, and dry material loading or unloading.</p>
        <p>Fugitive emissions are emissions generated by a facility that are not collected by a capture system and are released into the atmosphere. This includes emissions that (1) escape capture by process equipment exhaust hoods, (2) are emitted during material transfer, (3) are emitted from buildings housing material processing or handling equipment, or (4) are emitted directly from process equipment.</p>
    </div>
    <div id="fug-links" class="lect-tab-panel" data-tab-group="fug">
        <p><a href="https://www.epa.gov/sites/default/files/2019-08/documents/method_22_0.pdf" target="_blank" rel="noopener">EPA Method 22 Document</a></p>
        <p><a href="https://www.epa.gov/sites/default/files/2020-08/method22qa.doc" target="_blank" rel="noopener">EPA Method 22 Q/A Document</a></p>
    </div>
</div>

<h4>Luminous</h4>
<a id="luminous"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="lum-def-btn" class="lect-tab-btn is-active" data-tab-group="lum" onclick="lectureTab(''lum'', ''lum-def'')">Definition</button></li>
        <li><button type="button" id="lum-res-btn" class="lect-tab-btn" data-tab-group="lum" onclick="lectureTab(''lum'', ''lum-res'')">Resources</button></li>
    </ul>
    <div id="lum-def" class="lect-tab-panel is-active" data-tab-group="lum">
        <p>Objects that can emit light energy by themselves are known as luminous objects. Luminous objects are visible as they emit light on their own. Luminosity is an absolute measure of radiated electromagnetic power (light), the radiant power emitted by a light-emitting object.</p>
    </div>
    <div id="lum-res" class="lect-tab-panel" data-tab-group="lum">
        <h4>Resources</h4>
        <ul>
            <li><a href="https://en.wikipedia.org/wiki/Luminosity" target="_blank" rel="noopener">Wikipedia page</a></li>
        </ul>
    </div>
</div>

<h4>Method 9</h4>
<a id="method9"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="m9-def-btn" class="lect-tab-btn is-active" data-tab-group="m9" onclick="lectureTab(''m9'', ''m9-def'')">Definition</button></li>
        <li><button type="button" id="m9-epa-btn" class="lect-tab-btn" data-tab-group="m9" onclick="lectureTab(''m9'', ''m9-epa'')">EPA Resources</button></li>
        <li><button type="button" id="m9-tech-btn" class="lect-tab-btn" data-tab-group="m9" onclick="lectureTab(''m9'', ''m9-tech'')">Technical Info</button></li>
    </ul>
    <div id="m9-def" class="lect-tab-panel is-active" data-tab-group="m9">
        <p>Method 9 was implemented by the U.S. Environmental Protection Agency (EPA) to quantify the opacity of emissions from a plume. Method 9 was <a href="#prom">promulgated</a> in 1974 to regulate and control emissions that contribute to air pollution and is defined as <em>the visual determination of the opacity of emissions from stationary sources.</em></p>
        <p>Method 9 requires that readers are qualified (certified) by attending live smoke schools and completing the qualification testing. The student is certified by Method 9; certification requires the student to statistically meet the accuracy and reliability requirements specified in Method 9.</p>
    </div>
    <div id="m9-epa" class="lect-tab-panel" data-tab-group="m9">
        <h4>EPA Resources</h4>
        <ul>
            <li><a href="https://www.epa.gov/emc/method-9-visual-opacity" target="_blank" rel="noopener">EPA Method 9 page</a></li>
        </ul>
    </div>
    <div id="m9-tech" class="lect-tab-panel" data-tab-group="m9">
        <h4>Technical Information</h4>
        <p>Method 9 refers to 40 CFR 60, Appendix A, the EPA regulation that sets emission standards for U.S. industries.</p>
    </div>
</div>

<h4>Method 22</h4>
<a id="method22"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="m22-def-btn" class="lect-tab-btn is-active" data-tab-group="m22" onclick="lectureTab(''m22'', ''m22-def'')">Definition</button></li>
        <li><button type="button" id="m22-epa-btn" class="lect-tab-btn" data-tab-group="m22" onclick="lectureTab(''m22'', ''m22-epa'')">EPA Resources</button></li>
    </ul>
    <div id="m22-def" class="lect-tab-panel is-active" data-tab-group="m22">
        <p>Method 22 is defined by the EPA as <em>a simple procedure that uses the human eye to determine the total time an industrial activity causes visible emissions.</em> Method 22 is used to detect fugitive emissions which are usually caused by equipment failures, but can also be operations such as grinding or driving vehicles on dirt roads.</p>
        <p>Method 22 does not require certification like Method 9, but Method 22 readers should understand the concepts and principles behind Method 9.</p>
    </div>
    <div id="m22-epa" class="lect-tab-panel" data-tab-group="m22">
        <h4>EPA Resources</h4>
        <ul>
            <li><a href="https://www.epa.gov/emc/method-22-visual-determination-fugitive-emissions" target="_blank" rel="noopener">EPA Method 22 page</a></li>
            <li><a href="https://www.epa.gov/sites/default/files/2017-08/documents/method_22.pdf" target="_blank" rel="noopener">EPA Method 22 Document</a></li>
            <li><a href="https://www.epa.gov/sites/production/files/2020-08/method22qa.doc" target="_blank" rel="noopener">EPA Method 22 Q &amp; A PDF document</a></li>
        </ul>
    </div>
</div>

<h4>National Ambient Air Quality Standards (NAAQS)</h4>
<a id="naaqs"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="naaqs-def-btn" class="lect-tab-btn is-active" data-tab-group="naaqs" onclick="lectureTab(''naaqs'', ''naaqs-def'')">Definition</button></li>
        <li><button type="button" id="naaqs-res-btn" class="lect-tab-btn" data-tab-group="naaqs" onclick="lectureTab(''naaqs'', ''naaqs-res'')">Resources</button></li>
    </ul>
    <div id="naaqs-def" class="lect-tab-panel is-active" data-tab-group="naaqs">
        <p>National Ambient Air Quality Standards (NAAQS) are federal the EPA Clean Air Act requirements. These regulations control the pollutants known to harm human health and the environment and can cause property damage. The standards cover ground-level ozone, particulate matter, carbon monoxide, lead, sulfur dioxide, and nitrogen dioxide. NAAQS are also called Criteria Air Pollutants.</p>
    </div>
    <div id="naaqs-res" class="lect-tab-panel" data-tab-group="naaqs">
        <ul>
            <li><a href="https://www.epa.gov/criteria-air-pollutants" target="_blank" rel="noopener">EPA NAAQS web page</a></li>
        </ul>
    </div>
</div>

<h4>National Emissions Data System (NEDS)</h4>
<a id="neds"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="neds-def-btn" class="lect-tab-btn is-active" data-tab-group="neds" onclick="lectureTab(''neds'', ''neds-def'')">Definition</button></li>
    </ul>
    <div id="neds-def" class="lect-tab-panel is-active" data-tab-group="neds">
        <p>The National Emissions Data System (NEDS) is an automated data processing system used by the EPA to store data on the pollution sources.</p>
    </div>
</div>

<h4>New Source Performance Standards (NSPS)</h4>
<a id="nsps"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="nsps-def-btn" class="lect-tab-btn is-active" data-tab-group="nsps" onclick="lectureTab(''nsps'', ''nsps-def'')">Definition</button></li>
        <li><button type="button" id="nsps-res-btn" class="lect-tab-btn" data-tab-group="nsps" onclick="lectureTab(''nsps'', ''nsps-res'')">Resources</button></li>
    </ul>
    <div id="nsps-def" class="lect-tab-panel is-active" data-tab-group="nsps">
        <p>New Source Performance Standards are EPA standards for pollution control. The original Clean Air Act focused on new facility construction, and NSPS was developed for new operations or modifications within an existing facility. NSPS standards include replacing air pollution control devices and when an operation is modified by an aggregate of more than 50% of the original capital dollars spent to improve or extend the facility''s operational life.</p>
        <p>The NSPS defines the level of pollution that stationary sources can emit.</p>
        <p>NSPS originated as part of the 1970 Clean Air Act Extension amendments.</p>
    </div>
    <div id="nsps-res" class="lect-tab-panel" data-tab-group="nsps">
        <ul>
            <li><a href="https://en.wikipedia.org/wiki/New_Source_Performance_Standards" target="_blank" rel="noopener">Wikipedia</a></li>
            <li><a href="https://www.epa.gov/compliance/demonstrating-compliance-new-source-performance-standards-and-state-implementation-plans" target="_blank" rel="noopener">EPA NSPS Compliance web page</a></li>
            <li>Edison Electric Institute (EEI) New Source document</li>
        </ul>
    </div>
</div>

<h4>New Source Review (NSR) Permitting</h4>
<a id="nsr"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="nsr-def-btn" class="lect-tab-btn is-active" data-tab-group="nsr" onclick="lectureTab(''nsr'', ''nsr-def'')">Definition</button></li>
        <li><button type="button" id="nsr-res-btn" class="lect-tab-btn" data-tab-group="nsr" onclick="lectureTab(''nsr'', ''nsr-res'')">Resources</button></li>
    </ul>
    <div id="nsr-def" class="lect-tab-panel is-active" data-tab-group="nsr">
        <p>New Source Review (NSR) is a permit (legal document) that defines air quality requirements that facility owners and operators must follow. NSR permits specify what type of construction is allowed, emission limits, and the required frequency of operation. State or local governments typically issue NSR permits. The EPA may issue NSR permits in some cases.</p>
        <p>NSR originated as part of the 1977 Clean Air Act Extension amendments.</p>
    </div>
    <div id="nsr-res" class="lect-tab-panel" data-tab-group="nsr">
        <ul>
            <li><a href="https://en.wikipedia.org/wiki/New_Source_Review" target="_blank" rel="noopener">Wikipedia</a></li>
            <li><a href="https://www.epa.gov/nsr/learn-about-new-source-review" target="_blank" rel="noopener">EPA webpage on New Source Review permits</a></li>
            <li><a href="https://www.epa.gov/sites/default/files/2015-12/documents/nsrbasicsfactsheet103106.pdf" target="_blank" rel="noopener">EPA PDF outlining the basics of NSR</a></li>
        </ul>
    </div>
</div>

<h4>Notice of Violation</h4>
<a id="nov"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="nov-def-btn" class="lect-tab-btn is-active" data-tab-group="nov" onclick="lectureTab(''nov'', ''nov-def'')">Definition</button></li>
    </ul>
    <div id="nov-def" class="lect-tab-panel is-active" data-tab-group="nov">
        <p>A Notice of Violation (NOV) is issued by a local, state, or federal authority and alerts a facility that they are not in compliance with a required condition. It informs the organization that it has violated a regulation, federal, state, local law, or a permit condition. An NOV is the first step in an enforcement action and may be settled in the initial review or taken to civil court to resolve the violation. NOVs may result in civil monetary penalties, court-ordered actions, agreements to implement corrective actions (i.e., training), and criminal charges against individual parties.</p>
    </div>
</div>

<h4>Opacity</h4>
<a id="opacity"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="op-def-btn" class="lect-tab-btn is-active" data-tab-group="op" onclick="lectureTab(''op'', ''op-def'')">Definition</button></li>
        <li><button type="button" id="op-ex-btn" class="lect-tab-btn" data-tab-group="op" onclick="lectureTab(''op'', ''op-ex'')">Opacity Examples</button></li>
    </ul>
    <div id="op-def" class="lect-tab-panel is-active" data-tab-group="op">
        <p>For the visible emission industry, opacity is defined as the percentage of the background obscured or blocked by visible emissions. An emission that lets more light through (one can see more, rather than less, of the background) has a lower opacity.</p>
    </div>
    <div id="op-ex" class="lect-tab-panel" data-tab-group="op">
        <h4>Varying Degrees of Opacity</h4>
        <p>Approximate opacity values: 1-25%, 2-50%, 3-75%, 4-100%</p>
        <img src="/images/lecture/glossary/opacity-1.jpg" alt="Smoke Opacity" style="max-width:48%; border-radius:0.375rem; margin:0.25rem;">
        <img src="/images/lecture/glossary/opacity-2.jpg" alt="Smoke Opacity" style="max-width:48%; border-radius:0.375rem; margin:0.25rem;">
        <img src="/images/lecture/glossary/opacity-3.jpg" alt="Smoke Opacity" style="max-width:48%; border-radius:0.375rem; margin:0.25rem;">
        <img src="/images/lecture/glossary/opacity-4.jpg" alt="Smoke Opacity" style="max-width:48%; border-radius:0.375rem; margin:0.25rem;">
    </div>
</div>

<h4>Path Length</h4>
<a id="pathlength"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="path-def-btn" class="lect-tab-btn is-active" data-tab-group="path" onclick="lectureTab(''path'', ''path-def'')">Definition</button></li>
    </ul>
    <div id="path-def" class="lect-tab-panel is-active" data-tab-group="path">
        <p>Path length is the line of sight path length through the plume that is being observed.</p>
        <p>Method 9 states: The observer shall, as much as possible, make their observations from a position such that their line of sight is approximately perpendicular to the direction of plume travel. And when observing opacity of emissions from rectangular outlets (e.g. roof monitors, open baghouses, non-circular stacks, storage piles, fugitive sources that are not round), approximately perpendicular to the longer axis of the outlet (think across the street not down the road).</p>
        <p>As you move closer to a stack with a vertically rising emission, the vertical viewing angle formed by your line of sight increases. This causes the observed opacity to have a positive bias because your line of sight has a greater length of travel through the plume (greater than 1x the diameter). If your line of sight is greater than 18 degrees (3 stack heights - up or down relative to your position) from the perpendicular, a positive error greater than 1% occurs. As the vertical angle increases the error increases. To avoid this, best practice is that the observer should stand at least three (3) stack heights away from a vertically rising plume.</p>
    </div>
</div>

<h4>Point Source Emissions</h4>
<a id="point"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="pt-def-btn" class="lect-tab-btn is-active" data-tab-group="pt" onclick="lectureTab(''pt'', ''pt-def'')">Definition</button></li>
    </ul>
    <div id="pt-def" class="lect-tab-panel is-active" data-tab-group="pt">
        <p>Point source emissions are generated by stationary sources and are localized. Examples of point source emissions generators are smokestacks and chimneys. Major point source emissions are found at power plants, factories, refineries, and foundries. Point sources are specified with longitude, latitude, and elevation.</p>
    </div>
</div>

<h4>Promulgate</h4>
<a id="prom"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="prom-def-btn" class="lect-tab-btn is-active" data-tab-group="prom" onclick="lectureTab(''prom'', ''prom-def'')">Definition</button></li>
    </ul>
    <div id="prom-def" class="lect-tab-panel is-active" data-tab-group="prom">
        <p>Promulgation is being made public, announced, published, formalized, etc. The EPA promulgated Method 9 in 1974.</p>
        <p>Promulgation of federal, state, and local regulations follows specific procedures and includes a period for public comments, which are addressed in the final rule.</p>
    </div>
</div>

<h4>Quick Check</h4>
<a id="quick"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="qc-def-btn" class="lect-tab-btn is-active" data-tab-group="qc" onclick="lectureTab(''qc'', ''qc-def'')">Definition</button></li>
        <li><button type="button" id="qc-doc-btn" class="lect-tab-btn" data-tab-group="qc" onclick="lectureTab(''qc'', ''qc-doc'')">Quick Check Document</button></li>
    </ul>
    <div id="qc-def" class="lect-tab-panel is-active" data-tab-group="qc">
        <p>The quick check method is used to observe facility emission sources to detect if a visible emission is present. CAA, Inc. defines Quick Checks as observations made when an air quality permit requires daily observations to determine if there are any visible emissions, but no method has been specified.</p>
        <p>Quick Checks are a momentary glance at the stack to determine the presence or lack of visible emissions. If an emission is detected, then official Method observations must be completed.</p>
    </div>
    <div id="qc-doc" class="lect-tab-panel" data-tab-group="qc">
        <img src="/images/lecture/glossary/emissions-quick-check.jpg" alt="Smoke Opacity" style="max-width:100%; border-radius:0.375rem;">
    </div>
</div>

<h4>Smoke School</h4>
<a id="ss"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="ss-def-btn" class="lect-tab-btn is-active" data-tab-group="ss" onclick="lectureTab(''ss'', ''ss-def'')">Definition</button></li>
    </ul>
    <div id="ss-def" class="lect-tab-panel is-active" data-tab-group="ss">
        <p>Smoke schools teach the science and skill of making visible emission observations (VEO) from emission sources. Smoke schools have two elements: lecture instruction and field certification. A certificate is issued upon successful completion of lecture and field sessions.</p>
        <p>Lecture instruction provides the building blocks of VEO - the principles behind reading opacity, the hows and whys.</p>
        <p>Field qualification testing teaches students to read the opacity of smoke and ascertains that the observer is certified in accordance with the requirements of EPA Method 9. Field training certifies that the student is statistically accurate and reliable enough to make observations as defined by Method 9.</p>
    </div>
</div>

<h4>State Implementation Plan (SIP)</h4>
<a id="sip"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="sip-def-btn" class="lect-tab-btn is-active" data-tab-group="sip" onclick="lectureTab(''sip'', ''sip-def'')">Definition</button></li>
        <li><button type="button" id="sip-res-btn" class="lect-tab-btn" data-tab-group="sip" onclick="lectureTab(''sip'', ''sip-res'')">Resources</button></li>
    </ul>
    <div id="sip-def" class="lect-tab-panel is-active" data-tab-group="sip">
        <p>State Implementation Plans (SIP) are air pollution regulations and documents used by states, territories, or local air districts.</p>
        <p>The EPA (federal) defines the National Ambient Air Quality Standards (NAAQS). These standards are adopted by states and territories of the U.S. as a part of their SIP. Local jurisdictions add additional requirements if needed to meet the local air quality guidelines. The federal EPA must approve SIPs.</p>
    </div>
    <div id="sip-res" class="lect-tab-panel" data-tab-group="sip">
        <p><a href="https://www.epa.gov/sips/basic-information-air-quality-sips" target="_blank" rel="noopener">EPA SIP information</a></p>
        <p><a href="https://www.epa.gov/air-quality-implementation-plans/sip-requirements-clean-air-act" target="_blank" rel="noopener">EPA SIP requirements</a></p>
    </div>
</div>

<h4>Title V</h4>
<a id="titlev"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="tv-def-btn" class="lect-tab-btn is-active" data-tab-group="tv" onclick="lectureTab(''tv'', ''tv-def'')">Definition</button></li>
        <li><button type="button" id="tv-res-btn" class="lect-tab-btn" data-tab-group="tv" onclick="lectureTab(''tv'', ''tv-res'')">Resources</button></li>
    </ul>
    <div id="tv-def" class="lect-tab-panel is-active" data-tab-group="tv">
        <p>Title V is part of the EPA''s Clean Air Act that requires facilities with major sources of air pollutants to have an operating permit, operate in compliance with the permit, and certify their compliance with the requirements of the permit. Title V was one of the 1990 Clean Air Act Amendments.</p>
        <p>Title V resources are managed in the U.S. through the EPA''s ten regional offices.</p>
    </div>
    <div id="tv-res" class="lect-tab-panel" data-tab-group="tv">
        <p><a href="https://www.epa.gov/title-v-operating-permits" target="_blank" rel="noopener">EPA Title V web page</a></p>
        <p><a href="https://www.epa.gov/sites/production/files/2020-01/documents/petitionsrule_final_factsheet.pdf" target="_blank" rel="noopener">EPA Title V Fact Sheet</a></p>
        <p><a href="https://www.epa.gov/title-v-operating-permits/who-has-obtain-title-v-permit" target="_blank" rel="noopener">Who has to obtain a Title V Permit?</a></p>
    </div>
</div>

<h4>Visible Emissions</h4>
<a id="visible-emissions"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="ve-def-btn" class="lect-tab-btn is-active" data-tab-group="ve" onclick="lectureTab(''ve'', ''ve-def'')">Definition</button></li>
        <li><button type="button" id="ve-pt-btn" class="lect-tab-btn" data-tab-group="ve" onclick="lectureTab(''ve'', ''ve-pt'')">Point Source Emissions</button></li>
        <li><button type="button" id="ve-fug-btn" class="lect-tab-btn" data-tab-group="ve" onclick="lectureTab(''ve'', ''ve-fug'')">Fugitive Emissions</button></li>
    </ul>
    <div id="ve-def" class="lect-tab-panel is-active" data-tab-group="ve">
        <p>Visible emissions are emissions that the human eye can see. Visible emissions may include gas, vapor, and particulate matter. There are two types of emissions: point source emissions, including smokestacks and chimneys, or fugitive emissions, which are typically unintended releases, due to sanding, driving on a dirt road, or similar activities. These emissions types are defined and regulated by the Environmental Protection Agency (EPA) and have defined observation methods. Generally, Method 9 is used for point source emissions, and Method 22 for fugitive emissions.</p>
        <p>Additionally, Method 9 can be used for fugitive emissions, and Methods 203A, 203B, 203B, and specific methods, including Tennessee Method 1, are used in special cases or where local regulations apply.</p>
    </div>
    <div id="ve-pt" class="lect-tab-panel" data-tab-group="ve">
        <h4>Examples of Point Source Emissions</h4>
        <img src="/images/lecture/glossary/vapor-plume.jpg" alt="Vapor Plume" style="max-width:48%; border-radius:0.375rem; margin:0.25rem;">
        <img src="/images/lecture/glossary/visible-emissions-chimney.jpg" alt="Point Source Emission" style="max-width:48%; border-radius:0.375rem; margin:0.25rem;">
    </div>
    <div id="ve-fug" class="lect-tab-panel" data-tab-group="ve">
        <h4>Examples of Fugitive Emissions</h4>
        <p>Fugitive emissions can come from dusty roads, grinding, sanding, but most fugitive emissions are a result of unintended emissions from faulty equipment in industry.</p>
        <img src="/images/lecture/glossary/fugitive-emission-1.jpg" alt="Fugitive emission" style="max-width:100%; border-radius:0.375rem;">
    </div>
</div>

<h4>Visible Emissions Observations (VEO)</h4>
<a id="veo"></a>
<div class="lect-tabs">
    <ul class="lect-tab-nav">
        <li><button type="button" id="veo-def-btn" class="lect-tab-btn is-active" data-tab-group="veodef" onclick="lectureTab(''veodef'', ''veo-def'')">Definition</button></li>
    </ul>
    <div id="veo-def" class="lect-tab-panel is-active" data-tab-group="veodef">
        <p>Visible emissions observations (VEO) are performed to determine the opacity of emissions from a stationary source, such as a smokestack. Visible emissions observations may also be called reading smoke, opacity reading, Method 9 observations, or Method 9 readings. The EPA Clean Air Act of 1990 requires facilities to demonstrate continual compliance with regulations that control air pollution. Regular visible emission readings can often be used as a demonstration of compliance.</p>
        <p>Visible emissions observations may take the form of a quick check, Method 22, Method 203 A, B, C, or a specific state or local requirement, such as Tennessee VE Method 1. Visible emissions observations are documented with required elements to enable experts to interpret the observations.</p>
    </div>
</div>',
    2
)
ON CONFLICT (slug) DO UPDATE SET content = EXCLUDED.content;
