package com.caa.platform.integration.qbo;
 
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.util.UriComponentsBuilder;
 
import java.time.LocalDate;
import java.util.List;
import java.util.Map;
 
/**
 * Michael, 2026-08-25 -- QBO integration, layer 4 (Class sync).
 * Reverse-engineered from real, live examples Michael pulled directly
 * from the actual QuickBooks account -- not guessed at:
 *
 *   All:VA:ALT260303, All:TX:MID260804, All:TX:HOU260818/260819
 *
 * Confirmed, in order, through real experiments (not assumption):
 *  - "All" is a genuine parent Class, state a genuine child of it,
 *    and the session-level entry a genuine grandchild -- QBO's colon
 *    syntax is its OWN read-only FullyQualifiedName display
 *    convention, derived from real ParentRef relationships; a flat
 *    Class name literally cannot produce this (Class names can't even
 *    contain a colon), so this is structural proof, not inference.
 *  - The trailing 6 digits are YYMMDD of the session's EARLIEST
 *    scheduled date AT THE MOMENT publish() is hit and saved -- not a
 *    running counter, not the publish date itself. Confirmed via a
 *    real, controlled test: Midlothian's original class ref (260804)
 *    stayed frozen at the ORIGINAL 8/4 date even after that session
 *    was later rescheduled to 8/3 -- generated once, permanently
 *    locked in, exactly like Client.qboReferenceId never re-syncing
 *    once set.
 *  - Two Big Spring, TX sessions on the same real day share the exact
 *    same class ref -- confirms match-before-create at the session
 *    level too, same principle as QboCustomerSyncService: nothing
 *    special-cased for a "collision," it's just the natural result of
 *    searching before creating.
 *
 * City code: first three letters of the city, uppercased -- confirmed
 * twice (ALT/Altavista, MID/Midlothian). A real collision risk exists
 * (two different cities sharing the same first three letters) but is
 * deliberately not specially handled -- match-before-create means a
 * collision would just merge two different cities' sessions under one
 * shared class, silently wrong rather than erroring, so this is
 * flagged as a known, accepted limitation, not solved here.
 */
@Service
public class QboClassSyncService {
 
    private static final Logger log = LoggerFactory.getLogger(QboClassSyncService.class);
    private static final String PARENT_CLASS_NAME = "All";
 
    private final QboApiClient apiClient;
    private final SessionRepository sessionRepository;
 
    public QboClassSyncService(QboApiClient apiClient, SessionRepository sessionRepository) {
        this.apiClient = apiClient;
        this.sessionRepository = sessionRepository;
    }
 
    /**
     * Michael, 2026-08-25 -- called from the real publish() action
     * (SessionController), not on session creation -- confirmed this
     * is the actual, real trigger point ("once a date is set, and
     * Publish is hit and saved, it locks in the QBO reference").
     * Idempotent, same as syncCustomer(): if qboClassRefId is already
     * set, no QBO API calls happen at all -- the permanent-lock-in
     * behavior itself is enforced simply by never re-running this once
     * a value exists, not by any special "frozen" flag.
     *
     * REQUIRES_NEW -- found live (see QboCustomerSyncService's own
     * comment for the full mechanism): SessionController.publish()
     * wraps this call in a try/catch specifically so a QBO failure
     * never blocks a session from actually publishing, but plain
     * @Transactional here still marked publish()'s ENTIRE shared
     * transaction rollback-only the moment this threw -- regardless of
     * being caught locally -- producing a 500 on publish() even though
     * the exception was "handled." REQUIRES_NEW is what actually makes
     * the non-blocking behavior real, not just intended.
     */
    @Transactional(propagation = org.springframework.transaction.annotation.Propagation.REQUIRES_NEW)
    public String syncClass(Session session, LocalDate earliestSessionDate, String cityName, String stateCode) {
        if (session.getQboClassRefId() != null && !session.getQboClassRefId().isBlank()) {
            return session.getQboClassRefId();
        }
        if (cityName == null || cityName.isBlank() || stateCode == null || stateCode.isBlank() || earliestSessionDate == null) {
            throw new IllegalStateException(
                    "Session " + session.getId() + " is missing a city, state, or date -- all three are required before a QBO class can be created.");
        }
 
        String parentId = findOrCreateClass(PARENT_CLASS_NAME, null);
        String stateId = findOrCreateClass(stateCode.toUpperCase(), parentId);
 
        String cityCode = cityName.length() >= 3 ? cityName.substring(0, 3).toUpperCase() : cityName.toUpperCase();
        String dateSuffix = String.format("%02d%02d%02d",
                earliestSessionDate.getYear() % 100, earliestSessionDate.getMonthValue(), earliestSessionDate.getDayOfMonth());
        String sessionClassName = cityCode + dateSuffix;
 
        String sessionClassId = findOrCreateClass(sessionClassName, stateId);
 
        session.setQboClassRefId(sessionClassId);
        sessionRepository.save(session);
        return sessionClassId;
    }
 
