<?php

namespace App\Http\Controllers;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-09-04 -- Public Certificate Lookup, matching the real,
 * existing DIBs feature (compliance-assurance.com/certs.php and
 * certs-email-id.php). Confirmed with Michael: genuinely public, no
 * login at all, and living outside /admin/ and /account/ entirely --
 * not merely whitelisted within them, matching the real, existing
 * PublicCalendarController's own precedent for a top-level,
 * unauthenticated controller.
 */
class PublicCertLookupController extends Controller
{
    private ComplianceEngineClient $engine;

    public function __construct(ComplianceEngineClient $engine)
    {
        $this->engine = $engine;
    }

    public function showLookupForm(): View
    {
        return view('public.certs-lookup');
    }

    public function lookup(Request $request): View
    {
        $validated = $request->validate([
            'student_number' => ['required', 'string'],
            'last_name' => ['required', 'string'],
        ]);

        try {
            $result = $this->engine->publicCertLookup($validated['student_number'], $validated['last_name']);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return view('public.certs-lookup', ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return view('public.certs-lookup', ['error' => 'No record found matching that Student # and last name.']);
        }

        return view('public.certs-result', ['result' => $result]);
    }

    public function showStudentNumberLookupForm(): View
    {
        return view('public.certs-student-number-lookup');
    }

    public function lookupStudentNumber(Request $request): View
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'last_name' => ['required', 'string'],
        ]);

        try {
            $result = $this->engine->publicStudentNumberLookup($validated['email'], $validated['last_name']);
        } catch (\Throwable $e) {
            return view('public.certs-student-number-lookup', ['error' => 'No record found matching that email and last name.']);
        }

        return view('public.certs-student-number-lookup', ['studentNumber' => $result['studentNumber'] ?? null]);
    }
}
