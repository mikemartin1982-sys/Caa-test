-- ============================================================================
-- Lecture content seed: Introduction section, page 1 (Getting Started)
-- Reference: Michael, 2026-09-06 -- Self-Paced Lecture course, real content
-- capture. Not a schema migration -- pure content data against the real
-- lecture_pages table (migration 044). Toggle sections ("Course
-- Assistance," "Quiz Structure") flattened to plain, always-visible
-- content, matching the same simplification already used throughout this
-- project (VR Smoke School, About, Why Choose Compliance Assurance, and
-- this course's own home-base page).
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'introduction'),
    'Getting Started',
    'getting-started',
    '<img src="/images/lecture/getting-started-image.jpg" alt="Visible emissions online course" style="max-width:100%; border-radius:0.375rem;">
<h3>A Modular Design for Ease of Use</h3>

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
</ul>',
    1
);
