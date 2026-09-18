package com.caa.platform.integration.qbo;

import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentComponents;
import com.caa.platform.enrollment.Payment;
import com.caa.platform.enrollment.PaymentRepository;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionDay;
import com.caa.platform.session.SessionDayRepository;
import com.caa.platform.student.Student;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Service;
import org.springframework.web.client.HttpClientErrorException;
import org.springframework.web.client.RestTemplate;

import java.time.format.DateTimeFormatter;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Michael, 2026-09-01 -- Client Auto-Notify feature. Pattern matched
 * directly to BrevoSyncService's own, established approach (@Value
 * config, plain RestTemplate, "api-key" header, log-and-move-on
 * failure tolerance) -- this is a genuinely separate concern from that
 * service, though (Brevo's real transactional /smtp/email endpoint,
 * not the /contacts campaign-sync one), so kept as its own class
 * rather than folded into BrevoSyncService.
 *
 * Confirmed with Michael: Field and Lecture components both supported
 * -- VR remains deferred until its own access/delivery mechanism
 * exists. Any other enrollment on this same invoice is silently
 * skipped, not sent an incomplete or misleading email.
 */
@Service
public class QboPaymentNotificationService {

    private static final Logger log = LoggerFactory.getLogger(QboPaymentNotificationService.class);
    private static final DateTimeFormatter DATE_FORMAT = DateTimeFormatter.ofPattern("MMMM d, yyyy");
    private static final DateTimeFormatter TIME_FORMAT = DateTimeFormatter.ofPattern("h:mm a");

    private final PaymentRepository paymentRepository;
    private final SessionDayRepository sessionDayRepository;
    private final RestTemplate restTemplate = new RestTemplate();

    @Value("${app.brevo.api-key:}")
    private String apiKey;

    @Value("${app.brevo.base-url:https://api.brevo.com/v3}")
    private String baseUrl;

    /**
     * Michael, 2026-09-01 -- deliberately NOT reusing app.mail.from --
     * that's the separate SMTP/Mailpit sender, never verified with
     * Brevo itself. Brevo requires its own, pre-authenticated sender
     * identity (Single Sender Verification or full domain
     * authentication, confirmed against Brevo's own current docs) --
     * a real, dedicated config key here rather than assuming those two
     * addresses are the same, already-verified one.
     */
    @Value("${app.brevo.sender-email:}")
    private String senderEmail;

    @Value("${app.brevo.sender-name:Compliance Assurance Associates, Inc.}")
    private String senderName;

    /**
     * Michael, 2026-09-02 -- Client Auto-Notify feature. Real,
     * Michael-designed template built directly in Brevo's own editor
     * (Transactional > Templates) -- switches this service from
     * building raw htmlContent to referencing this template + a real
     * params object, matching Brevo's own, confirmed API shape
     * (templateId and htmlContent are mutually exclusive per request).
     */
    @Value("${app.brevo.enrollment-notification-template-id}")
    private Integer enrollmentNotificationTemplateId;

    /**
     * Michael, 2026-09-03 -- LECTURE_ONLY path. A genuinely separate
     * template from the Field one above -- confirmed with Michael as
     * needing entirely different content (no location/time at all,
     * just the real, existing self-paced lecture access URL and
     * Student ID).
     */
    @Value("${app.brevo.lecture-notification-template-id}")
    private Integer lectureNotificationTemplateId;

    public QboPaymentNotificationService(PaymentRepository paymentRepository, SessionDayRepository sessionDayRepository) {
        this.paymentRepository = paymentRepository;
        this.sessionDayRepository = sessionDayRepository;
    }

    /**
     * Michael, 2026-09-01 -- called once a QBO invoice is confirmed
     * paid (QboPaymentPollingService). One real Brevo email per
     * student, one-on-one, confirmed with Michael as preferred over a
     * single combined payload handed to Brevo's own automation.
     */
    public void notifyStudentsForInvoice(String qbInvoiceId) {
        List<Payment> payments = paymentRepository.findByQbInvoiceId(qbInvoiceId);

        // Michael, 2026-09-01 -- found live: this API-key check
        // previously returned before the loop even started, meaning
        // NONE of this invoice's Payment rows ever got a recorded
        // reason at all when the key was missing -- exactly the case
        // hit during tonight's own real test. Every applicable Payment
        // (Field-only, non-VR) gets its own recorded failure now, even
        // for this global, config-level cause.
        if (apiKey == null || apiKey.isBlank()) {
            log.warn("Brevo API key not configured (app.brevo.api-key / BREVO_API_KEY) -- skipping student notifications for Invoice {}", qbInvoiceId);
            for (Payment payment : payments) {
                if (isNotifiable(payment)) {
                    recordFailure(payment, "Brevo API key not configured.");
                }
            }
            return;
        }

        for (Payment payment : payments) {
            if (isNotifiable(payment)) {
                sendNotification(payment);
            }
        }
    }

