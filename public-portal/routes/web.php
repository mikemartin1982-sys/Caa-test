<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\OnsiteController;
use App\Http\Controllers\PublicCalendarController;
use App\Http\Controllers\SmokeSchoolInfoController;
use App\Http\Controllers\PublicCertLookupController;
use App\Http\Controllers\SessionDetailController;
use App\Http\Controllers\LectureController;
use App\Http\Controllers\Account\ClientAuthController;
use App\Http\Controllers\Account\ClientRegistrationController;
use App\Http\Controllers\Account\AccountDashboardController;
use App\Http\Controllers\Account\EnrollmentController as AccountEnrollmentController;
use App\Http\Controllers\Admin\SessionDetailController as AdminSessionDetailController;
use App\Http\Controllers\Admin\RosterController;
use App\Http\Controllers\Admin\StaffAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\DigitalTestingAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site -- Public Calendar (Section 4e), session detail (Section 4d),
| and the "New Client Account" inquiry form (Section 3c). No auth.
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('public.home');
Route::get('/calendar', [PublicCalendarController::class, 'calendar'])->name('public.calendar');
Route::get('/training-map', [PublicCalendarController::class, 'map'])->name('public.map');
Route::get('/smoke-schools', [PublicCalendarController::class, 'list'])->name('public.list');
Route::get('/smoke-schools/{state}/{slug}', [SessionDetailController::class, 'show'])->name('public.session-detail');

// Michael, 2026-09-05 -- Smoke School Discovery pages (VR first;
// In-Person/Public/Private to follow individually before the nav
// itself gets consolidated to reflect all of them).
Route::get('/vr-smoke-school', [SmokeSchoolInfoController::class, 'vr'])->name('public.vr-smoke-school');
Route::get('/in-person-smoke-schools', [SmokeSchoolInfoController::class, 'inPerson'])->name('public.in-person-smoke-schools');
Route::get('/public-smoke-schools', [SmokeSchoolInfoController::class, 'publicSchools'])->name('public.public-smoke-schools');
Route::get('/private-smoke-schools', [SmokeSchoolInfoController::class, 'privateSchools'])->name('public.private-smoke-schools');
Route::post('/private-smoke-schools', [SmokeSchoolInfoController::class, 'submitPrivateInquiry'])->name('public.private-smoke-schools.submit');
Route::get('/new-clients', [SmokeSchoolInfoController::class, 'newClients'])->name('public.new-clients');
Route::get('/digital-student', [SmokeSchoolInfoController::class, 'digitalStudent'])->name('public.digital-student');
Route::get('/professional-services', [SmokeSchoolInfoController::class, 'professionalServices'])->name('public.professional-services');
Route::get('/veo-services-compliance-plans', [SmokeSchoolInfoController::class, 'veoServicesCompliancePlans'])->name('public.veo-services-compliance-plans');
Route::get('/veo-expertise', [SmokeSchoolInfoController::class, 'veoExpertise'])->name('public.veo-expertise');
Route::post('/veo-expertise', [SmokeSchoolInfoController::class, 'submitVeoExpertise'])->name('public.veo-expertise.submit');
Route::get('/veo-readings', [SmokeSchoolInfoController::class, 'veoReadings'])->name('public.veo-readings');
Route::get('/veo-form-instructions', [SmokeSchoolInfoController::class, 'veoFormInstructions'])->name('public.veo-form-instructions');
Route::get('/about-menu', [SmokeSchoolInfoController::class, 'aboutMenu'])->name('public.about-menu');
Route::get('/about', [SmokeSchoolInfoController::class, 'about'])->name('public.about');
Route::get('/why-choose-compliance', [SmokeSchoolInfoController::class, 'whyChooseCompliance'])->name('public.why-choose-compliance');
Route::get('/faqs', [SmokeSchoolInfoController::class, 'faqs'])->name('public.faqs');
Route::get('/about-team', [SmokeSchoolInfoController::class, 'aboutTeam'])->name('public.about-team');
Route::get('/our-customers', [SmokeSchoolInfoController::class, 'ourCustomers'])->name('public.our-customers');

Route::get('/become-a-client', [InquiryController::class, 'create'])->name('public.become-a-client');
Route::post('/become-a-client', [InquiryController::class, 'store'])->name('public.become-a-client.store');

