package com.caa.platform.lecture;

import com.caa.platform.enrollment.EnrollmentComponents;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.student.Student;
import com.caa.platform.student.StudentRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

/**
 * Michael, 2026-09-06 -- Self-Paced Lecture course shell. Backs the real
 * endpoints spec'd in ComplianceEngineClient.php's own doc comments
 * (lectureStudentLookup(), getLectureSections()). Genuinely public --
 * students sign in with student_number + last name, not staff/client
 * credentials -- needs its own permitAll() rule in SecurityConfig (see
 * this class's own trailing note; not touched here directly since I only
 * have an older snapshot of that file and don't want to risk silently
 * dropping the real, already-applied /api/v1/public/certs/** rule).
 *
 * Confirmed with Michael: Student.lectureFeeExempt is a genuine sign-in
 * override, not just a billing flag -- a student flagged exempt
 * (troubleshooting their record, or management granting free access) can
 * sign in even without a real LECTURE_ONLY enrollment at all. See
 * studentLookup() below for where this is actually checked.
 *
 * Still a real, separate, open question: Enrollment also has its own
 * lectureAccessGranted flag, distinct from lectureFeeExempt above, which
 * this doesn't check at all yet. If that flag represents a separate,
 * real gate (e.g. staff manually granting access after payment clears,
 * independent of the enrollment record simply existing), this method
 * needs a third condition added.
 */
@RestController
@RequestMapping("/api/v1/lecture")
public class LectureController {

    private final StudentRepository studentRepository;
    private final EnrollmentRepository enrollmentRepository;
    private final LectureSectionRepository sectionRepository;
    private final LecturePageRepository pageRepository;
    private final LectureStudentPageProgressRepository progressRepository;
    private final LectureResourcePageRepository resourcePageRepository;
    private final LectureQuizRepository quizRepository;
    private final LectureQuizQuestionRepository quizQuestionRepository;
    private final LectureQuizChoiceRepository quizChoiceRepository;
    private final LectureStudentQuizInProgressAnswerRepository inProgressAnswerRepository;
    private final LectureStudentQuizAttemptRepository quizAttemptRepository;
    private final LectureStudentQuizAnswerRepository quizAnswerRepository;

    public LectureController(StudentRepository studentRepository,
                              EnrollmentRepository enrollmentRepository,
                              LectureSectionRepository sectionRepository,
                              LecturePageRepository pageRepository,
                              LectureStudentPageProgressRepository progressRepository,
                              LectureResourcePageRepository resourcePageRepository,
                              LectureQuizRepository quizRepository,
                              LectureQuizQuestionRepository quizQuestionRepository,
                              LectureQuizChoiceRepository quizChoiceRepository,
                              LectureStudentQuizInProgressAnswerRepository inProgressAnswerRepository,
                              LectureStudentQuizAttemptRepository quizAttemptRepository,
                              LectureStudentQuizAnswerRepository quizAnswerRepository) {
        this.studentRepository = studentRepository;
        this.enrollmentRepository = enrollmentRepository;
        this.sectionRepository = sectionRepository;
        this.pageRepository = pageRepository;
        this.progressRepository = progressRepository;
        this.resourcePageRepository = resourcePageRepository;
        this.quizRepository = quizRepository;
        this.quizQuestionRepository = quizQuestionRepository;
        this.quizChoiceRepository = quizChoiceRepository;
        this.inProgressAnswerRepository = inProgressAnswerRepository;
        this.quizAttemptRepository = quizAttemptRepository;
        this.quizAnswerRepository = quizAnswerRepository;
    }

