-- ============================================================================
-- Lecture content fix: Introduction section, page 1 (Getting Started)
-- Reference: Michael, 2026-09-07 -- found live: the original page uses a
-- real two-column layout (image left, "A Modular Design for Ease of Use"
-- caption below it; welcome text + toggles right), but the earlier seed
-- stacked everything in a single column. UPDATE, not a new INSERT -- the
-- row already exists.
-- ============================================================================

UPDATE lecture_pages
SET content = '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:280px; flex-shrink:0;">
        <img src="/images/lecture/getting-started-image.jpg" alt="Visible emissions online course" style="width:100%; border-radius:0.375rem;">
        <h3 style="margin-top:0.75rem;">A Modular Design for Ease of Use</h3>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Welcome to CAA Online Visible Emissions Course</h2>
        <p>Thank you for choosing Compliance Assurance Associates, Inc. (CAA)''s online opacity (aka visible emissions) training course. This page introduces you to the online visible emissions course and explains the steps to complete your visible emission training program.</p>
        <p>Each course section is available from the navigation on the left, but since you have not yet completed this self-paced lecture, you will have to proceed through the course section-by-section, by using the PREVIOUS and NEXT buttons at the bottom of each page.</p>

        <h3>Course Assistance</h3>
        <p>If you have <strong>operational questions</strong> or issues, please call the CAA at <a href="tel:+1-901-381-9960">901-381-9960</a> during business hours (M-F 8:00 AM - 4:00 PM Central). This phone number is also available in the header on every page.</p>
        <p>If you have questions about the <strong>course content or questions about visible emissions observations</strong>, first check the FAQs page to see if the question appears there. If not, use the email question link available in the header on every page or the Submit a Question page.</p>
        <p>Content/VEO questions will be responded to within one week''s time. We will add general questions to the FAQs page.</p>
        <p>NOTE: Throughout the course sections, links that are bold red will take you to our glossary page for an explanation of the selected text.</p>

        <h3>Quiz Structure</h3>
        <ul>
            <li>There is a quiz at the end of each section.</li>
            <li>You must pass with a score of at least 70% correct before moving on to the next section.</li>
            <li>Upon completion of each section quiz, your score will be reported on the screen.</li>
            <li>You will have the opportunity to retake quiz sections to improve your score at the time you submit your completed quiz.</li>
            <li>When all quiz sections have been completed, you will be asked "Do you want to create your lecture certificate?" At that point, you can choose to retake one or more sections of the quiz.</li>
            <li>The total score of the quiz will appear on your training certificate.</li>
            <li>Once the training certificate is created, it cannot be changed.</li>
        </ul>
    </div>
</div>'
WHERE lecture_section_id = (SELECT id FROM lecture_sections WHERE slug = 'introduction')
  AND slug = 'getting-started';
