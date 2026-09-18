package com.caa.platform.integration.qbo;

import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentComponents;
import com.caa.platform.enrollment.EnrollmentRepository;
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
import java.util.Optional;

/**
 * Michael, 2026-09-02 -- Client Auto-Notify feature, Private/Semi-
 * Private path. Deliberately a separate class from
 * QboPaymentNotificationService, not a shared/extended one -- that
 * class's own name and entire design center on "notify once a QBO
 * payment is confirmed," which has nothing to do with this trigger at
 * all. Confirmed with Michael directly: Private/Semi-Private don't
 * bill until session close, so waiting on payment would mean a
 * student's "here's where and when" email arriving only after their
 * own session already happened -- defeating the whole point. This
 * fires synchronously at Enrollment creation itself instead, with no
 * dependency on Payment/QBO whatsoever.
 *
 * Confirmed strictly scoped to PRIVATE/SEMI_PRIVATE only -- PUBLIC
 * already has its own, correct, payment-based path (firing both would
 * mean a Public student getting two separate emails). PROPOSED is
 * confirmed by Michael as never having real enrollments at all
 * (scoping-only, pre-commitment). VTCA is confirmed as externally
 * operated -- CAA provides the service, but enrollment/roster is
 * handled entirely by VTCA's own operators, so no notification from
 * this system is appropriate there either.
 *
 * Only the mechanical "call Brevo's API" shape is shared with
 * QboPaymentNotificationService -- everything else (trigger, template,
 * map-URL strictness) is deliberately independent.
 */
@Service
public class PrivateEnrollmentNotificationService {

    private static final Logger log = LoggerFactory.getLogger(PrivateEnrollmentNotificationService.class);
    private static final DateTimeFormatter DATE_FORMAT = DateTimeFormatter.ofPattern("MMMM d, yyyy");
    private static final DateTimeFormatter TIME_FORMAT = DateTimeFormatter.ofPattern("h:mm a");

    private final SessionDayRepository sessionDayRepository;
    private final EnrollmentRepository enrollmentRepository;
    private final RestTemplate restTemplate = new RestTemplate();

    @Value("${app.brevo.api-key:}")
    private String apiKey;

    @Value("${app.brevo.base-url:https://api.brevo.com/v3}")
    private String baseUrl;

    @Value("${app.brevo.sender-email:}")
    private String senderEmail;

    @Value("${app.brevo.sender-name:Compliance Assurance Associates, Inc.}")
    private String senderName;

    /**
     * Michael, 2026-09-02 -- found live: after actually building the
     * real Public template (425) himself, Michael had already made
     * the copy fully generic ("a smoke school," not "public smoke
     * school") -- confirmed together the same template genuinely
     * works for both paths, so this deliberately reuses the SAME
     * config key as the Public path (QboPaymentNotificationService),
     * not a separate, still-empty one. If a real, genuine wording
     * difference is ever needed later, split back into its own key
     * then -- not preemptively now.
     */
    @Value("${app.brevo.enrollment-notification-template-id}")
    private Integer privateEnrollmentNotificationTemplateId;

    /**
     * Michael, 2026-09-03 -- LECTURE_ONLY path. Same, shared config key
     * as QboPaymentNotificationService's own -- confirmed with Michael
     * this template genuinely applies regardless of session type,
     * since the lecture itself has no location/time content at all.
     */
    @Value("${app.brevo.lecture-notification-template-id}")
    private Integer lectureNotificationTemplateId;

    public PrivateEnrollmentNotificationService(SessionDayRepository sessionDayRepository, EnrollmentRepository enrollmentRepository) {
        this.sessionDayRepository = sessionDayRepository;
        this.enrollmentRepository = enrollmentRepository;
    }

