@extends('layouts.app')

@section('title', 'Terms and Conditions for Compliance Assurance Associates, Inc.')

{{--
    Michael, 2026-09-27 -- Terms and Conditions, legal text verbatim from the
    live terms.php. Departures: links to the privacy policy point at our own
    Privacy page once it exists (the live site's otherwise); the side image
    and the two PDF links only render once those files are added under
    public/images and public/PDFs, so nothing shows broken meanwhile.
--}}
@php
    $privacyUrl = Route::has('public.privacy') ? route('public.privacy') : 'https://compliance-assurance.com/privacy.php';
    $termsPdf = 'PDFs/12302402-Terms-of-Use.pdf';
    $eulaPdf = 'PDFs/123037xx-Virtual-Opacity-EULA.pdf';
    $hasImage = file_exists(public_path('images/home-page/terms-and-conditions.jpg'));
@endphp

@push('styles')
<style>
    .legal h2 { font-size: 1.1rem; font-weight: 500; color: #555; margin-top: 0.25rem; }
    .legal-intro { display: flex; flex-wrap: wrap; gap: 2.5rem; align-items: flex-start; margin: 1.5rem 0 2rem; }
    .legal-intro-side { flex: 0 0 260px; max-width: 100%; }
    .legal-intro-side img { width: 100%; margin-bottom: 1rem; }
    .legal-intro-main { flex: 1 1 320px; }
    .legal-intro-main h3 { margin-top: 0; }
    .legal p, .legal li { line-height: 1.6; }
    .legal h5 { font-size: 1rem; margin: 1.25rem 0 0.25rem; }
</style>
@endpush

@section('content')
<div class="legal">
    <h1>Terms and conditions</h1>
    <h2>Terms and conditions for website, smoke schools, and VirtualOpacity<sup>&reg;</sup></h2>

    <div class="legal-intro">
        @if ($hasImage || file_exists(public_path($termsPdf)) || file_exists(public_path($eulaPdf)))
            <div class="legal-intro-side">
                @if ($hasImage)
                    <img src="/images/home-page/terms-and-conditions.jpg" alt="Terms and conditions">
                @endif
                @if (file_exists(public_path($termsPdf)))
                    <p><a href="/{{ $termsPdf }}" title="Smoke school terms and conditions" target="_blank">Website and smoke school terms and conditions PDF &raquo;</a></p>
                @endif
                @if (file_exists(public_path($eulaPdf)))
                    <p><a href="/{{ $eulaPdf }}" title="Method 9 VR terms and conditions" target="_blank">VirtualOpacity<sup>&reg;</sup> end user license agreement (EULA) PDF &raquo;</a></p>
                @endif
            </div>
        @endif

        <div class="legal-intro-main">
            <h3>Website use and Method 9 in-person training</h3>
            <p><a href="#eula">Jump to EULA for VirtualOpacity<sup>&reg;</sup> &raquo;</a></p>
            <p>Please carefully read these Terms of Use ("Agreement"). This Agreement has been prepared as a legally binding agreement between you ("you" or "your") and Compliance Assurance Associates, Inc., dba CAA, Inc., together with any subsidiaries and affiliates, ("CAA" "we," or "us") for the https://compliance-assurance.com website and all associated pages (the "Site") and for in-person Method 9 training events.</p>
            <p>Your use of the Site is only on the condition that you agree to abide by the terms contained in this Agreement. By accessing and using the Site, you agree to be bound by the terms and conditions of this Agreement. DO NOT ACCESS THE SITE IN ANY WAY IF YOU DO NOT AGREE TO THESE TERMS OF USE. THIS AGREEMENT CONTAINS AN ARBITRATION REQUIREMENT AND CLASS ACTION WAIVER. PLEASE SEE SECTION 15 FOR DETAILS.</p>
        </div>
    </div>

    <p><strong>1. Changes to agreement</strong><br>
    CAA reserves the right to change or modify this Agreement or any additional terms at its sole discretion at any time. If you do not agree to the modified terms of the Agreement for the Site, you should discontinue your use of the Site. Therefore, it is important that you read this page regularly to ensure you are familiar with the most updated Agreement. UNLESS OTHERWISE STATED IN THE AMENDED VERSION OF THIS AGREEMENT, ANY CHANGES TO THIS AGREEMENT WILL APPLY IMMEDIATELY UPON POSTING. Although we are not obligated to provide you with notice of any changes, any changes to this Agreement will not apply retroactively to events that occurred prior to such changes. Your continued use of this Site will constitute your agreement to any new provisions within the revised Agreement.</p>

    <p><strong>2. Your license to access this site</strong><br>
    The contents of the Site, and the Site as a whole, are intended to provide information and delivery of CAA's various products and services, as set forth and defined by us and subject to change by us at any time, in our sole discretion. All written content prepared and posted by us, and the Site design, layout, look, appearance, and graphics on the Site, as well as the trademarks, service marks, and logos contained on our Site (collectively, "CAA Content") are owned by or licensed to us and are subject to copyright, trademark, and other intellectual property rights under the United States and foreign laws and international conventions. We reserve all rights not expressly granted in, and to, the Site and the CAA Content. Except as otherwise provided in this Agreement, no part of the Site and no CAA Content may be copied, reproduced, uploaded, posted, publicly displayed, transmitted, or distributed in any way to any other person, corporation, entity, computer, server, website, or other medium for dissemination, publication or distribution or for any commercial use without our prior express written consent. On the condition that you comply with all your obligations under this Agreement, CAA grants you a limited, revocable, non-exclusive, non-transferable, non-sublicensable license to access the Site. Any use of the Site in excess of this license is strictly prohibited and constitutes a violation of this Agreement, which may result in the termination of your right to access and use this Site. Your access to this Site is provided on a temporary basis with no guarantee for future availability. We reserve the right to withdraw or modify any content or products we provide on the Site without notice.</p>

    <p><strong>3. Restrictions on participation</strong><br>
    Do not misuse the Site or any of the content provided on the Site. You may not interfere with the presentation or delivery of the content on the Site or try to access the Site using any method other than the interface and the instructions that we provide. ALL material contained on the Site has been copyrighted by CAA and any use is strictly prohibited without prior consent of CAA. As a condition of accessing the Site, you agree not to (a) reproduce, duplicate, copy, sell, resell or exploit any portion of the Site other than as expressly allowed under this Agreement; (b) use CAA's name, trademarks, server or other materials in connection with, or to transmit, any unsolicited communications or emails; (c) use any high-volume, automated or electronic means to access the Site (including without limitation, robots, spiders, scripts or web-scraping tools); (d) frame the Site (unless explicitly licensed to do so), place pop-up windows over its pages or otherwise affect the display of its page; (e) use any manual process to monitor or copy any of the material on the Site or for any other unauthorized purpose; (f) introduce any viruses, Trojan horses, worms, logic bombs or other material which is malicious or technologically harmful; or (g) otherwise interfere with or disrupt the Site or servers or networks connected to the Site, or disobey any requirements, procedures, policies, or regulations of networks connected to the Site.</p>

    <p><strong>4. User content</strong><br>
    Some features of the Site may allow you to provide content to the Site, such as written comments. This content may be shared publicly on the Site or by other means. Therefore, all content submitted by you to the Site may be retained by us for as long as we require it in relation to the purposes set out in the Privacy Policy, which can be <a href="{{ $privacyUrl }}" title="Compliance Assurance privacy policy">viewed here</a>, subject to state law, even after you terminate use. We may continue to disclose such content to third parties, as described in the Privacy Policy. You agree to use the Site in accordance with all applicable laws. You agree that you will not use the Site for organized partisan political activities. You also agree that you will not e-mail or post any of the following content ("Prohibited Content") anywhere on the Site: Content that defames, harasses, or threatens others; content that exploits, harms, or attempts to exploit or harm minors in any way by exposing them to inappropriate content, asking for personally identifiable information, or otherwise; content that discusses illegal activities with the intent to commit such activities, or encourages others to commit such activities; content that infringes or misappropriates another's intellectual property rights, including, but not limited to, copyrights, trademarks, patents, or trade secrets; content that you do not have the right to disclose under contractual confidentiality obligations or fiduciary duties; material that contains obscene (i.e., pornographic) language or images; advertising, promotional materials, or any form of commercial solicitation; content that impersonates or attempts to impersonate another user, person, or entity (including, without limitation, the use of e-mail addresses associated with any of the foregoing); content that otherwise harms other users or visitors to the Sites; content that is otherwise unlawful or that violates any applicable local, state, national or international law. Although CAA will not routinely screen or monitor content posted by users to the Site, CAA reserves the right to remove Prohibited Content of which it becomes aware (though it is under no obligation to do so).</p>

    <p><strong>5. Posting content on our site</strong><br>
    You are responsible for any content you post to the Site and the consequences of sharing or publishing such content with others or the general public. This includes, for example, any personal information, such as your home address, the home address of others, or your current location. WE ARE NOT RESPONSIBLE FOR THE CONSEQUENCES OF SHARING OR POSTING ANY PERSONAL OR OTHER INFORMATION ON OUR SITE. You may not post content intended to provide professional advice, including the provision of medical treatment, legal advice, or investment advice. You may not post content to solicit, recommend, endorse, or offer to buy or sell any securities or other financial instruments, tout stocks, or recommend that any particular security, portfolio of securities, transaction or investment strategy is suitable for you or any specific person. Except as otherwise provided in this Agreement, you or the owner of any content that you post to our Site retain ownership of all rights, title, and interests in that content. However, by posting content on the Site, you grant us and our assigns, agents, and licensees the irrevocable, royalty free, perpetual, worldwide right and license to use, reproduce, modify, display, remix, perform, distribute, redistribute, adapt, promote, create derivative works, and syndicate this content in any medium and through any form of technology or distribution. We own all rights, title, and interests in any compilation, collective work or other derivative work created by us using or incorporating your content (but not your original content).</p>

    <p><strong>6. Notice of copyright infringement</strong><br>
    We respect the rights of all copyright holders, and we have adopted and implemented a policy that provides for the termination in appropriate circumstances of users who infringe the rights of copyright holders. If you believe that your work has been copied in a way that constitutes copyright infringement, please provide our designated copyright agent with the following information: identification of the copyrighted work claimed to have been infringed, or, if multiple copyrighted works at a single online site are covered by a single notification, a representative list of such works at that site; identification of the material that is claimed to be infringing or to be the subject of infringing activity and that is to be removed or access to which is to be disabled, and information reasonably sufficient to permit us to locate the material; information reasonably sufficient to permit us to contact the complaining party; a statement that the complaining party has a good-faith belief that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law; a statement that the information in the notification is accurate, and under penalty of perjury, that the complaining party is authorized to act on behalf of the owner of an exclusive right that is allegedly infringed; and a physical or electronic signature of a person authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.</p>

    <p>Our copyright agent for notice of claims of infringement on the Site is:<br>
    Compliance Assurance Associates, Inc.<br>
    682 Orvil Smith Rd, Harvest, AL 35749<br>
    <a href="https://compliance-assurance.com/home-page.php" title="Home page" target="_blank" rel="noopener">https://compliance-assurance.com/home-page.php</a></p>

    <p>This contact information is only for suspected copyright infringement. We will remove any content that infringes upon the copyright of any person under the laws of the United States upon receipt of such a statement (or any statement in conformance with 17 U.S.C. § 512(c)(3)), and will terminate the access privileges of those who repeatedly infringe on the copyright of others. United States law imposes substantial penalties for falsely submitting a notice of copyright infringement.</p>

    <p><strong>7. Links, frames, and metatags</strong><br>
    You may link to the home page of the Site for non-commercial purposes as long as the link does not cast us in a false or misleading light. This limited right may be revoked at any time, subject to our sole discretion. You may not frame the content of the Site, unless specifically licensed to do so. You may not use metatags or any other "hidden text" that incorporates CAA trademarks or name without our express prior written consent.</p>

    <p><strong>8. Links to other websites</strong><br>
    The Site may contain links to other websites, including other CAA websites, but also to third-party websites that we do not control. These other websites may provide opinions, recommendations, or other information from various individuals, organizations, or companies. We are not responsible for the nature, quality, or accuracy of the content or opinions expressed on such websites and we do not investigate, monitor, or check them for quality, accuracy, or completeness. Inclusion of any linked website on the Site does not imply or express an approval or endorsement of the linked website by us or of any of the content, opinions, treatments, information, products, or services provided on these websites. This Agreement and the Privacy Policy do not apply to these other websites. Be sure to read the user agreements and privacy policies that govern your use of these other websites.</p>

    <p><strong>9. Your privacy; protection of your account credentials</strong><br>
    The CAA Privacy Policy, which can be <a href="{{ $privacyUrl }}">viewed here</a>, describes how we collect and use personal information about you through the Site. You are responsible for protecting your account log-in credentials from unauthorized access and use. You must promptly notify us by email at info@compliance-assurance.com of any known or suspected unauthorized use(s) of your account.</p>

    <p><strong>10. System availability and errors</strong><br>
    You must provide the equipment and Internet connections necessary to access the Site at your own expense. We do not guarantee that the Site will operate with your computer, mobile device, internet service plans, or mobile provider service plans or with any particular computer or other piece of hardware, software, equipment, or device you install on or used with your computer. There may be times when the Site is unavailable due to technical errors or for maintenance and support activities. We do not represent, warrant, or guarantee that the Site will always be available or is completely free of human or technological errors. The Site may contain typographical mistakes, inaccuracies, or omissions and some information may not be complete or current. We expressly reserve the right to correct any errors, inaccuracies, or omissions and to change or update information at any time without prior notice. We do not make any representation or warranty concerning errors, omissions, delays, or defects in the Site or any information supplied to you via the Site, or that files available through Site are free of viruses, worms, Trojan horses, or other code that include or manifest contaminating or destructive characteristics.</p>

    <p><strong>11. Disclaimer</strong><br>
    CAA will host and provide the Site using a commercially reasonable level of skill and care. That said, THE SITE AND ANY INFORMATION, PRODUCTS, OR SERVICES THEREIN ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION, ANY IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT. CAA DOES NOT WARRANT, AND HEREBY DISCLAIMS ANY WARRANTIES, EITHER EXPRESS OR IMPLIED, WITH RESPECT TO THE ACCURACY, ADEQUACY, OR COMPLETENESS OF THE SITE, INFORMATION OBTAINED FROM A SITE, OR LINK TO A SITE. CAA DOES NOT WARRANT THAT THE SITE WILL OPERATE IN AN UNINTERRUPTED OR ERROR-FREE MANNER OR THAT THE SITE IS FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS. WITHOUT LIMITING THE FOREGOING, CAA DOES NOT WARRANT THAT (A) THE SITE WILL MEET YOUR REQUIREMENTS OR EXPECTATIONS OR ACHIEVE THE INTENDED PURPOSES, (B) THE SITE WILL NOT EXPERIENCE OUTAGES OR OTHERWISE BE UNINTERRUPTED, TIMELY, SECURE, OR ERROR-FREE, (C) THE INFORMATION OR SERVICES OBTAINED THROUGH OR FROM THE SITE WILL BE ACCURATE, COMPLETE, CURRENT, ERROR-FREE, COMPLETELY SECURE, OR RELIABLE, OR (D) THAT DEFECTS IN OR ON THE SITE WILL BE CORRECTED. CAA DOES NOT MAKE ANY REPRESENTATION REGARDING YOUR ABILITY TO TRANSMIT AND RECEIVE INFORMATION FROM OR THROUGH THE SITE, AND YOU AGREE AND ACKNOWLEDGE THAT YOUR ABILITY TO ACCESS THE SITE MAY BE IMPAIRED. CAA DISCLAIMS ANY AND ALL LIABILITY RESULTING FROM OR RELATED TO SUCH EVENTS OR THE ACCESS OR USE OF THE SITE OR ANY INFORMATION OR SERVICES RELATED TO IT. YOU ACKNOWLEDGE AND AGREE THAT ANY ACCESS TO OR USE OF THE SITE OR ANY INFORMATION OR SERVICES PROVIDED THEREON IS AT YOUR OWN RISK.</p>

    <p><strong>12. Liability limitation</strong><br>
    EXCEPT AS PROHIBITED BY LAW, YOU AGREE THAT CAA WILL NOT BE LIABLE TO YOU FOR ANY LOSS OR DAMAGES ARISING OUT OF OR RELATING TO THIS AGREEMENT OR YOUR (OR ANY THIRD PARTY'S) USE OR INABILITY TO USE THE SITE, DATA LOSS, YOUR PLACEMENT OF CONTENT ON A SITE, YOUR RELIANCE UPON INFORMATION OBTAINED FROM OR THROUGH THE SITE, OR ANY OTHER POTENTIAL CLAIMS RELATED TO THE SITE. EXCEPT AS PROHIBITED BY LAW, CAA WILL NOT HAVE LIABILITY FOR ANY CONSEQUENTIAL, INDIRECT, PUNITIVE, SPECIAL, OR INCIDENTAL DAMAGES, WHETHER FORESEEABLE OR UNFORESEEABLE (INCLUDING, BUT NOT LIMITED TO, CLAIMS FOR DEFAMATION, ERRORS, LOSS OF DATA, OR INTERRUPTION IN AVAILABILITY OF DATA), ARISING OUT OF OR RELATING TO THIS AGREEMENT, YOUR USE OR INABILITY TO USE THE SITE, DATA LOSS, ANY PURCHASES ON THIS SITE, YOUR PLACEMENT OF CONTENT ON A SITE, OR YOUR RELIANCE UPON INFORMATION OBTAINED FROM OR THROUGH THE SITE, WHETHER BASED IN CONTRACT, TORT, STATUTORY, OR OTHER LAW. CAA'S TOTAL CUMULATIVE LIABILITY ARISING OUT OF OR RELATED TO YOUR USE OF THE SITE WILL NOT EXCEED TWENTY U.S. DOLLARS ($20). YOU ACKNOWLEDGE AND AGREE THAT THE WARRANTY DISCLAIMERS AND THE LIMITATIONS OF LIABILITY SET FORTH IN THIS AGREEMENT REFLECT A REASONABLE AND FAIR ALLOCATION OF RISK BETWEEN YOU AND CAA, AND THAT THESE LIMITATIONS ARE AN ESSENTIAL BASIS FOR CAA'S ABILITY TO MAKE THE SITE AVAILABLE TO YOU ON AN ECONOMICALLY FEASIBLE BASIS.</p>

    <p><strong>13. Indemnification</strong><br>
    You agree to indemnify, defend, and hold harmless CAA and its affiliates, officers, agents, employees, and assignees from any and all claims, liabilities, expenses and damages, including reasonable attorneys' fees and costs, made by any third party relating to or arising out of: (a) your use or attempted use of the Site or any content contained therein; (b) your violation of any law or rights of any third party, (c) information or content that you post or otherwise make available on the Site, including without limitation any claim of infringement or misappropriation of intellectual property or other proprietary rights, and (d) any act or omission by you which is a breach of your obligations under this Agreement. You will have the right to defend and compromise such claim at your expense for the benefit of CAA; provided, however, you will not have the right to obligate CAA in any respect in connection with any such settlement without the written consent of CAA. Notwithstanding the foregoing, if you fail to assume your obligation to defend, CAA may do so to protect their interests and you will reimburse all costs incurred by CAA in connection with such defense.</p>

    <p><strong>14. Termination rights</strong><br>
    You agree that CAA, in its sole discretion, may terminate your use of the Site or your participation in it thereof, for any reason or no reason, and that CAA shall have no liability to you for any such action.</p>

    <p><strong>15. Arbitration; class action waiver</strong><br>
    ALL DISPUTES, CLAIMS, AND CAUSES OF ACTION ARISING OUT OF OR RELATED TO THE LICENSE GRANTED BY CAA TO YOU HEREUNDER OR THIS AGREEMENT (EACH A "DISPUTE") SHALL BE SETTLED BY ARBITRATION IN HUNTSVILLE, ALABAMA ADMINISTERED BY THE AMERICAN ARBITRATION ASSOCIATION IN ACCORDANCE WITH ITS CONSUMER ARBITRATION RULES. YOU HEREBY IRREVOCABLY AND UNCONDITIONALLY WAIVE ANY RIGHT THAT YOU MAY HAVE TO A TRIAL BY JURY REGARDING ANY DISPUTE. YOU UNDERSTAND THAT THIS AGREEMENT CONTAINS A BINDING ARBITRATION CLAUSE THAT CAA MAY ENFORCE. YOU HEREBY IRREVOCABLY AND UNCONDITIONALLY WAIVE ANY RIGHT THAT YOU MAY HAVE TO A PUNITIVE OR EXEMPLARY DAMAGES AWARD. ANY ARBITRATION AWARD SHALL BE LIMITED TO ACTUAL DAMAGES AND ATTORNEYS' FEES. YOU HEREBY IRREVOCABLY AND UNCONDITIONALLY WAIVE ANY RIGHT THAT YOU MAY HAVE TO ANY FORM OF CLASS ARBITRATION. YOUR DISPUTE SHALL BE RESOLVED INDIVIDUALLY AND SHALL NOT BE CONSOLIDATED WITH ANY OTHER CLAIM OF ANY OTHER PERSON OR ENTITY. YOU AGREE AND ACKNOWLEDGE THAT YOU MUST INITIATE ANY DISPUTE BY FILING A PROPER DEMAND FOR ARBITRATION WITHIN ONE YEAR OF THE DATE OF YOUR USE OF THE SITE AND THAT YOU CAN BRING NO DISPUTE AFTER THAT TIME. JUDGMENT ON THE AWARD RENDERED BY THE ARBITRATOR(S) MAY BE ENTERED IN ANY COURT HAVING JURISDICTION. JURISDICTION FOR ANY APPEAL OF AN ARBITRATION AWARD IS APPROPRIATE ONLY IN THE CIRCUIT COURT OF MADISON COUNTY, ALABAMA. ALABAMA LAW WILL GOVERN ANY DISPUTE HEREIN. YOU AGREE AND ACKNOWLEDGE THAT ANY DISPUTE, INCLUDING THE FACT AND OUTCOME OF ARBITRATION, IS STRICTLY CONFIDENTIAL. YOU ALSO AGREE AND ACKNOWLEDGE THAT ANY ARBITRATOR MUST AGREE TO THE SAME STRICT CONFIDENTIALITY. YOU AGREE AND ACKNOWLEDGE THAT CAA WOULD NOT PERMIT USE OF ITS SITE ABSENT YOUR AGREEMENT TO ARBITRATE AND WAIVER OF YOUR RIGHT TO A TRIAL BY JURY.</p>

    <p><strong>16. No warranty/false statements</strong><br>
    CAA makes no express or implied warranty that a user accessing the Site will be certified by the Environmental Protection Agency (EPA) 40CFR 60 Appendix A Method 9, or other body. Attempt or beginning a certification test incurs financial responsibility by the user's client company. User acknowledges that any false or misleading statements asserted during access to the Site may be deemed to be providing false or misleading statements to the EPA. User acknowledges that assisting another user, or completion of any portion of the Qualification Testing contained on the Site may be deemed making false statements to the EPA, which may be deemed the commission of a felony act. Any false statement while using the site may result in a withdrawal of a user's Method 9 Certification or other EPA or Department of Justice enforcement action.</p>

    <p><strong>17. Bankruptcy/non-transferable, nonrefundable</strong><br>
    Any payment for access to the Site shall be non-transferable and non-refundable and shall represent payment to CAA for access to the Site. Should any entity contracting for access to the Site be subject to a voluntary or involuntary petition for relief pursuant to the Bankruptcy Code, then the entity subject to bankruptcy shall be deemed to have breached any contract for access to the Site.</p>

    <p><strong>18. Records/payment</strong><br>
    Payment for Compliance Assurance Associates, Inc. (CAA) public smoke schools is required upon enrollment and should be completed prior to training. Invoices for private (on-site) training are issued upon completion of training and are subject to the NET conditions stated on the invoice. Enrollment fees are non-refundable; however, if an event cancellation occurs due to forces outside the client's or CAA's control, a credit is issued for attendance at a future smoke school event.
    Refunds for visible emission training and other training services are issued as a credit. The credit is valid for future training for approximately seven months.
    A partial refund (minus a $25 per-student enrollment plus a 3.5% credit card processing fee and an additional 3.5% credit card refund processing fee, as applicable) is rendered via credit card, depending on the reason for cancellation.
    In the event of an error in CAA's online enrollment process or by CAA staff, a full refund is issued within two (2) business days of contacting CAA.
    Client enrollment fees are forfeited in the event of a no-show. In the case of an emergency or issue beyond the reasonable control of the attendee, CAA issues a credit memo.
    No refunds are provided if a student attempts lecture or field certification and does not pass. Issuing refunds would imply a certification for payment. To be certified, students must meet the 40 CFR 60 Appendix A Reference Method 9 criteria. Compliance Assurance Associates, Inc. provides a reasonable number of opportunities for students to certify: four (4) test runs for one-day schools and eight (8) test runs for two-day field sessions, and as many test runs as the student desires in a VR environment. CAA does not guarantee the ability to certify. For public events with staggered arrival times, CAA will ensure all students have at least four (4) opportunities to complete certification. If you have concerns that staggered start times may impede your students' ability to certify, contact CAA to arrange early-start test runs.
    Qualification Testing and Method Certification results will be maintained by CAA for an indefinite period of time. Records for an individual user are available to that individual user and to the general public (anyone who has the Student Record number and last name of the employee/student). Records are maintained under the current employer for that individual user and thus the previous employer who contracted with CAA, Inc. of the training may no longer have those records under their client account, but can access the Site using the individual's last name and student record number found on their certification documents. The individual user hereby specifically authorizes CAA to release records associated with the individual user, including but not limited to Qualification Testing and Method Certification issuance, to the individual user's former and current employer, and any government agency making such request. Records for an individual user may be transferred by the individual user and associated with a new employer upon request of the individual user. If the individual user is no longer associated with the employer that contracted and paid for the individual user to access the Site, then the employer must immediately make the employee inactive in their client account and notify CAA within ten (10) business days from the date of separation from the employer. Should an employer fail or refuse to pay for an individual user's access to the Site, then the individual user agrees to pay for said access in addition to any cost of collection.</p>

    <p><strong>19. General legal terms</strong><br>
    If a court of law finds that any provision of this Agreement is invalid or unenforceable, the remaining provisions will continue to be valid and enforceable. We may assign this contract at any time without notice to you. You may not assign this contract to anyone else. Subject to the Disclaimer and Limitation of Liability section above, in any dispute with us, your sole remedy is to stop using the Site. This includes any dispute related to, or arising out of: (a) any term of this Agreement or our enforcement or application of this Agreement; (b) any of our policies or practices, including our Privacy Policy, or our enforcement or application of these policies; (c) the content available on the Site or the Internet or any change in content provided by us; (d) your ability to access or use the Site; or (e) the amount or type of fees, surcharges, applicable taxes, billing methods, or any change to the fees, applicable taxes, surcharges or billing methods. You agree that the laws of the State of Alabama govern this Agreement and any claim or dispute that you may have against us, without regard to Alabama's conflict of laws rules. The United Nations Convention on Contracts for the International Sale of Goods shall have no applicability. You further agree that any disputes or claims that you may have against us reside in and will be resolved by a state court located in Madison County, Alabama and you agree and submit to the exercise of personal jurisdiction of such courts for the purpose of litigating any such claim or action. PLEASE NOTE THAT BY AGREEING TO THIS AGREEMENT, YOU ARE: (x) WAIVING CLAIMS THAT YOU MIGHT OTHERWISE HAVE AGAINST US BASED ON THE LAWS OF OTHER JURISDICTIONS, INCLUDING YOUR OWN; (y) IRREVOCABLY CONSENTING TO THE EXCLUSIVE JURISDICTION OF, AND VENUE IN, STATE OR FEDERAL COURTS IN THE STATE OF ALABAMA OVER ANY DISPUTES OR CLAIMS YOU HAVE WITH US; AND (z) SUBMITTING YOURSELF TO THE PERSONAL JURISDICTION OF COURTS AND ARBITRATION LOCATED IN THE STATE OF ALABAMA FOR THE PURPOSE OF RESOLVING ANY SUCH DISPUTES OR CLAIMS. Registrations, agreements, and terms presented by us electronically to you have the same effect as one in writing and are legally enforceable as a signed writing. You also consent to receive all communications regarding our Site electronically from us. The delivery of any communication from us is effective when sent by us, regardless of when you receive or read the communication. In addition, we are not responsible for communications that do not reach you if you have not provided us with your current contact information. If you decided not to receive notices from us electronically, we may terminate your access to the Site.</p>

    <p><strong>20. Changes to services</strong><br>
    Changes to service include client requests and additional charges by Compliance Assurance Associates, Inc. Changes to services requested by the client (written, verbal, or by action) are reflected in the final invoice. Changes to services include, but are not limited to:</p>
    <ul>
        <li>Standby time required by the client will incur expenses related to extending the schedule.</li>
        <li>Reschedule or cancellation of smoke schools within ten (10) business days from when the school was scheduled to begin.</li>
        <li>Safety training that was not included in the original scope of work.</li>
        <li>Significant smoke school changes, e.g., the number of students attending or the scope of the training.</li>
    </ul>

    <p><strong>21. Cancellation policy for private (on-site) smoke schools</strong><br>
    If either party is prevented from completing the performance of its obligations due to an act of God, government decree, or other occurrence beyond the reasonable control of either party, neither party is liable for consequences or associated expenses.
    If the client cancels due to an operational or training change with less than thirty (30) days' notice, and CAA cannot resell the date, the client will pay associated expenses not to exceed $750 or 10% of proposed costs, whichever is greater. Under the same circumstances, if the client cancels less than fourteen (14) days, the client agrees to pay all administrative and mobilization costs incurred by CAA, including hotels, meals, and labor costs, not to exceed $750 or 20% of proposed costs, whichever is greater.
    If a smoke school is canceled due to weather or other circumstances that are mutually agreed upon, the client and CAA will split these expenses 50% / 50%.</p>

    <p><strong>22. Safety</strong><br>
    Compliance Assurance Associates, Inc. will provide training in a safe environment and comply with federal, state, and local regulations.
    If unsafe work conditions are discovered at a private smoke school location, CAA reserves the right to decline to work until the condition(s) have been corrected and to invoice for associated delays.
    The client will inform CAA of site-specific safety requirements and warrants that the work site will comply with applicable safety standards.
    COVID-19 NOTE: CAA cannot guarantee that smoke school students will not contract COVID-19.</p>

    <p><strong>23. Sales tax/fees</strong><br>
    Compliance Assurance Associates, Inc.'s training, engineering, and consulting services are not taxable.
    Sales/use and added-value taxes and permits are typically not required for training services. Additional taxes and fees levied by the state or local jurisdiction are the responsibility of the client/facility, including permit fees or requirements.</p>

    <p><strong>24. Compliance with codes and law</strong><br>
    For private smoke schools (on-site training), the client agrees to provide an environment where CAA can comply with all applicable codes, regulations, and laws and will be responsible for expenses incurred by CAA to comply unless otherwise stated in the proposal.</p>

    <p><strong>25. Assignment</strong><br>
    CAA reserves the right to assign all obligations under the terms of this contract, including but not limited to all liabilities, warranties, and performance requirements.</p>

    <p><strong>26. Proprietary material</strong><br>
    Drawings, patterns, specifications, video, processes or information provided to or received from the client are confidential and proprietary and the parties agree to mutually respect and adhere to maintaining the respective party's proprietary nature. Any loss associated from the release of such material will be appropriately compensated for by the other party, and as their employer any such persons directed to this material releasing such shall be responsible and liable for such release.</p>

    <p><strong>27. Notices of violation and permitting</strong><br>
    The client is responsible for payments for Notices of Violation of air quality and required permitting for the client's facility.</p>

    <p><strong>28. Contact CAA</strong><br>
    If you have any questions or need to contact us for any reason relating to this Agreement, please contact CAA at: 682 Orvil Smith Rd, Harvest, AL 35749. Hours: 8AM–4:00PM. Phone: (901) 381-9960 (U.S.). <a href="mailto:info@compliance-assurance.com" title="Email Compliance Assurance">info@compliance-assurance.com</a></p>

    <h3 id="eula">End user license agreement for use of VirtualOpacity<sup>&reg;</sup></h3>

    <p><strong>End-user agreement for CAA VirtualOpacity<sup>&reg;</sup> Smoke Testing Application used with a third-party VR headset (this Agreement):</strong></p>

    <p>Compliance Assurance Associates, Inc. (CAA) provides its VirtualOpacity<sup>&reg;</sup> software product, CAA's virtual reality (VR)-based visible emissions observation (VEO) training and certification software application (hereinafter referred to as "the App"). By using VirtualOpacity<sup>&reg;</sup>, you acknowledge and agree to the following:</p>

    <h5>Proper use</h5>
    <p>The user agrees not to use the App or any of its contents for any purpose other than for VEO training and certification through CAA.</p>

    <h5>Physical environment risks</h5>
    <p>You understand that using a VR headset may limit your awareness of your physical surroundings. You agree to clear the area around you of potential hazards before using the App and to try to remain aware of your surroundings during use.</p>

    <h5>Personal injury</h5>
    <p>You assume all risks associated with using the App, including but not limited to the risks of physical injury to yourself or others, property damage, or any other harm resulting from your use of the App and the associated third-party VR headset.</p>

    <h5>Health risks</h5>
    <p>You acknowledge that use of virtual reality technology may cause dizziness, nausea, disorientation, or other physical discomfort in some users. You agree to immediately discontinue use of the App if you experience any of these symptoms.</p>

    <h5>Limitation of liability</h5>
    <p>To the fullest extent permitted by applicable law, CAA shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly, or any loss of data, use, goodwill, or other intangible losses resulting from (a) your use or inability to use the App; (b) any third party conduct or content on the App; or (c) unauthorized access, use, or alteration of your transmissions or content.</p>

    <h5>Third-party hardware</h5>
    <p>You acknowledge that CAA does not manufacture, sell, or control third-party VR headsets and the software that runs on them. CAA is not responsible for any issues, damages, or injuries arising from the use of such third-party hardware. CAA is not responsible for any app or content on the VR headset other than the CAA app, the App.</p>

    <h5>User responsibility</h5>
    <p>You agree to use the App responsibly and in accordance with all safety guidelines provided by CAA, the manufacturer of your VR headset, and your organization. You acknowledge that it is your responsibility to ensure your physical safety and the safety of others around you while using the App.</p>

    <h5>Adjusting VR headset</h5>
    <p>You are responsible to adjust the VR headset to accommodate your head size, eye spacing, and eye glasses, if applicable. Proper adjustment reduces the chance of dizziness or nausea. If you are sharing the VR headset with others, each person is responsible for adjusting the headset for themselves.</p>

    <h5>Shared hardware and hygiene</h5>
    <p>If you are using the App with a shared VR headset, you acknowledge and agree to the following:</p>
    <ul>
        <li><strong>Responsibility for cleaning:</strong> You or someone in your organization is responsible for properly cleaning and disinfecting the shared hardware before and after each use. This may include, but is not limited to, using appropriate disinfectant wipes, cleaning solutions, or disposable covers, in accordance with your organization's policies.</li>
        <li><strong>Health risks:</strong> You understand that failing to properly clean shared hardware may increase the risk of spreading communicable diseases, skin conditions, or other health issues.</li>
        <li><strong>No liability:</strong> CAA is not responsible for any illness, infection, or other health issues that may arise from the use of shared hardware. You agree to use shared hardware at your own risk.</li>
    </ul>

    <h5>Data privacy and retention</h5>
    <p>CAA uses your information (Student Record) for identification, for contacting you, and as an official record of your VEO certifications. Your Student Record contains your CAA student ID, name, phone number, test scores, and certifications.</p>
    <p>CAA will not share this information with any entities other than our subsidiaries and affiliates without your permission. Your certification status is available via the CAA website to those who know your student ID and last name.</p>
    <p>CAA holds your Student Record indefinitely, ensuring a complete history of CAA certifications is available. The phone number field is optional. If you prefer not to provide your actual number, you may enter all 1's instead.</p>
    <p>CAA's full privacy policy can be viewed here: <a href="{{ $privacyUrl }}" title="Compliance Assurance Associates, Inc. privacy policy">{{ $privacyUrl }}</a></p>

    <p><em>By using the App, you acknowledge that you have read, understood, and agree to be bound by the terms of this Agreement. If you do not agree to these terms, you may not use the App.</em></p>
</div>
@endsection
