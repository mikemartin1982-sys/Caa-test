<?php

namespace App\Http\Controllers;

use App\Mail\PrivateSmokeSchoolInquiry;
use App\Mail\VeoExpertiseInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Michael, 2026-09-05 -- Smoke School Discovery pages. Confirmed with
 * Michael: real, informational content for VR, In-Person, Public, and
 * Private smoke schools, so prospective clients can find what they're
 * looking for in a drilled-down fashion. VR/In-Person/Public each
 * point at the existing, real "Become a Client" self-service
 * registration flow rather than a dedicated form -- confirmed with
 * Michael as sufficient when the page itself explains the offering
 * well. Private is the one real exception -- confirmed with Michael
 * as a genuine "request a quote" inquiry, kept as its own real form.
 *
 * A single, dedicated controller for all four pages in this series,
 * rather than scattering them across unrelated controllers.
 */
class SmokeSchoolInfoController extends Controller
{
    public function vr(): View
    {
        return view('public.vr-smoke-school');
    }

    public function inPerson(): View
    {
        return view('public.in-person-smoke-schools');
    }

    public function publicSchools(): View
    {
        return view('public.public-smoke-schools');
    }

    public function privateSchools(): View
    {
        return view('public.private-smoke-schools');
    }

    public function newClients(): View
    {
        return view('public.new-clients');
    }

    public function digitalStudent(): View
    {
        return view('public.digital-student');
    }

    public function professionalServices(): View
    {
        return view('public.professional-services');
    }

    public function veoServicesCompliancePlans(): View
    {
        return view('public.veo-services-compliance-plans');
    }

    public function veoExpertise(): View
    {
        return view('public.veo-expertise');
    }

    public function veoReadings(): View
    {
        return view('public.veo-readings');
    }

    public function veoFormInstructions(): View
    {
        return view('public.veo-form-instructions');
    }

    public function aboutMenu(): View
    {
        return view('public.about-menu');
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function whyChooseCompliance(): View
    {
        return view('public.why-choose-compliance');
    }

    public function faqs(): View
    {
        return view('public.faqs');
    }

    public function aboutTeam(): View
    {
        return view('public.about-team');
    }

    public function ourCustomers(): View
    {
        return view('public.our-customers');
    }

    /**
     * Michael, 2026-09-06 -- VEO Expertise request form. Same real
     * pattern as submitPrivateInquiry(): no database record at all,
     * pure internal notification email to
     * client@compliance-assurance.com, honeypot instead of a real
     * captcha, silent rejection on a filled honeypot.
     */
    public function submitVeoExpertise(Request $request): RedirectResponse
    {
        if (!empty($request->input('website'))) {
            return redirect()->route('public.veo-expertise')->with('status', 'Thank you -- your request has been sent.');
        }

        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:50'],
            'lname' => ['required', 'string', 'max:50'],
            'company' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'emailconfirm' => ['required', 'email', 'same:email'],
            'help' => ['required', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        Mail::to('client@compliance-assurance.com')->send(new VeoExpertiseInquiry($validated));

        return redirect()->route('public.veo-expertise')->with('status', 'Thank you -- your request has been sent. A member of our team will be in touch soon.');
    }

    /**
     * Michael, 2026-09-05 -- confirmed with Michael: no database
     * record at all -- staff already have real templates to respond
     * to this and other inquiries manually, this is purely a real,
     * internal notification email. Goes to
     * client@compliance-assurance.com specifically, NOT info@ (the
     * general contact address used elsewhere on the real, live site).
     *
     * Honeypot field ('website', a real, standard convention -- named
     * to look like something worth filling in, genuinely hidden from
     * real users via CSS, but a bot filling every visible field would
     * still see and fill it) -- confirmed with Michael as sufficient
     * for now; a real CAPTCHA can replace this later if needed.
     * Rejection here is silent (redirect back with the same success
     * message) rather than a real, visible error -- telling a bot
     * "you got caught" only teaches it to route around this specific
     * check next time.
     */
    public function submitPrivateInquiry(Request $request): RedirectResponse
    {
        if (!empty($request->input('website'))) {
            return redirect()->route('public.private-smoke-schools')->with('status', 'Thank you -- your request has been sent.');
        }

        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:50'],
            'lname' => ['required', 'string', 'max:50'],
            'company' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'employees' => ['required', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        Mail::to('client@compliance-assurance.com')->send(new PrivateSmokeSchoolInquiry($validated));

        return redirect()->route('public.private-smoke-schools')->with('status', 'Thank you -- your request has been sent. A member of our team will be in touch soon.');
    }
}
