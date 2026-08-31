package com.caa.platform.client;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.web.client.HttpClientErrorException;
import org.springframework.web.client.RestTemplate;

import java.util.HashMap;
import java.util.Map;

/**
 * Section 2/3: "Brevo: email/campaigns, one-directional sync (platform ->
 * Brevo only)." This is the entire integration -- the platform pushes
 * Client contact data to Brevo; nothing ever flows back the other way.
 *
 * Uses Brevo's "updateEnabled" flag on the create-contact call, which
 * lets a single POST handle both create AND update -- if the email
 * already exists in Brevo, it updates that contact instead of erroring,
 * avoiding the need for separate create-vs-update branching logic.
 *
 * Same failure-tolerance pattern as PDF generation and email dispatch: a
 * Brevo API problem should never block a Client from being created or
 * updated in our own system -- log it and move on.
 *
 * ATTRIBUTE NAMES (FIRSTNAME, LASTNAME, SMS, COMPANY) assume standard/
 * common Brevo contact attributes. COMPANY specifically is often a
 * CUSTOM attribute that has to already exist in the Brevo account's
 * contact attribute settings before the API can set it -- if it's not
 * there, Brevo may silently drop it or return an error depending on
 * account configuration. Verify against your actual Brevo attribute
 * list and adjust the keys below if they don't match.
 */
@Service
public class BrevoSyncService {

    private static final Logger log = LoggerFactory.getLogger(BrevoSyncService.class);

    private final ClientRepository clientRepository;
    private final RestTemplate restTemplate = new RestTemplate();

    @Value("${app.brevo.api-key:}")
    private String apiKey;

    @Value("${app.brevo.base-url:https://api.brevo.com/v3}")
    private String baseUrl;

    public BrevoSyncService(ClientRepository clientRepository) {
        this.clientRepository = clientRepository;
    }

    public void syncContact(Client client) {
        if (apiKey == null || apiKey.isBlank()) {
            log.warn("Brevo API key not configured (app.brevo.api-key / BREVO_API_KEY) -- skipping sync for Client {}", client.getId());
            return;
        }
        if (client.getEmail() == null || client.getEmail().isBlank()) {
            log.warn("Client {} has no email address -- cannot sync to Brevo, which requires one.", client.getId());
            return;
        }

        Map<String, Object> attributes = new HashMap<>();
        if (client.getFirstName() != null) attributes.put("FIRSTNAME", client.getFirstName());
        if (client.getLastName() != null) attributes.put("LASTNAME", client.getLastName());
        if (client.getPhone() != null) attributes.put("SMS", client.getPhone());
        if (client.getCompany() != null) attributes.put("COMPANY", client.getCompany());

        Map<String, Object> body = new HashMap<>();
        body.put("email", client.getEmail());
        body.put("attributes", attributes);
        body.put("updateEnabled", true);

        HttpHeaders headers = new HttpHeaders();
        headers.set("api-key", apiKey);
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.setAccept(java.util.List.of(MediaType.APPLICATION_JSON));

        HttpEntity<Map<String, Object>> request = new HttpEntity<>(body, headers);

        try {
            @SuppressWarnings("unchecked")
            ResponseEntity<Map<String, Object>> response = (ResponseEntity<Map<String, Object>>) (ResponseEntity<?>)
                    restTemplate.postForEntity(baseUrl + "/contacts", request, Map.class);

            log.info("Synced Client {} to Brevo (status {})", client.getId(), response.getStatusCode());

            // On a fresh create, Brevo returns the new numeric contact ID
            // in the body. On an update-via-updateEnabled, it typically
            // returns 204 with no body -- in that case, brevoContactId
            // stays whatever it already was (correct, since the contact
            // already existed).
            if (response.getBody() != null && response.getBody().get("id") != null) {
                client.setBrevoContactId(String.valueOf(response.getBody().get("id")));
                clientRepository.save(client);
            }
        } catch (HttpClientErrorException e) {
            log.error("Brevo sync failed for Client {}: HTTP {} -- {}",
                    client.getId(), e.getStatusCode(), e.getResponseBodyAsString());
        } catch (Exception e) {
            log.error("Brevo sync failed for Client {}", client.getId(), e);
        }
    }
}
