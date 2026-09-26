<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-08-24 -- "Find Employee": a global, cross-client
 * search, distinct from the per-client Employees roster
 * (ClientManagementController::employees()). Every existing student
 * lookup requires already knowing the employer client; this is the
 * counterpart when staff only know a name, email, phone, or ID.
 */
class EmployeeSearchController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    public function index(Request $request): View
    {
        $query = $request->query('q', '');
        $students = $query !== '' ? $this->engine->searchStudents($query) : [];

        return view('admin.employees.index', [
            'students' => $students,
            'query' => $query,
        ]);
    }

    /** AJAX -- backs the flyout, same shape as admin.clients.search elsewhere. */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        return response()->json($query !== '' ? $this->engine->searchStudents($query) : []);
    }

    /**
     * Michael, 2026-08-24 -- "Combine Employee": a standalone,
     * company-scoped flow matching real DIBs source Michael provided
     * for this exact purpose. Step 1: pick a company (this method).
     * Step 2: pick two of that company's own employees to merge
     * (combineStep2()).
     */
    public function combineStep1(): View
    {
        return view('admin.employees.combine-step1');
    }

    /** Step 2: that company's own employee list, with two radio columns to pick which two get merged. */
    public function combineStep2(int $client): View
    {
        return view('admin.employees.combine-step2', [
            'clientRecord' => $this->engine->getClient($client),
            'students' => $this->engine->listStudentsForClient($client),
        ]);
    }

    /**
     * Michael, 2026-08-24 -- "Reassign Employee": a standalone page,
     * matching real DIBs source Michael provided for this exact
     * purpose. No server-side data needed at all -- both search
     * flyouts and the actual reassignment are entirely AJAX/form-driven
     * against endpoints that already exist (admin.employees.search,
     * admin.clients.search, admin.clients.students.reassign).
     */
    public function reassignForm(): View
    {
        return view('admin.employees.reassign');
    }

    /**
     * Michael, 2026-08-24 -- "Add Employee": same two-step,
     * company-scoped shape as Combine (Michael's source for this page
     * was only the step-1 company picker; step 2's actual form isn't
     * shown in DIBs' own source, so this reuses our own already-tested
     * student creation logic -- ComplianceEngineClient::createStudent(),
     * the same call Manual Enroll's own inline "add employee" flow
     * already makes -- rather than guessing at DIBs' field layout).
     * Step 1: pick a company.
     */
    public function addEmployeeStep1(): View
    {
        return view('admin.employees.add-step1');
    }

    /** Step 2: the actual add-employee form for that one company. */
    public function addEmployeeStep2(int $client): View
    {
        return view('admin.employees.add-step2', [
            'clientRecord' => $this->engine->getClient($client),
        ]);
    }

    /**
     * Michael, 2026-08-30 -- Lecture Certificate Upload feature. The
     * new, client-agnostic student detail page -- confirmed with
     * Michael as built proactively now, not deferred, since without it
     * an Employee Search result for a student with no employer client
     * on file (a real, expected case from the future legacy-data
     * migration) would be a dead end with nowhere to link to at all.
     * Lives here, not ClientManagementController -- that one's every
     * method is inherently client-centered, and this genuinely isn't.
     */
    public function showGlobal(int $student): View
    {
        return view('admin.students.show-global', [
            'student' => $this->engine->getStudent($student),
            'lectureCertificateHistory' => $this->engine->getLectureCertificateHistory($student),
            'providers' => $this->engine->listProviders(),
        ]);
    }

    /**
     * Michael, 2026-08-30 -- Lecture Certificate Upload feature.
     * Shared between both the client-scoped and the new global student
     * pages -- the Java endpoint itself isn't client-scoped at all
     * (/students/{id}/lecture-certificate), so one route serves both,
     * rather than duplicating this per page. Staff-only, confirmed
     * with Michael -- the logged-in staff member's own id is who
     * uploadedBy records, not something the form itself supplies.
     */
    public function uploadCertificate(Request $request, int $student): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'in:CAA_LECTURE,THIRD_PARTY'],
            'provider_id' => ['nullable', 'integer'],
            'completion_date' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        try {
            $this->engine->uploadLectureCertificate($student, [
                'source' => $validated['source'],
                'providerId' => $validated['provider_id'] ?? null,
                'completionDate' => $validated['completion_date'],
                'staffUserId' => \Illuminate\Support\Facades\Auth::id(),
            ], $request->file('file'));
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not upload certificate: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('status', 'Could not upload certificate -- check the server log for the actual cause.');
        }

        return back()->with('status', 'Certificate uploaded.');
    }

    /**
     * Michael, 2026-08-30 -- streams the actual file back, not a
     * redirect to some Java-side URL -- the compliance-engine host
     * isn't meant to be reachable directly from a browser, matching
     * this whole project's architecture (browser talks to Laravel
     * only). Confirmed visible to clients, students, and staff -- no
     * additional role check here beyond whatever auth middleware
     * already covers before this route is ever reached.
     */
    public function downloadCertificate(int $certificate): \Illuminate\Http\Response
    {
        $response = $this->engine->downloadLectureCertificate($certificate);

        if ($response->failed()) {
            abort($response->status() === 404 ? 404 : 500, 'Could not retrieve this certificate.');
        }

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'Content-Disposition' => $response->header('Content-Disposition') ?: 'attachment',
        ]);
    }
}