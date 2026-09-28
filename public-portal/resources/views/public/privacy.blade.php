@extends('layouts.app')

@section('title', 'Compliance Assurance Smoke School Privacy Policy')

{{--
    Michael, 2026-09-27 -- Privacy Policy, text verbatim from the live
    privacy.php. The "PDF of May 2025 Privacy Policy" link only renders once
    that PDF is added under public/PDFs. (Checked for this rebuild: the new
    server is in Hostinger's Phoenix, AZ data center, so the "servers located
    in the U.S." statement still holds.)
--}}
@php
    $privacyPdf = 'PDFs/123025xx-Privacy-Policy.pdf';
@endphp

@push('styles')
<style>
    .legal h2 { font-size: 1.1rem; font-weight: 500; color: #555; margin-top: 0.25rem; }
    .legal p, .legal li { line-height: 1.6; }
    .legal h4 { font-size: 18px; font-weight: 600; color: #444; margin: 1.5rem 0 0.5rem; }
    .legal ul { margin: 0 0 1rem 1.5rem; padding-left: 0; }
    .legal li { margin-bottom: 0.5rem; }
    .legal-photo { width: 100%; max-width: 20rem; border-radius: 0.5rem; margin: 1.5rem 0; }
</style>
@endpush

@section('content')
<div class="legal">
    <h1>Privacy Policy</h1>
    <h2>How Compliance Assurance Associates, Inc. collects, uses, and protects personal information</h2>

    <p>Compliance Assurance Associates, Inc. (CAA) recognizes that privacy is important. This Policy applies to all of the products and services offered by its subsidiaries or affiliated companies. CAA complies with U.S. laws regarding privacy and data integrity, and seeks in all cases to restrict the need for personal information.</p>

    <p>If you have questions or concerns regarding privacy matters on this website, email <a href="mailto:Joe.Spivey@compliance-assurance.com">Joe Spivey</a> or write to:<br>
    Privacy Matters | Compliance Assurance Associates, Inc. | 682 Orvil Smith Rd. | Harvest, AL 35749</p>

    @if (file_exists(public_path($privacyPdf)))
        <p><a href="/{{ $privacyPdf }}" title="Compliance Assurance Privacy and Data Retention Policy" target="_blank">PDF of May 2025 Privacy Policy &raquo;</a></p>
    @endif

    @if (file_exists(public_path('images/home-page/privacy-policy.jpg')))
        <img src="/images/home-page/privacy-policy.jpg" alt="Privacy policy" class="legal-photo">
    @endif

    <h3>Information We Collect and How We Use It</h3>

    <h4>Information you provide</h4>
    <p>When you sign up for a CAA account or other CAA service or promotion that requires registration, we ask you for personal information (such as your name, email address, and an account password).</p>
    <p>We may combine the information you submit under your account with information from other CAA services or third parties to provide you with a better user experience. Some services may provide the opportunity to opt out of combining such information.</p>
    <p>Payment processing is secure and performed by a third-party vendor.</p>

    <h4>User communications</h4>
    <p>CAA may retain emails or other communications you send us. We use this information to process your inquiries, respond to your requests, and enhance our services.</p>

    <h4>Affiliated sites</h4>
    <p>CAA may offer services in connection with other websites. Personal information you provide to those sites may be sent to CAA for service delivery. We handle this information in accordance with our privacy policy. Affiliated sites may have different privacy practices, and we recommend reviewing their individual privacy policies.</p>

    <h4>Other websites</h4>
    <p>This privacy policy applies only to websites and services that are owned and operated by CAA.</p>

    <p>CAA only processes personal information for the purposes described in the applicable privacy policy and/or privacy notice for specific services. In addition to the above, such purposes include:</p>
    <ul>
        <li>Providing our products and services to users, enrollment, and notification of visible emissions or other applicable training events.</li>
        <li>Auditing, research, and analysis to maintain, protect, and improve our services.</li>
        <li>Ensuring the technical functioning of our network.</li>
        <li>Developing new services.</li>
    </ul>

    <p>CAA processes personal information on servers located in the U.S.</p>

    <h4>Choices for personal information</h4>
    <p>When you sign up for a particular service that requires registration, we ask you to provide personal information. If we use this information in a manner different than the purpose for which it was collected, we will ask for your consent prior to such use. Our purpose is to track your certification records and provide timely notice about recertification and other issues related to your certification.</p>
    <p>If CAA proposes to use personal information for purposes other than those described in this policy and/or in the specific service notices, we will offer you an effective way to opt out of the use of personal information for those other purposes.</p>
    <p>You can decline to submit personal information to our services. In such cases, CAA may not be able to provide those services to you through an online interface. However, we will be glad to continue servicing your needs via postal services.</p>

    <h4>Information sharing</h4>
    <p>CAA only shares personal information with other companies or individuals outside of CAA in the following circumstances:</p>
    <ul>
        <li>We have your consent. We require consent for the sharing of sensitive personal information.</li>
        <li>We provide such information to our subsidiaries, affiliated companies, or other trusted businesses or persons for the purpose of processing personal information on our behalf. We require that these parties agree to process such information based on our instructions and in compliance with this policy and appropriate confidentiality and security measures.</li>
        <li>We have a good faith belief that access, use, preservation, or disclosure of such information is reasonably necessary to (a) satisfy applicable law, regulation, legal process, or enforceable governmental request, (b) enforce applicable Terms and Conditions, including investigation of potential violations thereof, (c) detect, prevent, or otherwise address fraud, security, or technical issues, or (d) protect against imminent harm to the rights, property, or safety of CAA staff, its users, or the public as required or permitted by law.</li>
    </ul>
    <p>If CAA becomes involved in a merger, acquisition, or any form of sale of some or all of its assets, we will provide notice before personal information is transferred and becomes subject to a different privacy policy.</p>

    <h4>Information security</h4>
    <p>We take appropriate security measures to protect against unauthorized access to our data, or unauthorized alteration, disclosure, or destruction of data. These include internal reviews of our data collection, storage, and processing practices and security measures, as well as physical security measures to guard against unauthorized access to systems where we store personal data.</p>
    <p>We limit access to personal information to CAA employees, contractors, and agents who need to know that information to operate, develop, or improve our services. These individuals are bound by confidentiality obligations and may be subject to discipline, including termination and criminal prosecution, if they fail to meet these obligations.</p>

    <h4>Data integrity</h4>
    <p>CAA processes personal information only for the purposes for which it was collected and in accordance with this policy or applicable service-specific privacy notices. We review our data collection, storage, and processing practices to ensure that we only collect, store, and process the personal information needed to provide or improve our services.</p>
    <p>CAA takes reasonable steps to ensure that the personal information we process is accurate, complete, and current, but we depend on our users to update or correct their personal information whenever necessary.</p>

    <h4>Accessing and updating personal information</h4>
    <p>CAA strives to provide you with access to your personal information when you use our services. You may request corrections to inaccurate data or ask for its deletion, unless we are required to retain it for legal or legitimate business purposes. To process these requests, we need users to identify themselves and specify the information in question.</p>
    <p>We may decline requests that are:</p>
    <ul>
        <li>Excessively repetitive or systematic.</li>
        <li>Technically challenging.</li>
        <li>Potentially compromising to others' privacy.</li>
        <li>Impractical (e.g., involving backup data).</li>
    </ul>

    <h4>Enforcement</h4>
    <p>CAA regularly reviews its compliance with this policy. Please direct questions or concerns regarding this policy or CAA's treatment of personal information by contacting Joe Spivey through this website or by writing to us at:<br>
    Privacy Matters | Compliance Assurance Associates, Inc. | 682 Orvil Smith Rd. | Harvest, AL 35749</p>
    <p>CAA's policy is to contact users regarding their concerns when we receive formal written complaints. For unresolved complaints about personal data transfer, we will cooperate with appropriate regulatory authorities, including local data protection agencies, to reach a resolution.</p>

    <h4>Changes to this policy</h4>
    <p>This privacy policy may change over time. We will not reduce your rights under this policy without explicit consent, and we anticipate most changes will be minor. We will post all policy changes on this page. For significant changes, we may provide more prominent notice, including email notifications. Each version of this policy will be identified at the top of the page by its effective date.</p>
    <p>If you have questions or concerns regarding privacy matters on this website, email <a href="mailto:Joe.Spivey@compliance-assurance.com">Joe Spivey</a> or write to:<br>
    Privacy Matters<br>
    Compliance Assurance Associates, Inc.<br>
    682 Orvil Smith Rd.<br>
    Harvest, AL 35749</p>
</div>
@endsection
