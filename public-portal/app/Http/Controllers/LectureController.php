<?php

namespace App\Http\Controllers;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineForbiddenException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-09-06 -- Self-Paced Lecture course shell.
 *
 * Deliberately its own, separate controller and session state -- this is
 * NOT Laravel's own Auth guard system at all, matching the real, live
 * course's own lightweight "signed in as" state (student_id/name/record_id
 * carried in session, confirmed from its real, original source). All real
 * business logic (sign-in eligibility, section/page/progress data) lives
 * on the Java Compliance Engine side via ComplianceEngineClient -- no
 * direct database access from Laravel at all, matching this whole
 * project's own, established pattern.
 */
class LectureController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    public function showSignIn(): View
    {
        return view('lecture.sign-in');
    }

    /**
     * Michael, 2026-09-06 -- real sign-in check: student_number + last
     * name against the real, existing students table, plus
     * self_paced_lecture_allowed = true and a real LECTURE_ONLY
     * enrollment (all enforced Java-side, per lectureStudentLookup()'s
     * own doc comment). A 403 here means the student exists but isn't
     * allowed/enrolled -- shown as the real "must be a CAA client and
     * enrolled" message, matching the real, original sign-in page's own
     * copy, rather than a generic error.
     */
    public function submitSignIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:50'],
            'student_lname' => ['required', 'string', 'max:100'],
        ]);

        try {
            $student = $this->engine->lectureStudentLookup($validated['student_id'], $validated['student_lname']);
        } catch (ComplianceEngineForbiddenException $e) {
            return redirect()->route('lecture.sign-in')
                ->withErrors(['sign_in' => 'You must be a CAA client and enrolled to take this course.'])
                ->withInput();
        }

        if (empty($student['id'])) {
            return redirect()->route('lecture.sign-in')
                ->withErrors(['sign_in' => 'We could not find a match for that student record number and last name.'])
                ->withInput();
        }

        $request->session()->put('lecture_student_id', $student['id']);
        $request->session()->put('lecture_student_name', $student['name'] ?? '');
        $request->session()->put('lecture_student_number', $student['studentNumber'] ?? $validated['student_id']);

        return redirect()->route('lecture.home-base');
    }

    public function signOut(Request $request): RedirectResponse
    {
        $request->session()->forget(['lecture_student_id', 'lecture_student_name', 'lecture_student_number']);

        return redirect()->route('lecture.sign-in');
    }

    /**
     * The real home-base landing page, shown right after sign-in. Pulls
     * the real, dynamic section/page/progress structure from the Java
     * side so the sidebar reflects genuine data -- currently just the 10
     * real section names with no pages, since Michael is capturing real
     * page content one section at a time.
     */
    public function homeBase(Request $request): View|RedirectResponse
    {
        $studentId = $request->session()->get('lecture_student_id');

        if (! $studentId) {
            return redirect()->route('lecture.sign-in');
        }

        $sections = $this->engine->getLectureSections($studentId);
        $resources = $this->engine->getLectureResources();

        return view('lecture.home-base', [
            'sections' => $sections,
            'resources' => $resources,
            'studentName' => $request->session()->get('lecture_student_name'),
        ]);
    }

    /**
     * The real, generic content-page view. Renders whatever real page
     * data exists for the given id (title/content/next-page) once
     * Michael starts feeding sections in.
     *
     * Also fetches the same real sections data home-base uses, so the
     * shared sidebar partial (see lecture.sidebar) has what it needs to
     * render on this page too, not just on home-base.
     *
     * A 403 here means this specific page isn't unlocked for this
     * student yet (the real, sequential unlock rule, e.g. a student
     * guessing at a later page's URL directly) -- sent back to home-base
     * with a real, honest message rather than a generic error.
     */
    public function showPage(Request $request, int $pageId): View|RedirectResponse
    {
        $studentId = $request->session()->get('lecture_student_id');

        if (! $studentId) {
            return redirect()->route('lecture.sign-in');
        }

        try {
            $page = $this->engine->getLecturePage($studentId, $pageId);
        } catch (ComplianceEngineForbiddenException $e) {
            return redirect()->route('lecture.home-base')
                ->withErrors(['page' => "That page isn't available yet -- please complete the previous page first."]);
        }

        $sections = $this->engine->getLectureSections($studentId);
        $resources = $this->engine->getLectureResources();

        return view('lecture.page', ['page' => $page, 'sections' => $sections, 'resources' => $resources]);
    }

    /**
     * Michael, 2026-09-07 -- the real "Next" action. Marks the current
     * page read (the real progress write, migration 044's own design),
     * then moves the student on -- to the next real page if one exists,
     * to the real section quiz if this was the section's last page and
     * a quiz has been seeded for it, or back to home-base with a real
     * note if neither exists yet. Deliberately a separate, real POST
     * action from showPage() above -- viewing a page shouldn't silently
     * count as reading it.
     *
     * next_page_id / next_quiz_id come from real, hidden form fields on
     * the page view itself (already known from showPage()'s own fetch)
     * rather than re-fetching the page here just to learn them again.
     */
    public function markPageRead(Request $request, int $pageId): RedirectResponse
    {
        $studentId = $request->session()->get('lecture_student_id');

        if (! $studentId) {
            return redirect()->route('lecture.sign-in');
        }

        $this->engine->markLecturePageRead($studentId, $pageId);

        $nextPageId = $request->input('next_page_id');
        $nextQuizId = $request->input('next_quiz_id');

        if (! empty($nextPageId)) {
            return redirect()->route('lecture.page', $nextPageId);
        }

        if (! empty($nextQuizId)) {
            return redirect()->route('lecture.quiz', $nextQuizId);
        }

        return redirect()->route('lecture.home-base')
            ->with('status', 'Section complete! The section quiz isn\'t built yet -- check back soon.');
    }

    /**
     * Michael, 2026-09-07 -- Self-Paced Lecture Resources (migration 045).
     * A real Resources page (Glossary, FAQs, Abbreviations, VEO
     * Resources, Bibliography) by slug -- no unlock check at all, since
     * this content isn't gated or sequential, matching the real, live
     * course's own confirmed behavior. Still requires sign-in, though --
     * Resources content lives behind the same real student session as
     * everything else in this course.
     */
    public function showResource(Request $request, string $slug): View|RedirectResponse
    {
        $studentId = $request->session()->get('lecture_student_id');

        if (! $studentId) {
            return redirect()->route('lecture.sign-in');
        }

        $resource = $this->engine->getLectureResource($slug);
        $sections = $this->engine->getLectureSections($studentId);
        $resources = $this->engine->getLectureResources();

        return view('lecture.resource', [
            'resource' => $resource,
            'sections' => $sections,
            'resources' => $resources,
            'currentResourceSlug' => $slug,
        ]);
    }

    /**
     * Michael, 2026-09-07 -- real quiz-taking flow. Shows the real
     * quiz-taking view for a given quiz -- questions, choices, and this
     * student's own, current in-progress answers (if they step away
     * mid-quiz and come back). A 403 here means the section itself isn't
     * unlocked yet.
     */
    public function showQuiz(Request $request, int $quizId): View|RedirectResponse
    {
        $studentId = $request->session()->get('lecture_student_id');

        if (! $studentId) {
            return redirect()->route('lecture.sign-in');
        }

        try {
            $quiz = $this->engine->getLectureQuiz($studentId, $quizId);
        } catch (ComplianceEngineForbiddenException $e) {
            return redirect()->route('lecture.home-base')
                ->withErrors(['quiz' => "This section isn't available yet -- please pass the previous section's quiz first."]);
        }

        $sections = $this->engine->getLectureSections($studentId);
        $resources = $this->engine->getLectureResources();

        return view('lecture.quiz', ['quiz' => $quiz, 'sections' => $sections, 'resources' => $resources]);
    }

    /**
     * Michael, 2026-09-07 -- the real, immediate per-question submit,
     * matching the live course's own confirmed behavior: each answer
     * selection is its own, real form submission (a page reload), not
     * part of a single "submit all" action. Redirects back to the same
     * real quiz page either way -- if the quiz is now complete, the view
     * itself shows the real pass/fail banner using the flashed result.
     */
    public function submitQuizAnswer(Request $request, int $quizId, int $questionId): RedirectResponse
    {
        $studentId = $request->session()->get('lecture_student_id');

        if (! $studentId) {
            return redirect()->route('lecture.sign-in');
        }

        $validated = $request->validate(['choice_id' => ['required', 'integer']]);

        $result = $this->engine->submitLectureQuizAnswer($studentId, $questionId, $validated['choice_id']);

        return redirect()->route('lecture.quiz', $quizId)->with('quizResult', $result);
    }

    /**
     * Michael, 2026-09-07 -- the real Quiz Summary Page
     * (lecture-quiz.php with no real section id in the live course) --
     * every section at once, its own real question count and the
     * student's own most recent completed-attempt score, if any.
     */
    public function showQuizSummary(Request $request): View|RedirectResponse
    {
        $studentId = $request->session()->get('lecture_student_id');

        if (! $studentId) {
            return redirect()->route('lecture.sign-in');
        }

        $summary = $this->engine->getLectureQuizSummary($studentId);
        $sections = $this->engine->getLectureSections($studentId);
        $resources = $this->engine->getLectureResources();

        return view('lecture.quiz-summary', ['summary' => $summary, 'sections' => $sections, 'resources' => $resources]);
    }
}
