<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\View\View;

/**
 * Michael, 2026-09-01 -- Client Auto-Notify feature. Confirmed with
 * Michael as scoped to only our own known invoices (real Payment
 * rows) -- the exact same records the scheduled polling job already
 * tracks, nothing broader (no raw, QBO-wide invoice listing).
 */
class InvoiceCheckerController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    /**
     * Michael, 2026-09-03 -- both the Public (Payment-based) and
     * Private/Semi-Private (Enrollment-based) tables live on this same
     * page now, as two genuinely separate sections, not merged into
     * one -- confirmed with Michael: "QBO Invoice" as a concept
     * doesn't apply to the Private/Semi-Private rows at all, so mixing
     * them into one table would just show a confusing, blank column
     * for half the rows. Every action on this page reloads both
     * datasets together, since both tables are always visible
     * regardless of which one triggered the reload.
     */
    private function viewData(array $extra = []): array
    {
        return array_merge([
            'invoices' => $this->engine->knownInvoices(),
            'privateNotifications' => $this->engine->privateNotifications(),
        ], $extra);
    }

    public function show(): View
    {
        return view('admin.invoice-checker', $this->viewData());
    }

    /**
     * Michael, 2026-09-01 -- "Refresh" -- Option B, confirmed with
     * Michael: genuinely re-checks and acts, not just re-displays.
     * Real side effects (may mark invoices paid, may send real Brevo
     * emails) -- a POST, not a GET, matching how every other side-
     * effecting action in this project is routed. Public-side only --
     * there's no equivalent "re-check QBO" concept for the Private/
     * Semi-Private table at all, since those rows were never billed
     * in the first place.
     */
    public function refresh(): View
    {
        return view('admin.invoice-checker', $this->viewData([
            'invoices' => $this->engine->refreshInvoices(),
            'justRefreshed' => true,
        ]));
    }

    /**
     * Michael, 2026-09-02 -- Client Auto-Notify feature. Real, manual
     * retry for a Public invoice stuck at "Not yet" -- a real side
     * effect (may send real Brevo emails), so a POST, matching the
     * same routing convention as refresh() above.
     */
    public function retryNotification(\Illuminate\Http\Request $request): View
    {
        $qbInvoiceId = $request->input('qb_invoice_id');
        return view('admin.invoice-checker', $this->viewData([
            'invoices' => $this->engine->retryNotification($qbInvoiceId),
            'justRefreshed' => true,
        ]));
    }

    /**
     * Michael, 2026-09-03 -- real, manual retry for the Private/Semi-
     * Private table -- mirrors retryNotification() above, but keyed
     * by enrollment ID, not a QBO invoice ID.
     */
    public function retryPrivateNotification(\Illuminate\Http\Request $request): View
    {
        $enrollmentId = (int) $request->input('enrollment_id');
        return view('admin.invoice-checker', $this->viewData([
            'privateNotifications' => $this->engine->retryPrivateNotification($enrollmentId),
            'justRefreshed' => true,
        ]));
    }
}