    /**
     * Michael, 2026-09-02 -- called synchronously, once, right after a
     * single real Enrollment saves (EnrollmentController.create()) --
     * confirmed with Michael as "fire synchronously once an enrollment
     * is locked in," not deferred or batched.
     */
    public void notifyStudentForEnrollment(Enrollment enrollment) {
        Student student = enrollment.getStudent();
        Session session = enrollment.getSession();
        boolean isLecture = enrollment.getEnrollmentComponents() == EnrollmentComponents.LECTURE_ONLY;
        Integer templateId = isLecture ? lectureNotificationTemplateId : privateEnrollmentNotificationTemplateId;

        // Michael, 2026-09-03 -- found while building the retry
        // mechanism: this method is now callable twice for the same
        // Enrollment (once at creation, once via a manual retry) --
        // the exact same duplicate-send risk already found and fixed
        // for the Public path's own isNotifiable(). Guarded here too,
        // before a retry could ever re-send to an Enrollment that
        // already, genuinely succeeded.
        if (enrollment.getBrevoNotifiedAt() != null) {
            log.info("Enrollment {} already has a real, successful notification -- skipping.", enrollment.getId());
            return;
        }

        if (apiKey == null || apiKey.isBlank()) {
            log.warn("Brevo API key not configured (app.brevo.api-key / BREVO_API_KEY) -- skipping Private/Semi-Private enrollment notification for Student {}", student.getId());
            recordFailure(enrollment, "Brevo API key not configured.");
            return;
        }
        if (templateId == null) {
            log.warn("No enrollment notification template configured -- skipping notification for Student {}", student.getId());
            recordFailure(enrollment, "No notification template configured.");
            return;
        }
        if (student.getEmail() == null || student.getEmail().isBlank()) {
            log.warn("Student {} has no email on file -- cannot send enrollment notification.", student.getId());
            recordFailure(enrollment, "Student has no email on file.");
            return;
        }
        if (senderEmail == null || senderEmail.isBlank()) {
            log.warn("Brevo sender email not configured (app.brevo.sender-email) -- skipping notification for Student {}", student.getId());
            recordFailure(enrollment, "Brevo sender email not configured.");
            return;
        }

        // Michael, 2026-09-03 -- Lecture is deliberately simpler here
        // too -- no location/time/mapUrl at all, matching the same
        // reasoning as QboPaymentNotificationService's own
        // buildLectureParams(): the lecture itself is self-paced and
        // online, not tied to a physical Session date, so building the
        // Field-only params (SessionDay lookup, GPS/map logic) is
        // skipped entirely for a Lecture send.
        Map<String, Object> params = isLecture ? buildLectureParams(student) : buildFieldParams(student, session);

        Map<String, Object> body = new HashMap<>();
        body.put("sender", Map.of("name", senderName, "email", senderEmail));
        body.put("to", List.of(Map.of("email", student.getEmail(), "name", student.getName())));
        HttpHeaders headers = new HttpHeaders();
        headers.set("api-key", apiKey);
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.setAccept(List.of(MediaType.APPLICATION_JSON));

        try {
            body.put("templateId", templateId);
            body.put("params", params);
            restTemplate.postForEntity(baseUrl + "/smtp/email", new HttpEntity<>(body, headers), Map.class);
            log.info("Sent Private/Semi-Private enrollment notification to Student {} ({})", student.getId(), student.getEmail());
            enrollment.setBrevoNotifiedAt(java.time.OffsetDateTime.now());
            enrollment.setBrevoNotificationError(null);
            enrollmentRepository.save(enrollment);
        } catch (HttpClientErrorException e) {
            log.error("Private/Semi-Private enrollment notification failed for Student {}: HTTP {} -- {}",
                    student.getId(), e.getStatusCode(), e.getResponseBodyAsString());
            recordFailure(enrollment, "HTTP " + e.getStatusCode() + ": " + e.getResponseBodyAsString());
        } catch (Exception e) {
            log.error("Private/Semi-Private enrollment notification failed for Student {}", student.getId(), e);
            recordFailure(enrollment, e.getMessage());
        }
    }

    /**
     * Michael, 2026-09-03 -- centralizes persisting a real, honest
     * failure reason -- brevoNotifiedAt deliberately left untouched
     * (still null, or still whatever a genuinely earlier success set
     * it to) so an attempt that fails after a prior success is never
     * silently overwritten to look successful.
     */
    private void recordFailure(Enrollment enrollment, String reason) {
        enrollment.setBrevoNotificationError(reason);
        enrollmentRepository.save(enrollment);
    }

    private Map<String, Object> buildFieldParams(Student student, Session session) {
        Optional<SessionDay> firstDay = sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(session.getId());
        String sessionDate = firstDay
                .map(SessionDay::getSessionDate)
                .map(d -> d.format(DATE_FORMAT))
                .orElse("a date to be confirmed");
        String sessionStartTime = firstDay
                .map(SessionDay::getStartTime)
                .map(t -> t.format(TIME_FORMAT))
                .orElse("a time to be confirmed");

        // Michael, 2026-09-02 -- deliberately NO address fallback here,
        // unlike the Public path's own buildMapUrl(). Confirmed with
        // Michael directly: a Private/Semi-Private session can't be
        // published without real GPS coordinates already set, so this
        // case is genuinely never supposed to happen -- but given the
        // real, physical stakes Michael described (a massive
        // industrial site, entering through the front gate, traversing
        // the plant, ending up in a parking lot or field off to the
        // side), an honest "location pending" is worth the small,
        // extra defensiveness over ever silently offering a link that
        // could send someone to the wrong physical spot.
        String mapUrl = "";
        if (session.getGridLat() != null && session.getGridLng() != null) {
            mapUrl = "https://www.google.com/maps/search/?api=1&query="
                    + session.getGridLat() + "," + session.getGridLng();
        } else {
            log.warn("Session {} has no GPS coordinates set -- this should never happen for a published Private/Semi-Private session. Sending notification with no map link rather than an address-based one that could point to the wrong physical location.", session.getId());
        }

        Map<String, Object> params = new HashMap<>();
        params.put("studentName", student.getName());
        params.put("sessionLocationName", nonBlank(session.getLocationName()));
        params.put("sessionAddressStreet", nonBlank(session.getAddressStreet()));
        params.put("sessionAddressCity", nonBlank(session.getAddressCity()));
        params.put("sessionAddressState", nonBlank(session.getAddressState()));
        params.put("sessionDate", sessionDate);
        params.put("sessionStartTime", sessionStartTime);
        params.put("studentNumber", student.getStudentNumber());
        params.put("mapUrl", mapUrl);
        return params;
    }

    /**
     * Michael, 2026-09-03 -- Lecture template's own params, matching
     * QboPaymentNotificationService's own -- just studentName/
     * studentNumber, since the real access URL is static, built
     * directly into the template itself, not per-student.
     */
    private Map<String, Object> buildLectureParams(Student student) {
        Map<String, Object> params = new HashMap<>();
        params.put("studentName", student.getName());
        params.put("studentNumber", student.getStudentNumber());
        return params;
    }

    private String nonBlank(String s) {
        return s != null ? s : "";
    }
}