    /**
     * Michael, 2026-09-02 -- found live: this only ever checked
     * component/VR eligibility -- calling notifyStudentsForInvoice()
     * a second time for the same invoice (e.g. a real, manual retry
     * for students genuinely stuck at "Not yet") would have
     * unconditionally re-sent to every eligible student, INCLUDING
     * ones already successfully notified -- a real duplicate-send bug.
     * Now also skips anything with a real brevoNotifiedAt already set,
     * making this method genuinely safe to call more than once for
     * the same invoice.
     */
    /**
     * Michael, 2026-09-03 -- expanded from Field-only to also allow
     * LECTURE_ONLY, confirmed with Michael this morning. VR exclusion
     * kept for BOTH components -- VR remains deferred regardless of
     * which component is involved, matching the same, already-
     * established principle (own access/delivery mechanisms don't
     * exist yet).
     */
    private boolean isNotifiable(Payment payment) {
        Enrollment enrollment = payment.getEnrollment();
        EnrollmentComponents components = enrollment.getEnrollmentComponents();
        return (components == EnrollmentComponents.FIELD_ONLY || components == EnrollmentComponents.LECTURE_ONLY)
                && !enrollment.getSession().isVrSession()
                && payment.getBrevoNotifiedAt() == null;
    }

    /**
     * Michael, 2026-09-01 -- takes the Payment itself now, not just
     * the Enrollment, so every real outcome (success, or a specific,
     * real reason for not sending) can be persisted back onto it --
     * confirmed with Michael: the new invoice-status readout needs to
     * show whether notification actually happened, which nothing
     * previously recorded anywhere, only logged.
     */
    private void sendNotification(Payment payment) {
        Enrollment enrollment = payment.getEnrollment();
        Student student = enrollment.getStudent();
        Session session = enrollment.getSession();

        if (student.getEmail() == null || student.getEmail().isBlank()) {
            log.warn("Student {} has no email on file -- cannot send enrollment notification.", student.getId());
            recordFailure(payment, "Student has no email on file.");
            return;
        }
        if (senderEmail == null || senderEmail.isBlank()) {
            log.warn("Brevo sender email not configured (app.brevo.sender-email) -- skipping notification for Student {}", student.getId());
            recordFailure(payment, "Brevo sender email not configured.");
            return;
        }

        // Michael, 2026-09-03 -- template AND params both now branch on
        // the enrollment's own component. Field keeps the existing,
        // proven params (date/time/location/mapUrl); Lecture is
        // genuinely simpler -- confirmed with Michael as needing only
        // studentName/studentNumber, no location/time at all, since
        // it's a self-paced online course, not tied to a physical
        // Session date. Building the Field-only params (SessionDay
        // lookup, buildMapUrl()) is skipped entirely for a Lecture
        // send -- no reason to do that work for a template that
        // wouldn't use any of it.
        boolean isLecture = enrollment.getEnrollmentComponents() == EnrollmentComponents.LECTURE_ONLY;
        Map<String, Object> params = isLecture ? buildLectureParams(student) : buildFieldParams(student, session);
        Integer templateId = isLecture ? lectureNotificationTemplateId : enrollmentNotificationTemplateId;

        // Michael, 2026-09-02 -- switched from building raw htmlContent
        // to referencing Michael's own, real, Brevo-editor-built
        // templates with a real params object -- confirmed against
        // Brevo's own current docs as mutually exclusive with
        // htmlContent. subject deliberately NOT set here -- Brevo's
        // own docs confirm each template's own subject line (set
        // directly in Michael's editor, itself able to use
        // {{params...}} too) is used as the default unless explicitly
        // overridden, and there's no reason to override it from here.
        Map<String, Object> body = new HashMap<>();
        body.put("sender", Map.of("name", senderName, "email", senderEmail));
        body.put("to", List.of(Map.of("email", student.getEmail(), "name", student.getName())));
        body.put("templateId", templateId);
        body.put("params", params);

        HttpHeaders headers = new HttpHeaders();
        headers.set("api-key", apiKey);
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.setAccept(List.of(MediaType.APPLICATION_JSON));

        try {
            restTemplate.postForEntity(baseUrl + "/smtp/email", new HttpEntity<>(body, headers), Map.class);
            log.info("Sent enrollment notification to Student {} ({})", student.getId(), student.getEmail());
            payment.setBrevoNotifiedAt(java.time.OffsetDateTime.now());
            payment.setBrevoNotificationError(null);
            paymentRepository.save(payment);
        } catch (HttpClientErrorException e) {
            log.error("Brevo notification failed for Student {}: HTTP {} -- {}",
                    student.getId(), e.getStatusCode(), e.getResponseBodyAsString());
            recordFailure(payment, "HTTP " + e.getStatusCode() + ": " + e.getResponseBodyAsString());
        } catch (Exception e) {
            log.error("Brevo notification failed for Student {}", student.getId(), e);
            recordFailure(payment, e.getMessage());
        }
    }