// Digital Testing sign-in -- students only, no staff auth. Matches
// stacktest.net's real URL structure ("resolves to
// compliance-assurance.com/onsite/", per Michael, 2026-08-16). This is
// deliberately a SKELETON for the sign-in flow only (Session ID entry ->
// roster name dropdown -> hard-stop waiting screen) -- the underlying
// live session-state fields (sign-in/testing enabled, current run/point,
// Field Manager stage control) are NOT built yet, by explicit choice, so
// the waiting screen is static rather than reactive to real state.
Route::get('/onsite', [OnsiteController::class, 'index'])->name('onsite.index');
Route::post('/onsite', [OnsiteController::class, 'lookup'])->name('onsite.lookup');
Route::get('/onsite/{sessionId}', [OnsiteController::class, 'show'])->name('onsite.show');
Route::post('/onsite/{sessionId}/check-in', [OnsiteController::class, 'checkIn'])->name('onsite.check-in');
// Michael, 2026-09-18 -- dedicated GET so a returning student (session
// persisted server-side in checkIn(), see OnsiteController) can be
// redirected straight back into the waiting screen instead of only
// ever reaching it as the direct response to the check-in POST.
Route::get('/onsite/{sessionId}/waiting', [OnsiteController::class, 'waiting'])->name('onsite.waiting');
Route::get('/onsite/{sessionId}/stage', [OnsiteController::class, 'stage'])->name('onsite.stage');
Route::get('/onsite/{sessionId}/test', [OnsiteController::class, 'test'])->name('onsite.test');
Route::get('/onsite/{sessionId}/test/status', [OnsiteController::class, 'testStatus'])->name('onsite.test.status');
Route::post('/onsite/{sessionId}/test/submit-guess', [OnsiteController::class, 'submitTestGuess'])->name('onsite.test.submit-guess');
Route::post('/onsite/{sessionId}/test/confirm-final-answers', [OnsiteController::class, 'confirmTestFinalAnswers'])->name('onsite.test.confirm-final-answers');
Route::post('/onsite/{sessionId}/test/submit-signature', [OnsiteController::class, 'submitTestSignature'])->name('onsite.test.submit-signature');

/*
|--------------------------------------------------------------------------
| Account -- public self-serve registration + login (Michael, 2026-08-19),
| backed by the 'client' guard / ClientApiUserProvider. General
| prospective-client account creation, independent of VR vs. traditional
| testing -- NOT the same world as the "Client Portal" group directly
| below, which assumes a separate `users` table that was never finished/
| migrated. Confirmed with Michael: DIBs only ever allows ONE login per
| Client Portal, so the Client record itself being the account (this
| group's model) is the correct one going forward.
|--------------------------------------------------------------------------
*/
Route::get('/account/register', [ClientRegistrationController::class, 'showRegistrationForm'])->name('account.register');
Route::post('/account/register', [ClientRegistrationController::class, 'register'])->name('account.register.submit');
Route::get('/account/login', [ClientAuthController::class, 'showLoginForm'])->name('account.login');
Route::post('/account/login', [ClientAuthController::class, 'login'])->name('account.login.attempt');

// Michael, 2026-08-31 -- Password Reset feature. Unauthenticated by
// necessity, same as the login routes above -- no auth:client
// middleware group here.
Route::get('/account/password/forgot', [ClientAuthController::class, 'showForgotPasswordForm'])->name('account.password.forgot');
Route::post('/account/password/forgot', [ClientAuthController::class, 'sendResetLink'])->name('account.password.forgot.submit');
Route::get('/account/password/reset', [ClientAuthController::class, 'showResetForm'])->name('account.password.reset');
Route::post('/account/password/reset', [ClientAuthController::class, 'resetPassword'])->name('account.password.reset.submit');

// Michael, 2026-09-04 -- Public Certificate Lookup, matching the real,
// existing DIBs feature (certs.php/certs-email-id.php). Confirmed with
// Michael: genuinely public, no login at all, and living outside
// /admin/ and /account/ entirely -- deliberately at the top level
// (/certs), not merely whitelisted within an existing, authenticated
// route group. Route names use the same public.* convention already
// established by every other public-facing page (public.home,
// public.calendar, public.map) rather than a standalone certs.*
// prefix, for consistency.
Route::get('/certs', [PublicCertLookupController::class, 'showLookupForm'])->name('public.certs.lookup');
Route::post('/certs', [PublicCertLookupController::class, 'lookup'])->name('public.certs.lookup.submit');
Route::get('/certs/find-student-number', [PublicCertLookupController::class, 'showStudentNumberLookupForm'])->name('public.certs.find-student-number');
Route::post('/certs/find-student-number', [PublicCertLookupController::class, 'lookupStudentNumber'])->name('public.certs.find-student-number.submit');