    /**
     * Michael, 2026-08-25 -- one level of the hierarchy: search first
     * (by Name, scoped to the given parent, or top-level if parentId
     * is null), create only if genuinely nothing matches. Same
     * find-before-create principle as QboCustomerSyncService, and the
     * exact mechanism that makes Big Spring's two same-day sessions
     * correctly share one class rather than each getting their own.
     */
    @SuppressWarnings("unchecked")
    private String findOrCreateClass(String name, String parentId) {
        String existingId = findExistingClass(name, parentId);
        if (existingId != null) {
            return existingId;
        }
 
        Map<String, Object> payload = new java.util.HashMap<>();
        payload.put("Name", name);
        if (parentId != null) {
            payload.put("SubClass", true);
            payload.put("ParentRef", Map.of("value", parentId));
        }
 
        ResponseEntity<Map> response = apiClient.post("class", payload);
        Map<String, Object> body = response.getBody();
        if (body == null) {
            throw new IllegalStateException("QuickBooks Class creation returned an empty response.");
        }
        Map<String, Object> qboClass = (Map<String, Object>) body.get("Class");
        if (qboClass == null) {
            throw new IllegalStateException("QuickBooks Class creation response did not include the new Class.");
        }
        log.info("Created new QBO Class '{}' (parentId={}, Id={})", name, parentId, qboClass.get("Id"));
        return (String) qboClass.get("Id");
    }
 
    /**
     * Michael, 2026-08-25 -- filters by Name only, NOT ParentRef,
     * confirmed necessary live: querying "SELECT * FROM Class WHERE
     * Name = '...' AND ParentRef = '...'" consistently failed with a
     * generic QueryProcessingError (code 4002, no detail message),
     * while the exact same query with ParentRef removed succeeded
     * reliably every time. Most likely explanation, per Intuit's own
     * documentation: ParentRef isn't necessarily a FILTERABLE field on
     * every entity just because it is on some (e.g. CustomerRef on
     * Invoice, which IS documented as filterable) -- QBO's error
     * response for querying a non-filterable field is this same vague,
     * generic failure rather than a clear message naming the field.
     * Rather than depend on undocumented, entity-specific filterable
     * behavior, the parent match is verified here in Java instead,
     * against whatever Name matches come back.
     */
    @SuppressWarnings("unchecked")
    private String findExistingClass(String name, String parentId) {
        String escapedName = name.replace("'", "''");
        String query = "SELECT * FROM Class WHERE Name = '" + escapedName + "'";
 
        String path = UriComponentsBuilder.fromPath("query")
                .queryParam("query", query)
                .build()
                .toUriString();
 
        ResponseEntity<Map> response = apiClient.get(path);
        Map<String, Object> body = response.getBody();
        if (body == null) {
            return null;
        }
        Map<String, Object> queryResponse = (Map<String, Object>) body.get("QueryResponse");
        if (queryResponse == null) {
            return null;
        }
        List<Map<String, Object>> classes = (List<Map<String, Object>>) queryResponse.get("Class");
        if (classes == null || classes.isEmpty()) {
            return null;
        }
 
        // Michael, 2026-08-25 -- the actual parent-matching logic,
        // done here instead of in the QBO query itself. A top-level
        // class (parentId == null) must have no ParentRef at all; a
        // child class must have a ParentRef whose value matches the
        // expected parentId exactly.
        for (Map<String, Object> qboClass : classes) {
            Map<String, Object> actualParentRef = (Map<String, Object>) qboClass.get("ParentRef");
            String actualParentId = actualParentRef != null ? (String) actualParentRef.get("value") : null;
            boolean matches = parentId == null ? actualParentId == null : parentId.equals(actualParentId);
            if (matches) {
                return (String) qboClass.get("Id");
            }
        }
        return null;
    }
}