    /**
     * Michael, 2026-09-01 -- centralizes persisting a real, honest
     * failure reason -- brevoNotifiedAt deliberately left untouched
     * (still null, or still whatever a genuinely earlier success set
     * it to) so an attempt that fails after a prior success is never
     * silently overwritten to look successful.
     */
    private void recordFailure(Payment payment, String reason) {
        payment.setBrevoNotificationError(reason);
        paymentRepository.save(payment);
    }

    private String nonBlank(String s) {
        return s != null ? s : "";
    }

    /**
     * Michael, 2026-09-03 -- Field template's own real, proven params
     * -- extracted unchanged from what sendNotification() already
     * built directly, just given its own name now that a second,
     * genuinely different param set (Lecture) also exists.
     */
    private Map<String, Object> buildFieldParams(Student student, Session session) {
        // Michael, 2026-09-02 -- confirmed with Michael: startTime
        // should come from the SAME SessionDay row as the date itself
        // ("linked together"), not a separate, potentially-mismatched
        // lookup -- fetched ONCE here, both values derived from that
        // same result. Verified directly (not assumed) that
        // DateTimeFormatter's "h:mm a" pattern produces "8:00 AM"-style
        // output correctly.
        java.util.Optional<SessionDay> firstDay = sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(session.getId());
        String sessionDate = firstDay
                .map(SessionDay::getSessionDate)
                .map(d -> d.format(DATE_FORMAT))
                .orElse("a date to be confirmed");
        String sessionStartTime = firstDay
                .map(SessionDay::getStartTime)
                .map(t -> t.format(TIME_FORMAT))
                .orElse("a time to be confirmed");

        Map<String, Object> params = new HashMap<>();
        params.put("studentName", student.getName());
        params.put("sessionLocationName", nonBlank(session.getLocationName()));
        params.put("sessionAddressStreet", nonBlank(session.getAddressStreet()));
        params.put("sessionAddressCity", nonBlank(session.getAddressCity()));
        params.put("sessionAddressState", nonBlank(session.getAddressState()));
        params.put("sessionDate", sessionDate);
        params.put("sessionStartTime", sessionStartTime);
        params.put("studentNumber", student.getStudentNumber());
        params.put("mapUrl", buildMapUrl(session));
        return params;
    }

    /**
     * Michael, 2026-09-03 -- Lecture template's own params. Confirmed
     * with Michael as deliberately simpler -- no location/time at all,
     * just studentName/studentNumber, since this is a self-paced
     * online course with a real, fixed access URL (built directly
     * into the template itself, not passed as a param -- it's static,
     * not per-student).
     */
    private Map<String, Object> buildLectureParams(Student student) {
        Map<String, Object> params = new HashMap<>();
        params.put("studentName", student.getName());
        params.put("studentNumber", student.getStudentNumber());
        return params;
    }

    /**
     * Michael, 2026-09-02 -- "Map It" feature. Real, working Google
     * Maps URL, verified directly against Google's own current docs
     * (developers.google.com/maps/documentation/urls) as the
     * official, documented "Maps URLs API" search format -- not the
     * simpler but less formally-supported ?q= shorthand. Prefers real
     * GPS coordinates (Session.gridLat/gridLng) when set; falls back
     * to the real, text address otherwise, rather than sending a
     * broken or empty link for a session that never had coordinates
     * entered. Google's own docs confirm the comma between lat/lng
     * specifically doesn't need URL-encoding -- only the address
     * fallback (which can contain spaces) does.
     */
    private String buildMapUrl(Session session) {
        if (session.getGridLat() != null && session.getGridLng() != null) {
            return "https://www.google.com/maps/search/?api=1&query="
                    + session.getGridLat() + "," + session.getGridLng();
        }

        String address = String.join(" ", nonBlank(session.getLocationName()), nonBlank(session.getAddressStreet()),
                nonBlank(session.getAddressCity()), nonBlank(session.getAddressState())).trim();
        if (address.isBlank()) {
            return "";
        }
        return "https://www.google.com/maps/search/?api=1&query="
                + java.net.URLEncoder.encode(address, java.nio.charset.StandardCharsets.UTF_8);
    }
}