Route::middleware(['auth:client'])->group(function () {
    Route::get('/account', [AccountDashboardController::class, 'index'])->name('account.dashboard');
    // Michael, 2026-08-24 -- restructured into separate pages matching
    // the real portal's own familiar nav (see AccountDashboardController's
    // own docblock) -- account.employees and account.edit are new;
    // account.update/employees.store/employees.update are the same
    // existing form-submission handlers, unchanged, just redirecting
    // back to their own page now instead of one combined dashboard.
    Route::get('/account/employees', [AccountDashboardController::class, 'employees'])->name('account.employees');
    Route::get('/account/edit', [AccountDashboardController::class, 'edit'])->name('account.edit');
    Route::patch('/account', [AccountDashboardController::class, 'update'])->name('account.update');
    Route::post('/account/employees', [AccountDashboardController::class, 'storeEmployee'])->name('account.employees.store');
    Route::patch('/account/employees/{student}', [AccountDashboardController::class, 'updateEmployee'])->name('account.employees.update');
    Route::post('/account/logout', [ClientAuthController::class, 'logout'])->name('account.logout');

    // Michael, 2026-08-24 -- "Enroll in both Traditional and Public VR
    // Session(s)." Reconciled from the orphaned Portal\EnrollmentController
    // (see Account\EnrollmentController's own docblock for the full
    // reasoning) -- that group ran on a separate, never-finished auth
    // system, not this real 'client' guard, and is now fully removed.
    Route::get('/account/enroll', [AccountEnrollmentController::class, 'index'])->name('account.enroll');
    Route::get('/account/current-enrollments', [AccountEnrollmentController::class, 'currentEnrollments'])->name('account.current-enrollments');
    Route::post('/account/enrollments', [AccountEnrollmentController::class, 'store'])->name('account.enrollments.store');
    Route::post('/account/authorized-clients', [AccountEnrollmentController::class, 'authorizeOutsideClient'])
        ->name('account.authorized-clients.store');
});