    /**
     * GET /api/v1/lecture/student-lookup?studentNumber=...&lastName=...
     * Real sign-in check: student_number + last name against the real,
     * existing students table (no new identity system at all). Last name
     * is matched against the last word of the single, combined `name`
     * field -- confirmed with Michael as sufficient for now; a real
     * First/Last Name schema split is a separate, future item.
     *
     * Returns 403 (matching this whole project's own established
     * ComplianceEngineForbiddenException convention on the Laravel side)
     * if the student exists but isn't allowed/enrolled, rather than a
     * generic 404/500 -- LectureController.php on the Laravel side
     * already catches this specifically to show the real "must be
     * enrolled" message.
     */
    @GetMapping("/student-lookup")
    public ResponseEntity<?> studentLookup(@RequestParam String studentNumber, @RequestParam String lastName) {
        Student student = studentRepository.findByStudentNumber(studentNumber).orElse(null);

        if (student == null || !lastNameMatches(student.getName(), lastName)) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND)
                    .body(Map.of("error", "We could not find a match for that student record number and last name."));
        }

        if (!student.isSelfPacedLectureAllowed()) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "You must be a CAA client and enrolled to take this course."));
        }

        // Michael, 2026-09-06 -- confirmed: lectureFeeExempt is a real,
        // deliberate override here, not just a billing flag -- a student
        // flagged exempt (troubleshooting their record, or management
        // granting free access) can sign in even without a real
        // LECTURE_ONLY enrollment at all. Only checked when NOT exempt.
        if (!student.isLectureFeeExempt()) {
            boolean hasLectureEnrollment = enrollmentRepository.findByStudentId(student.getId()).stream()
                    .anyMatch(e -> e.getEnrollmentComponents() == EnrollmentComponents.LECTURE_ONLY);

            if (!hasLectureEnrollment) {
                return ResponseEntity.status(HttpStatus.FORBIDDEN)
                        .body(Map.of("error", "You must be a CAA client and enrolled to take this course."));
            }
        }

        return ResponseEntity.ok(student);
    }

    private boolean lastNameMatches(String fullName, String candidateLastName) {
        if (fullName == null || candidateLastName == null) {
            return false;
        }
        String[] parts = fullName.trim().split("\\s+");
        String actualLastName = parts[parts.length - 1];
        return actualLastName.equalsIgnoreCase(candidateLastName.trim());
    }

    public record LecturePageDto(Long id, String title, boolean read, boolean unlocked) {}
    public record LectureSectionDto(String name, boolean touched, boolean unlocked, List<LecturePageDto> pages, Long quizId, boolean quizPassed) {}

    /**
     * GET /api/v1/lecture/students/{studentId}/sections
     * The real, dynamic sidebar data: every real lecture_sections row,
     * its real lecture_pages (empty for sections Michael hasn't captured
     * yet), and this student's own real progress -- so the sidebar
     * renders genuine locked/unlocked/read state, not a static shell.
     *
     * Sequential unlock (migration 044's own design, extended 2026-09-07
     * for the real quiz-taking flow): a page is unlocked if its own
     * section is unlocked AND it's the first page in that section, or
     * the previous page in the same section has been read. A section
     * (beyond the first) is unlocked only once the previous section's
     * own quiz has been passed -- matching the real, live course's own
     * confirmed behavior (e.g. History stays disabled until the
     * Introduction quiz is passed, not just once all Introduction pages
     * are read). If a section has no real quiz seeded yet at all, the
     * next section stays locked -- the safer default, since there's no
     * real gate to have passed yet.
     */
    @GetMapping("/students/{studentId}/sections")
    public ResponseEntity<?> sections(@PathVariable Long studentId) {
        Student student = studentRepository.findById(studentId).orElse(null);
        if (student == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Student not found."));
        }

        List<Long> readPageIds = progressRepository.findByStudentId(studentId).stream()
                .map(p -> p.getLecturePage().getId())
                .toList();

        List<LectureSection> allSections = sectionRepository.findAllByOrderByOrderIndexAsc();
        List<LectureSectionDto> sections = new java.util.ArrayList<>();

        for (LectureSection section : allSections) {
            boolean sectionUnlocked = isSectionUnlocked(studentId, section.getId());

            List<LecturePage> pages = pageRepository.findByLectureSectionIdOrderByOrderIndexAsc(section.getId());
            List<LecturePageDto> pageDtos = new java.util.ArrayList<>();
            boolean sectionTouched = false;

            for (int i = 0; i < pages.size(); i++) {
                LecturePage page = pages.get(i);
                boolean read = readPageIds.contains(page.getId());
                boolean unlocked = sectionUnlocked && isPageUnlocked(studentId, pages, i);
                pageDtos.add(new LecturePageDto(page.getId(), page.getTitle(), read, unlocked));
                if (read) {
                    sectionTouched = true;
                }
            }

            Long quizId = quizRepository.findBySectionId(section.getId()).map(LectureQuiz::getId).orElse(null);
            boolean quizPassed = quizId != null
                    && quizAttemptRepository.findFirstByStudentIdAndQuizIdAndPassedTrue(studentId, quizId).isPresent();

            sections.add(new LectureSectionDto(section.getName(), sectionTouched, sectionUnlocked, pageDtos, quizId, quizPassed));
        }

        return ResponseEntity.ok(sections);
    }

    public record LectureContentPageDto(Long id, String title, String sectionName, String content, Long previousPageId, Long nextPageId, boolean nextIsQuiz, Long nextQuizId) {}

    /**
     * GET /api/v1/lecture/students/{studentId}/pages/{pageId}
     * A single real page's title/content, plus what comes next (the next
     * page in the same section, or -- if this was the section's last page
     * -- an indication that the section quiz comes next instead).
     *
     * Gated by the same real, sequential unlock rule as sections() above:
     * 404 if the page doesn't exist at all, 403 (matching this whole
     * project's own ComplianceEngineForbiddenException convention) if it
     * exists but isn't unlocked for this student yet -- e.g. a student
     * guessing at a later page's URL directly rather than working through
     * the course in order.
     *
     * Deliberately does NOT mark the page as read just by fetching it --
     * see markPageRead() below. A student opening a page hasn't
     * necessarily read it yet; marking happens when they explicitly move
     * on (the real "Next" action), not on simple page load.
     */
    @GetMapping("/students/{studentId}/pages/{pageId}")
    public ResponseEntity<?> getPage(@PathVariable Long studentId, @PathVariable Long pageId) {
        Student student = studentRepository.findById(studentId).orElse(null);
        if (student == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Student not found."));
        }

        LecturePage page = pageRepository.findById(pageId).orElse(null);
        if (page == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Page not found."));
        }

        // Michael, 2026-09-07 -- real section-level gate, added alongside
        // the quiz-taking flow: a student shouldn't be able to reach a
        // page within a locked section at all just by guessing its real
        // page id directly, even if the page itself would otherwise be
        // "first in its section."
        if (!isSectionUnlocked(studentId, page.getLectureSection().getId())) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "This section isn't available yet -- please pass the previous section's quiz first."));
        }

        List<LecturePage> sectionPages = pageRepository.findByLectureSectionIdOrderByOrderIndexAsc(page.getLectureSection().getId());

        // Michael, 2026-09-07 -- fixed a real bug here: sectionPages.indexOf(page)
        // relies on LecturePage's own .equals(), which is never overridden, so
        // Java falls back to default reference equality. page and the entries in
        // sectionPages come from two separate repository calls (no @Transactional
        // boundary keeping them in the same Hibernate session), so they're
        // genuinely different object instances even though they represent the
        // same row -- indexOf() silently returned -1 every time, which meant
        // "Next" on every page actually pointed back at that same page itself
        // (index -1 + 1 = 0, the section's first page) instead of the real next
        // page. Comparing by id directly avoids relying on object identity.
        int pageIndex = -1;
        for (int i = 0; i < sectionPages.size(); i++) {
            if (sectionPages.get(i).getId().equals(page.getId())) {
                pageIndex = i;
                break;
            }
        }

        if (!isPageUnlocked(studentId, sectionPages, pageIndex)) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "This page isn't available yet -- please complete the previous page first."));
        }

        boolean hasNextPage = pageIndex < sectionPages.size() - 1;
        Long nextPageId = hasNextPage ? sectionPages.get(pageIndex + 1).getId() : null;
        boolean nextIsQuiz = !hasNextPage; // last page in the section -> the section quiz comes next

        // Michael, 2026-09-07 -- "Previous" support, added alongside the
        // second real content page (this course's first page never
        // needed it, being first in the section). Deliberately does NOT
        // check unlock state at all -- a student can always navigate
        // backward to a page they've already unlocked/read; only forward
        // movement is gated.
        Long previousPageId = pageIndex > 0 ? sectionPages.get(pageIndex - 1).getId() : null;

        // Michael, 2026-09-07 -- fixed a real LazyInitializationException
        // here: page.getLectureSection() is a LAZY proxy, and by this
        // point in the method the original repository call's own
        // Hibernate session has already closed, so calling a real getter
        // on it (.getName()) failed -- .getId() above didn't, since the
        // foreign key itself is embedded in the proxy without needing a
        // session at all. Fetching the section fresh via its own
        // repository avoids the stale proxy entirely.
        LectureSection section = sectionRepository.findById(page.getLectureSection().getId()).orElse(null);
        String sectionName = section != null ? section.getName() : "";

        // Michael, 2026-09-07 -- real quiz-taking flow. Real, working
        // quiz id for this page's own section, if one has been seeded --
        // markPageRead() on the Laravel side needs this to know where to
        // send the student once the section's last page is marked read.
        Long nextQuizId = nextIsQuiz && section != null
                ? quizRepository.findBySectionId(section.getId()).map(LectureQuiz::getId).orElse(null)
                : null;

        return ResponseEntity.ok(new LectureContentPageDto(page.getId(), page.getTitle(), sectionName, page.getContent(), previousPageId, nextPageId, nextIsQuiz, nextQuizId));
    }

    /**
     * POST /api/v1/lecture/students/{studentId}/pages/{pageId}/read
     * The real "don't lose progress if they step away" write (migration
     * 044's own design) -- one row per student per page. Idempotent: a
     * student re-visiting an already-read page and clicking Next again
     * doesn't create a duplicate row or error, matching the real unique
     * constraint on (student_id, lecture_page_id).
     */
    @PostMapping("/students/{studentId}/pages/{pageId}/read")
    public ResponseEntity<?> markPageRead(@PathVariable Long studentId, @PathVariable Long pageId) {
        Student student = studentRepository.findById(studentId).orElse(null);
        if (student == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Student not found."));
        }

        LecturePage page = pageRepository.findById(pageId).orElse(null);
        if (page == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Page not found."));
        }

        if (!progressRepository.existsByStudentIdAndLecturePageId(studentId, pageId)) {
            LectureStudentPageProgress progress = new LectureStudentPageProgress();
            progress.setStudent(student);
            progress.setLecturePage(page);
            progressRepository.save(progress);
        }

        return ResponseEntity.ok(Map.of("status", "ok"));
    }

    /**
     * Shared, real sequential-unlock check -- a page at index 0 in its
     * section is always unlocked; any later page requires the previous
     * page (by orderIndex within the same section) to already be read by
     * this student. Same rule sections() applies when building the
     * sidebar's own per-page unlocked flag -- kept here as a single,
     * shared method so the two endpoints can't silently drift apart.
     */
    private boolean isPageUnlocked(Long studentId, List<LecturePage> sectionPages, int pageIndex) {
        if (pageIndex <= 0) {
            return true;
        }
        Long previousPageId = sectionPages.get(pageIndex - 1).getId();
        return progressRepository.existsByStudentIdAndLecturePageId(studentId, previousPageId);
    }

    /**
     * Michael, 2026-09-07 -- real, shared section-level unlock check,
     * added alongside the quiz-taking flow. A section is unlocked if
     * it's the first section by orderIndex, or the previous section's
     * own quiz has been passed by this student. If the previous section
     * has no real quiz seeded yet at all, this section stays locked --
     * the safer default, since there's no real gate to have passed yet.
     */
    private boolean isSectionUnlocked(Long studentId, Long sectionId) {
        LectureSection section = sectionRepository.findById(sectionId).orElse(null);
        if (section == null || section.getOrderIndex() <= 1) {
            return true;
        }

        LectureSection previousSection = sectionRepository.findAllByOrderByOrderIndexAsc().stream()
                .filter(s -> s.getOrderIndex() == section.getOrderIndex() - 1)
                .findFirst()
                .orElse(null);
        if (previousSection == null) {
            return true;
        }

        LectureQuiz previousQuiz = quizRepository.findBySectionId(previousSection.getId()).orElse(null);
        return previousQuiz != null
                && quizAttemptRepository.findFirstByStudentIdAndQuizIdAndPassedTrue(studentId, previousQuiz.getId()).isPresent();
    }

    public record LectureResourcePageSummaryDto(String title, String slug) {}

    /**
     * GET /api/v1/lecture/resources
     * All real Resources items (Glossary, FAQs, Abbreviations, VEO
     * Resources, Bibliography), for the sidebar's own Resources listing.
     * Deliberately no student context at all -- Resources content isn't
     * gated, unlocked, or tracked per-student, matching the real, live
     * course's own confirmed behavior.
     */
    @GetMapping("/resources")
    public ResponseEntity<?> resources() {
        List<LectureResourcePageSummaryDto> resources = resourcePageRepository.findAllByOrderByOrderIndexAsc().stream()
                .map(r -> new LectureResourcePageSummaryDto(r.getTitle(), r.getSlug()))
                .toList();
        return ResponseEntity.ok(resources);
    }

    public record LectureResourcePageDto(String title, String content) {}

    /**
     * GET /api/v1/lecture/resources/{slug}
     * A single real Resources page by slug (not id) -- matches the real,
     * live course's own URL structure (glossary.php, faqs.php, etc.) and
     * the fact that these pages are referenced directly by name from
     * other pages' own content (e.g. the Introduction pages' own
     * glossary links), not navigated to sequentially at all.
     */
    @GetMapping("/resources/{slug}")
    public ResponseEntity<?> resource(@PathVariable String slug) {
        LectureResourcePage page = resourcePageRepository.findBySlug(slug).orElse(null);
        if (page == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Resource not found."));
        }
        return ResponseEntity.ok(new LectureResourcePageDto(page.getTitle(), page.getContent()));
    }

    public record LectureQuizChoiceDto(Long id, String choiceText) {}
    public record LectureQuizQuestionDto(Long id, String questionText, List<LectureQuizChoiceDto> choices, Long selectedChoiceId) {}
    public record LectureQuizDto(Long id, Integer passingScorePercent, List<LectureQuizQuestionDto> questions) {}

    /**
     * GET /api/v1/lecture/students/{studentId}/quizzes/{quizId}
     * The real quiz-taking data: every question and its choices, plus
     * this student's own, real in-progress answer for each question (if
     * any) -- so a student who steps away mid-quiz and comes back sees
     * their own radio selections still in place, matching the real
     * point of migration 046's own in-progress table.
     *
     * Deliberately never includes which choice is correct at all --
     * that would leak the real answer before the student has submitted
     * it. Correctness is only ever revealed via the real submit-answer
     * response below.
     *
     * Gated by the same real, shared section-level unlock check as
     * getPage() -- a student can't reach a section's quiz before the
     * section itself is unlocked.
     */
    @GetMapping("/students/{studentId}/quizzes/{quizId}")
    public ResponseEntity<?> getQuiz(@PathVariable Long studentId, @PathVariable Long quizId) {
        Student student = studentRepository.findById(studentId).orElse(null);
        if (student == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Student not found."));
        }

        LectureQuiz quiz = quizRepository.findById(quizId).orElse(null);
        if (quiz == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Quiz not found."));
        }

        if (!isSectionUnlocked(studentId, quiz.getSection().getId())) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "This section isn't available yet -- please pass the previous section's quiz first."));
        }

        List<LectureStudentQuizInProgressAnswer> inProgressAnswers =
                inProgressAnswerRepository.findByStudentIdAndQuestion_QuizId(studentId, quizId);
        Map<Long, Long> selectedChoiceByQuestionId = inProgressAnswers.stream()
                .collect(java.util.stream.Collectors.toMap(a -> a.getQuestion().getId(), a -> a.getChoice().getId()));

        List<LectureQuizQuestionDto> questionDtos = quizQuestionRepository.findByQuizIdOrderByOrderIndexAsc(quizId).stream()
                .map(q -> {
                    List<LectureQuizChoiceDto> choiceDtos = quizChoiceRepository.findByQuestionIdOrderByOrderIndexAsc(q.getId()).stream()
                            .map(c -> new LectureQuizChoiceDto(c.getId(), c.getChoiceText()))
                            .toList();
                    return new LectureQuizQuestionDto(q.getId(), q.getQuestionText(), choiceDtos, selectedChoiceByQuestionId.get(q.getId()));
                })
                .toList();

        return ResponseEntity.ok(new LectureQuizDto(quiz.getId(), quiz.getPassingScorePercent(), questionDtos));
    }

    public record SubmitAnswerRequest(Long choiceId) {}
    public record LectureQuizAnswerResultDto(boolean correct, boolean quizComplete, Integer scorePercent, Boolean passed) {}

    /**
     * POST /api/v1/lecture/students/{studentId}/questions/{questionId}/answer
     * The real, immediate per-question save -- matches the live course's
     * own confirmed behavior (each radio selection submits right away,
     * not a single "submit all" button at the end). Idempotent:
     * re-answering the same question updates the existing in-progress
     * row rather than creating a duplicate, matching the real unique
     * constraint on (student_id, lecture_quiz_question_id).
     *
     * Once every question in the quiz has a real, in-progress answer,
     * this computes the final score, writes the real, permanent attempt
     * record (LectureStudentQuizAttempt + LectureStudentQuizAnswer,
     * migration 044's own tables), and clears the in-progress answers --
     * so a retake genuinely starts fresh. quizComplete/scorePercent/passed
     * are only meaningful when quizComplete is true.
     *
     * Michael, 2026-09-07 -- fixed a real
     * jakarta.persistence.TransactionRequiredException here:
     * deleteByStudentIdAndQuestion_QuizId() below is a real, derived
     * delete query, and Spring Data JPA genuinely requires an explicit
     * transaction boundary for those -- unlike .save()/.findBy...()
     * calls, which get their own, automatic transaction wrapping by
     * default, a derived delete does not. Adding @Transactional here,
     * matching this whole project's own established pattern of business
     * logic living directly in controllers rather than a separate
     * service layer.
     */
    @Transactional
    @PostMapping("/students/{studentId}/questions/{questionId}/answer")
    public ResponseEntity<?> submitAnswer(@PathVariable Long studentId, @PathVariable Long questionId, @RequestBody SubmitAnswerRequest request) {
        Student student = studentRepository.findById(studentId).orElse(null);
        if (student == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Student not found."));
        }

        LectureQuizQuestion question = quizQuestionRepository.findById(questionId).orElse(null);
        if (question == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Question not found."));
        }

        LectureQuizChoice choice = quizChoiceRepository.findById(request.choiceId()).orElse(null);
        if (choice == null || !choice.getQuestion().getId().equals(questionId)) {
            return ResponseEntity.status(HttpStatus.BAD_REQUEST).body(Map.of("error", "That choice does not belong to this question."));
        }

        LectureStudentQuizInProgressAnswer existing =
                inProgressAnswerRepository.findByStudentIdAndQuestionId(studentId, questionId).orElse(null);
        if (existing == null) {
            existing = new LectureStudentQuizInProgressAnswer();
            existing.setStudent(student);
            existing.setQuestion(question);
        }
        existing.setChoice(choice);
        inProgressAnswerRepository.save(existing);

        Long quizId = question.getQuiz().getId();
        List<LectureQuizQuestion> allQuestions = quizQuestionRepository.findByQuizIdOrderByOrderIndexAsc(quizId);
        List<LectureStudentQuizInProgressAnswer> allAnswers =
                inProgressAnswerRepository.findByStudentIdAndQuestion_QuizId(studentId, quizId);

        boolean quizComplete = allAnswers.size() >= allQuestions.size();

        if (!quizComplete) {
            return ResponseEntity.ok(new LectureQuizAnswerResultDto(choice.isCorrect(), false, null, null));
        }

        // Michael, 2026-09-07 -- quiz is now complete: compute the real
        // final score, write the real, permanent attempt record, and
        // clear the in-progress answers so a retake starts fresh.
        //
        // Fetches the real choices fresh here rather than calling
        // a.getChoice().isCorrect() directly -- getChoice() is a LAZY
        // proxy from a separate repository call (allAnswers, above), and
        // when this fix was first written this method wasn't yet marked
        // @Transactional, so that proxy's own session had already closed
        // by this point -- the same real LazyInitializationException
        // already found and fixed in
        // getPage() earlier.
        List<Long> answeredChoiceIds = allAnswers.stream().map(a -> a.getChoice().getId()).toList();
        Map<Long, Boolean> correctByChoiceId = new java.util.HashMap<>();
        for (LectureQuizChoice c : quizChoiceRepository.findAllById(answeredChoiceIds)) {
            correctByChoiceId.put(c.getId(), c.isCorrect());
        }
        long correctCount = answeredChoiceIds.stream().filter(id -> Boolean.TRUE.equals(correctByChoiceId.get(id))).count();
        int scorePercent = (int) Math.round((correctCount * 100.0) / allQuestions.size());

        // Michael, 2026-09-07 -- fetches the real quiz fresh here rather
        // than calling question.getQuiz().getPassingScorePercent()
        // directly -- getQuiz() is a LAZY proxy from question's own
        // earlier, separate repository fetch, and when this fix was
        // first written this method wasn't yet marked @Transactional,
        // so calling a real field getter beyond .getId() on it risked
        // the same LazyInitializationException already found
        // and fixed in getPage() earlier.
        LectureQuiz quiz = quizRepository.findById(question.getQuiz().getId()).orElseThrow();
        boolean passed = scorePercent >= quiz.getPassingScorePercent();

        LectureStudentQuizAttempt attempt = new LectureStudentQuizAttempt();
        attempt.setStudent(student);
        attempt.setQuiz(quiz);
        attempt.setScorePercent(scorePercent);
        attempt.setPassed(passed);
        attempt = quizAttemptRepository.save(attempt);

        for (LectureStudentQuizInProgressAnswer a : allAnswers) {
            LectureStudentQuizAnswer permanentAnswer = new LectureStudentQuizAnswer();
            permanentAnswer.setAttempt(attempt);
            permanentAnswer.setQuestion(a.getQuestion());
            permanentAnswer.setChoice(a.getChoice());
            quizAnswerRepository.save(permanentAnswer);
        }

        inProgressAnswerRepository.deleteByStudentIdAndQuestion_QuizId(studentId, quizId);

        return ResponseEntity.ok(new LectureQuizAnswerResultDto(choice.isCorrect(), true, scorePercent, passed));
    }

    public record LectureQuizSummarySectionDto(String name, boolean unlocked, int totalQuestions, int questionsAnswered, Integer answersCorrectPercent) {}

    /**
     * GET /api/v1/lecture/students/{studentId}/quiz-summary
     * The real Quiz Summary Page data (lecture-quiz.php with no real
     * section id in the live course) -- every section at once, its
     * total question count, how many the student has answered in their
     * real, most recent completed attempt (0 if none yet), and that
     * attempt's own score -- matching the real, live course's own
     * summary table exactly.
     */
    @GetMapping("/students/{studentId}/quiz-summary")
    public ResponseEntity<?> quizSummary(@PathVariable Long studentId) {
        Student student = studentRepository.findById(studentId).orElse(null);
        if (student == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Student not found."));
        }

        List<LectureQuizSummarySectionDto> summary = sectionRepository.findAllByOrderByOrderIndexAsc().stream()
                .map(section -> {
                    boolean unlocked = isSectionUnlocked(studentId, section.getId());
                    int totalQuestions = quizRepository.findBySectionId(section.getId())
                            .map(q -> quizQuestionRepository.findByQuizIdOrderByOrderIndexAsc(q.getId()).size())
                            .orElse(0);

                    int questionsAnswered = 0;
                    Integer answersCorrectPercent = null;
                    LectureQuiz quiz = quizRepository.findBySectionId(section.getId()).orElse(null);
                    if (quiz != null) {
                        List<LectureStudentQuizAttempt> attempts = quizAttemptRepository.findByStudentIdAndQuizId(studentId, quiz.getId());
                        if (!attempts.isEmpty()) {
                            LectureStudentQuizAttempt mostRecent = attempts.get(attempts.size() - 1);
                            questionsAnswered = totalQuestions;
                            answersCorrectPercent = mostRecent.getScorePercent();
                        }
                    }

                    return new LectureQuizSummarySectionDto(section.getName(), unlocked, totalQuestions, questionsAnswered, answersCorrectPercent);
                })
                .toList();

        return ResponseEntity.ok(summary);
    }
}
