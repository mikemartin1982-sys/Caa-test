package com.caa.platform.integration.qbo;

import com.caa.platform.client.Client;
import com.caa.platform.client.ClientRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.util.UriComponentsBuilder;

import java.util.List;
import java.util.Map;

/**
 * Michael, 2026-08-25 -- QBO integration, layer 4. Confirmed with
 * Michael: never blindly create a new QuickBooks Customer -- search
 * for a real, existing match first, since an unmatched duplicate
 * "could cause billing issues in the backend." A Customer is only
 * ever created here as the genuine last resort, when no match exists.
 *
 * Matches against recordName (the same auto-derived, display-ready
 * name -- company + city/state -- already used everywhere else this
 * client is shown), not the raw company field, so what staff already
 * see as "this client's name" is exactly what gets searched for and
 * shown in QuickBooks too.
 */
@Service
public class QboCustomerSyncService {

    private static final Logger log = LoggerFactory.getLogger(QboCustomerSyncService.class);

    private final QboApiClient apiClient;
    private final ClientRepository clientRepository;

    public QboCustomerSyncService(QboApiClient apiClient, ClientRepository clientRepository) {
        this.apiClient = apiClient;
        this.clientRepository = clientRepository;
    }

    /**
     * Returns the QBO Customer Id for this client -- matching an
     * existing one if found, creating one only if genuinely none
     * exists. Idempotent: if qboReferenceId is already set, returns it
     * immediately without any QBO API call at all.
     *
     * Michael, 2026-08-25 -- REQUIRES_NEW, found live: when this was
     * plain @Transactional (default REQUIRED propagation), calling it
     * from inside another @Transactional method (e.g. publish() on
     * SessionController's sibling, QboClassSyncService) meant a
     * failure here marked THAT caller's entire transaction rollback-
     * only the instant it was thrown -- even though the caller caught
     * the exception locally and never re-threw it. Spring marks
     * rollback-only at the point of the throw, not the catch, so
     * "catch and continue" alone didn't actually protect the outer
     * transaction. REQUIRES_NEW gives this its own, fully independent
     * transaction that can fail and roll back on its own without
     * touching whatever transaction the caller is already in.
     */
    @Transactional(propagation = org.springframework.transaction.annotation.Propagation.REQUIRES_NEW)
    public String syncCustomer(Client client) {
        if (client.getQboReferenceId() != null && !client.getQboReferenceId().isBlank()) {
            return client.getQboReferenceId();
        }

        String displayName = client.getRecordName() != null ? client.getRecordName() : client.getCompany();
        if (displayName == null || displayName.isBlank()) {
            throw new IllegalStateException("Client " + client.getId() + " has no name to sync to QuickBooks.");
        }

        String existingId = findExistingCustomer(displayName);
        String qboId = existingId != null ? existingId : createCustomer(client, displayName);

        client.setQboReferenceId(qboId);
        clientRepository.save(client);
        return qboId;
    }

    /**
     * Michael, 2026-08-25 -- QuickBooks' own query language (a
     * SQL-like subset), confirmed against Intuit's own API docs:
     * SELECT * FROM Customer WHERE DisplayName = '...'. Single quotes
     * inside the name itself are escaped per QBO's own query syntax
     * (doubled, not backslash-escaped) -- a real, if uncommon, case
     * (an apostrophe in a company name) that would otherwise break the
     * query outright rather than just fail to match.
     */
    @SuppressWarnings("unchecked")
    private String findExistingCustomer(String displayName) {
        String escapedName = displayName.replace("'", "''");
        String query = "SELECT * FROM Customer WHERE DisplayName = '" + escapedName + "'";
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

        List<Map<String, Object>> customers = (List<Map<String, Object>>) queryResponse.get("Customer");
        if (customers == null || customers.isEmpty()) {
            return null;
        }

        if (customers.size() > 1) {
            // Michael, 2026-08-25 -- shouldn't happen (DisplayName is
            // unique in QBO by its own rules), but logged rather than
            // silently picking one if it somehow does -- worth a human
            // noticing, not worth failing the whole sync over.
            log.warn("QBO returned {} customers matching DisplayName '{}' -- using the first.", customers.size(), displayName);
        }

        return (String) customers.get(0).get("Id");
    }

    @SuppressWarnings("unchecked")
    private String createCustomer(Client client, String displayName) {
        java.util.Map<String, Object> payload = new java.util.HashMap<>();
        payload.put("DisplayName", displayName);
        if (client.getEmail() != null) {
            payload.put("PrimaryEmailAddr", Map.of("Address", client.getEmail()));
        }
        if (client.getPhone() != null) {
            payload.put("PrimaryPhone", Map.of("FreeFormNumber", client.getPhone()));
        }
        if (client.getAddress() != null || client.getCity() != null) {
            java.util.Map<String, Object> billAddr = new java.util.HashMap<>();
            if (client.getAddress() != null) billAddr.put("Line1", client.getAddress());
            if (client.getCity() != null) billAddr.put("City", client.getCity());
            if (client.getState() != null) billAddr.put("CountrySubDivisionCode", client.getState());
            if (client.getZip() != null) billAddr.put("PostalCode", client.getZip());
            payload.put("BillAddr", billAddr);
        }

        ResponseEntity<Map> response = apiClient.post("customer", payload);
        Map<String, Object> body = response.getBody();
        if (body == null) {
            throw new IllegalStateException("QuickBooks Customer creation returned an empty response.");
        }
        Map<String, Object> customer = (Map<String, Object>) body.get("Customer");
        if (customer == null) {
            throw new IllegalStateException("QuickBooks Customer creation response did not include the new Customer.");
        }
        log.info("Created new QBO Customer '{}' (Id={})", displayName, customer.get("Id"));
        return (String) customer.get("Id");
    }
}