/*
|--------------------------------------------------------------------------
| Admin -- staff-facing Session Details (Section 4c) and Roster (Section
| 4f/4g). Backed by the 'staff' guard / StaffApiUserProvider -- credentials
| verified live against the Java API, no local staff password table.
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [StaffAuthController::class, 'showLoginForm'])->name('admin.login');

// Satisfies Laravel's default unauthenticated-redirect, which looks for
// a route literally named "login" -- this app's real login lives at
// admin.login, so this just forwards there. Fixes "Route [login] not
// defined" whenever a protected page is hit while logged out.
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

Route::post('/admin/login', [StaffAuthController::class, 'login'])->name('admin.login.attempt');

// Michael, 2026-08-31 -- Password Reset feature. Same reasoning as
// account.password.* above -- unauthenticated by necessity, no
// auth:staff middleware group.
Route::get('/admin/password/forgot', [StaffAuthController::class, 'showForgotPasswordForm'])->name('admin.password.forgot');
Route::post('/admin/password/forgot', [StaffAuthController::class, 'sendResetLink'])->name('admin.password.forgot.submit');
Route::get('/admin/password/reset', [StaffAuthController::class, 'showResetForm'])->name('admin.password.reset');
Route::post('/admin/password/reset', [StaffAuthController::class, 'resetPassword'])->name('admin.password.reset.submit');

Route::middleware(['auth:staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar');
    // Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop):
    // the frontend's "Save Changes" button calls this via fetch(), not
    // the Java endpoint's URL directly -- this project's browser JS
    // never talks to the Java API directly, always through Laravel.
    Route::post('/calendar/assignments', [AdminCalendarController::class, 'saveAssignments'])->name('calendar.assignments');

    // Michael, 2026-08-31 -- Truck/Trailer Equipment feature. Backend
    // (entities/controllers) already existed -- only this UI was
    // genuinely missing, confirmed with Michael directly.
    Route::get('/equipment', [\App\Http\Controllers\Admin\EquipmentController::class, 'index'])->name('equipment.index');
    Route::post('/equipment/trucks', [\App\Http\Controllers\Admin\EquipmentController::class, 'storeTruck'])->name('equipment.trucks.store');
    Route::post('/equipment/trailers', [\App\Http\Controllers\Admin\EquipmentController::class, 'storeTrailer'])->name('equipment.trailers.store');
    Route::get('/equipment/trailers/{trailer}', [\App\Http\Controllers\Admin\EquipmentController::class, 'showTrailer'])->name('equipment.trailers.show');
    Route::post('/equipment/trailers/{trailer}/testing-systems', [\App\Http\Controllers\Admin\EquipmentController::class, 'storeTestingSystem'])->name('equipment.testing-systems.store');
    Route::post('/equipment/trailers/{trailer}/panes', [\App\Http\Controllers\Admin\EquipmentController::class, 'storeCalibrationPane'])->name('equipment.panes.store');
    Route::patch('/equipment/trailers/{trailer}/panes/{pane}', [\App\Http\Controllers\Admin\EquipmentController::class, 'updateCalibrationPane'])->name('equipment.panes.update');
    Route::post('/equipment/testing-systems/{testingSystem}/maintenance-events', [\App\Http\Controllers\Admin\EquipmentController::class, 'storeMaintenanceEvent'])->name('equipment.maintenance-events.store');
    Route::post('/equipment/testing-systems/{testingSystem}/import-preview', [\App\Http\Controllers\Admin\EquipmentController::class, 'importPreview'])->name('equipment.import-preview');
    Route::post('/equipment/testing-systems/{testingSystem}/calibration-records', [\App\Http\Controllers\Admin\EquipmentController::class, 'submitCalibration'])->name('equipment.submit-calibration');
    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');

    Route::get('/staff', [\App\Http\Controllers\Admin\StaffManagementController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [\App\Http\Controllers\Admin\StaffManagementController::class, 'create'])->name('staff.create');
    Route::post('/staff', [\App\Http\Controllers\Admin\StaffManagementController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staff}/edit', [\App\Http\Controllers\Admin\StaffManagementController::class, 'edit'])->name('staff.edit');
    Route::patch('/staff/{staff}', [\App\Http\Controllers\Admin\StaffManagementController::class, 'update'])->name('staff.update');

    // Michael, 2026-08-25 -- QBO integration, layer 3. /callback is
    // deliberately NOT excluded from auth:staff -- the admin must
    // still be logged in when Intuit redirects back (they never left
    // our own session, just this browser tab briefly visited Intuit's
    // own consent page), matching how connect()/callback() both also
    // independently re-check Compliance-Administrator status.
    Route::get('/qbo', [\App\Http\Controllers\Admin\QboController::class, 'status'])->name('qbo.status');
    Route::get('/qbo/connect', [\App\Http\Controllers\Admin\QboController::class, 'connect'])->name('qbo.connect');
    Route::get('/qbo/callback', [\App\Http\Controllers\Admin\QboController::class, 'callback'])->name('qbo.callback');
    Route::post('/qbo/disconnect', [\App\Http\Controllers\Admin\QboController::class, 'disconnect'])->name('qbo.disconnect');

    Route::get('/clients', [\App\Http\Controllers\Admin\ClientManagementController::class, 'index'])->name('clients.index');
    Route::get('/employees', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'index'])->name('employees.index');
    Route::get('/employees/search', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'search'])->name('employees.search');
    Route::get('/employees/reassign', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'reassignForm'])->name('employees.reassign');
    Route::get('/employees/combine', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'combineStep1'])->name('employees.combine.step1');
    Route::get('/employees/combine/{client}', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'combineStep2'])->name('employees.combine.step2');
    Route::get('/employees/add', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'addEmployeeStep1'])->name('employees.add.step1');
    Route::get('/employees/add/{client}', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'addEmployeeStep2'])->name('employees.add.step2');
    // Michael, 2026-08-30 -- Lecture Certificate Upload feature. Global,
    // client-agnostic student route -- distinct from
    // admin.clients.employees.show below, needed for Employee Search
    // results with no employer client on file at all.
    Route::get('/students/{student}', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'showGlobal'])->name('students.show-global');
    Route::post('/students/{student}/lecture-certificate', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'uploadCertificate'])->name('students.upload-certificate');
    Route::get('/lecture-certificates/{certificate}/download', [\App\Http\Controllers\Admin\EmployeeSearchController::class, 'downloadCertificate'])->name('certificates.download');
    Route::get('/clients/{client}/edit', [\App\Http\Controllers\Admin\ClientManagementController::class, 'edit'])->name('clients.edit');
    Route::get('/clients/{client}/employees', [\App\Http\Controllers\Admin\ClientManagementController::class, 'employees'])->name('clients.employees');
    // Michael, 2026-08-25 -- Section 4a extension, piece 3A: the
    // Student detail page (certification history) -- deliberately its
    // own dedicated page, not folded into the employees table above,
    // matching Michael's own framing of this as "the Student page."
    Route::get('/clients/{client}/students/{student}', [\App\Http\Controllers\Admin\ClientManagementController::class, 'showStudent'])->name('students.show');
    Route::patch('/clients/{client}', [\App\Http\Controllers\Admin\ClientManagementController::class, 'update'])->name('clients.update');
    Route::post('/clients/{client}/purchase-orders', [\App\Http\Controllers\Admin\ClientManagementController::class, 'storePurchaseOrder'])->name('clients.purchase-orders.store');
    Route::patch('/clients/{client}/purchase-orders/{po}', [\App\Http\Controllers\Admin\ClientManagementController::class, 'updatePurchaseOrder'])->name('clients.purchase-orders.update');
    Route::post('/clients/{client}/purchase-orders/{po}/sessions', [\App\Http\Controllers\Admin\ClientManagementController::class, 'addPurchaseOrderSession'])->name('clients.purchase-orders.sessions.add');
    Route::delete('/clients/{client}/purchase-orders/{po}/sessions/{session}', [\App\Http\Controllers\Admin\ClientManagementController::class, 'removePurchaseOrderSession'])->name('clients.purchase-orders.sessions.remove');

    Route::patch('/clients/{client}/students/{student}', [\App\Http\Controllers\Admin\ClientManagementController::class, 'updateStudent'])->name('clients.students.update');
    Route::post('/clients/{client}/students', [\App\Http\Controllers\Admin\ClientManagementController::class, 'storeEmployee'])->name('clients.students.store');
    Route::patch('/clients/{client}/students/{student}/reassign', [\App\Http\Controllers\Admin\ClientManagementController::class, 'reassignStudent'])->name('clients.students.reassign');
    Route::post('/clients/{client}/students/{student}/combine', [\App\Http\Controllers\Admin\ClientManagementController::class, 'combineStudent'])->name('clients.students.combine');

    Route::get('/enroll', [\App\Http\Controllers\Admin\ManualEnrollController::class, 'create'])->name('enroll.create');
    Route::post('/enroll', [\App\Http\Controllers\Admin\ManualEnrollController::class, 'store'])->name('enroll.store');
    Route::get('/enroll/clients/{client}/students', [\App\Http\Controllers\Admin\ManualEnrollController::class, 'studentsForClient'])->name('enroll.students-for-client');
    Route::post('/enroll/clients/{client}/students', [\App\Http\Controllers\Admin\ManualEnrollController::class, 'storeStudent'])->name('enroll.store-student');
    Route::get('/enroll/lookup-session', [\App\Http\Controllers\Admin\ManualEnrollController::class, 'lookupSession'])->name('enroll.lookup-session');

    // Michael, 2026-08-31/09-01 -- QBO Per-Student Invoicing, Stage 3A.
    // Genuinely separate from Manual Enroll above, not a replacement --
    // confirmed with Michael as replacing only the Session Roster's own
    // "+ Enroll Student" link. Session is already known (from the URL
    // itself), so this starts at client search, not a session lookup.
    Route::get('/sessions/{session}/bulk-enroll', [\App\Http\Controllers\Admin\BulkEnrollController::class, 'show'])->name('enroll.bulk');
    Route::get('/sessions/{session}/bulk-enroll/clients/{client}/employees', [\App\Http\Controllers\Admin\BulkEnrollController::class, 'employeesForClient'])->name('enroll.bulk.employees-for-client');
    Route::post('/sessions/{session}/bulk-enroll', [\App\Http\Controllers\Admin\BulkEnrollController::class, 'store'])->name('enroll.bulk.store');
    Route::get('/sessions/{session}/bulk-enroll/confirm', [\App\Http\Controllers\Admin\BulkEnrollController::class, 'confirm'])->name('enroll.bulk.confirm');
    Route::post('/sessions/{session}/bulk-enroll/invoice-just-now', [\App\Http\Controllers\Admin\BulkEnrollController::class, 'invoiceJustNow'])->name('enroll.bulk.invoice-just-now');
    Route::post('/sessions/{session}/bulk-enroll/invoice-all-un-invoiced', [\App\Http\Controllers\Admin\BulkEnrollController::class, 'invoiceAllUnInvoiced'])->name('enroll.bulk.invoice-all-un-invoiced');
    // Michael, 2026-09-04 -- enroll.bulk.generate-private-invoice route removed -- billing moved to session close-out.

    // Michael, 2026-09-01 -- Client Auto-Notify feature. Confirmed
    // scoped to only our own known invoices, same records the
    // scheduled polling job already tracks.
    Route::get('/invoice-checker', [\App\Http\Controllers\Admin\InvoiceCheckerController::class, 'show'])->name('invoice-checker');
    Route::post('/invoice-checker/refresh', [\App\Http\Controllers\Admin\InvoiceCheckerController::class, 'refresh'])->name('invoice-checker.refresh');
    Route::post('/invoice-checker/retry-notification', [\App\Http\Controllers\Admin\InvoiceCheckerController::class, 'retryNotification'])->name('invoice-checker.retry-notification');
    Route::post('/invoice-checker/retry-private-notification', [\App\Http\Controllers\Admin\InvoiceCheckerController::class, 'retryPrivateNotification'])->name('invoice-checker.retry-private-notification');

    Route::get('/clients/search', [AdminSessionDetailController::class, 'searchClients'])->name('clients.search');
    Route::get('/digital-testing', [DigitalTestingAdminController::class, 'index'])->name('digital-testing.index');
    Route::get('/digital-testing/{session}/live-test/status-json', [DigitalTestingAdminController::class, 'liveTestStatusJson'])->name('digital-testing.live-test.status-json');
    Route::patch('/digital-testing/{session}', [DigitalTestingAdminController::class, 'update'])->name('digital-testing.update');
    Route::patch('/digital-testing/{session}/roster', [DigitalTestingAdminController::class, 'updateRosterStatuses'])->name('digital-testing.roster.update');
    Route::post('/digital-testing/{session}/live-test/start', [DigitalTestingAdminController::class, 'startLiveTest'])->name('digital-testing.live-test.start');
    Route::post('/digital-testing/{session}/live-test/record-true-value', [DigitalTestingAdminController::class, 'recordTrueValue'])->name('digital-testing.live-test.record-true-value');
    Route::post('/digital-testing/{session}/live-test/advance', [DigitalTestingAdminController::class, 'advanceLiveTest'])->name('digital-testing.live-test.advance');
    Route::post('/digital-testing/{session}/live-test/grade-test', [DigitalTestingAdminController::class, 'gradeLiveTest'])->name('digital-testing.live-test.grade-test');
    Route::post('/digital-testing/{session}/live-test/revisit', [DigitalTestingAdminController::class, 'revisitPoint'])->name('digital-testing.live-test.revisit');
    Route::post('/digital-testing/{session}/live-test/end-revisit', [DigitalTestingAdminController::class, 'endRevisit'])->name('digital-testing.live-test.end-revisit');

    Route::get('/sessions/{session}', [AdminSessionDetailController::class, 'show'])->name('sessions.show');
    Route::patch('/sessions/{session}', [AdminSessionDetailController::class, 'update'])->name('sessions.update');
    Route::put('/sessions/{session}/host', [AdminSessionDetailController::class, 'setHost'])->name('sessions.host.set');
    Route::post('/sessions/{session}/sync-qbo-class', [AdminSessionDetailController::class, 'syncQboClass'])->name('sessions.sync-qbo-class');
    // Michael, 2026-09-04 -- session close-out billing redesign, real, separate steps.
    Route::post('/sessions/{session}/generate-invoice', [AdminSessionDetailController::class, 'generateInvoice'])->name('sessions.generate-invoice');
    Route::post('/sessions/{session}/send-invoice', [AdminSessionDetailController::class, 'sendInvoice'])->name('sessions.send-invoice');
    Route::post('/sessions/{session}/days', [AdminSessionDetailController::class, 'addDay'])->name('sessions.days.add');
    Route::delete('/sessions/{session}/days/{day}', [AdminSessionDetailController::class, 'deleteDay'])->name('sessions.days.delete');
    Route::post('/sessions/{session}/send-confirmation-email', [AdminSessionDetailController::class, 'sendConfirmationEmail'])->name('sessions.send-confirmation-email');
    Route::post('/sessions/{session}/generate-bid', [AdminSessionDetailController::class, 'generateBid'])->name('sessions.generate-bid');
    Route::get('/sessions/{session}/bid-pdf', [AdminSessionDetailController::class, 'downloadBidPdf'])->name('sessions.bid-pdf');
    // Michael, 2026-08-25 -- Section 4a extension, piece 2/3B: real
    // .xlsx export for the Brevo retention-gap target list -- an
    // Excel output to be consumed directly, not a UI page.
    Route::get('/sessions/{session}/brevo-target-list', [AdminSessionDetailController::class, 'downloadBrevoTargetList'])->name('sessions.brevo-target-list');
    Route::post('/sessions/{session}/comments', [AdminSessionDetailController::class, 'storeComment'])->name('sessions.comments.store');
    Route::patch('/sessions/{session}/publish', [AdminSessionDetailController::class, 'publish'])->name('sessions.publish');
    Route::post('/sessions/{session}/copy-forward', [AdminSessionDetailController::class, 'copyForward'])->name('sessions.copy-forward');
    Route::post('/sessions/{session}/verify-info', [AdminSessionDetailController::class, 'verifyInfo'])->name('sessions.verify-info');
    Route::post('/sessions/{session}/notified-clients', [AdminSessionDetailController::class, 'addNotifiedClient'])->name('sessions.notified-clients.store');
    Route::delete('/sessions/{session}/notified-clients/{client}', [AdminSessionDetailController::class, 'removeNotifiedClient'])->name('sessions.notified-clients.destroy');

    Route::get('/sessions/{session}/roster', [RosterController::class, 'show'])->name('sessions.roster');
    Route::post('/sessions/{session}/send-summary-email', [RosterController::class, 'sendSummaryEmail'])->name('sessions.send-summary-email');
    Route::patch('/enrollments/{enrollment}/roster-status', [RosterController::class, 'updateStatus'])->name('roster.update-status');
    Route::delete('/enrollments/{enrollment}', [RosterController::class, 'unenroll'])->name('roster.unenroll');
});

// -----------------------------------------------------------------------
// Michael, 2026-09-06 -- Self-Paced Lecture course shell (migration 044).
// Deliberately its own, separate route group -- its own session state
// (lecture_student_id, not Laravel's own Auth guard), its own layout, no
// shared nav at all, matching the real, live course's own genuinely
// different structure. /lecture prefix avoids any real naming collision
// with the rest of this app's own top-level routes.
// -----------------------------------------------------------------------
Route::prefix('lecture')->name('lecture.')->group(function () {
    Route::get('/', [LectureController::class, 'showSignIn'])->name('sign-in');
    Route::post('/', [LectureController::class, 'submitSignIn'])->name('sign-in.submit');
    Route::post('/sign-out', [LectureController::class, 'signOut'])->name('sign-out');
    Route::get('/home-base', [LectureController::class, 'homeBase'])->name('home-base');
    Route::get('/pages/{page}', [LectureController::class, 'showPage'])->name('page');
    Route::post('/pages/{page}/read', [LectureController::class, 'markPageRead'])->name('page.read');
    Route::get('/resources/{slug}', [LectureController::class, 'showResource'])->name('resource');
    Route::get('/quiz-summary', [LectureController::class, 'showQuizSummary'])->name('quiz-summary');
    Route::get('/quizzes/{quiz}', [LectureController::class, 'showQuiz'])->name('quiz');
    Route::post('/quizzes/{quiz}/questions/{question}/answer', [LectureController::class, 'submitQuizAnswer'])->name('quiz.answer');
});

require __DIR__.'/mailbox.php';
