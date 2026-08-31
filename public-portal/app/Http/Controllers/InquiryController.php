<?php

namespace App\Http\Controllers;

use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Section 3c: the public "New Client Account" form. Submits as structured
 * data straight to the Inquiry endpoint -- deliberately does NOT create a
 * Client (security decision against bot/bad-actor abuse of a public
 * form). Staff review and convert manually in the admin dashboard.
 */
class InquiryController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function create(): View
    {
        return view('public.become-a-client');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip' => ['nullable', 'string', 'max:10'],
            'lead_source' => ['nullable', 'string', 'max:100'],
            'pref_newsletter' => ['boolean'],
            'pref_class_confirms' => ['boolean'],
            'pref_cert_reminders' => ['boolean'],
        ]);

        $this->engine->submitInquiry([
            'company' => $validated['company'],
            'firstName' => $validated['first_name'] ?? null,
            'lastName' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'companyAddress' => $validated['company_address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip' => $validated['zip'] ?? null,
            'leadSource' => $validated['lead_source'] ?? null,
            'prefNewsletter' => $validated['pref_newsletter'] ?? false,
            'prefClassConfirms' => $validated['pref_class_confirms'] ?? false,
            'prefCertReminders' => $validated['pref_cert_reminders'] ?? false,
        ]);

        return redirect()
            ->route('public.become-a-client')
            ->with('status', 'Thanks -- your inquiry has been received. A CAA team member will follow up shortly.');
    }
}
