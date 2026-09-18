package com.caa.platform.integration.qbo;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.LinkedMultiValueMap;
import org.springframework.util.MultiValueMap;
import org.springframework.web.client.HttpClientErrorException;
import org.springframework.web.client.RestTemplate;

import java.time.OffsetDateTime;
import java.util.Base64;
import java.util.Map;

/**
 * Michael, 2026-08-25 -- QBO integration, layer 4 (Customer/Class/
 * Invoice sync). Every call any future sync service makes to
 * QuickBooks' own REST API goes through here -- centralizes the one
 * thing that would otherwise need repeating in each of them: getting
 * the current connection, refreshing an expired access token
 * automatically first if needed, and building the right base URL
 * (sandbox vs production).
 *
 * Confirmed against Intuit's own docs: access tokens are valid for 1
 * hour. A sync attempt made any later than that would silently fail
 * with a 401 if this refresh step weren't handled transparently here.
 */
@Service
public class QboApiClient {

    private static final Logger log = LoggerFactory.getLogger(QboApiClient.class);

    private final QboConnectionRepository connectionRepository;
    private final RestTemplate restTemplate = new RestTemplate();

    @Value("${app.qbo.client-id:}")
    private String clientId;

    @Value("${app.qbo.client-secret:}")
    private String clientSecret;

    public QboApiClient(QboConnectionRepository connectionRepository) {
        this.connectionRepository = connectionRepository;
    }

    /**
     * Michael, 2026-08-25 -- returns the current, guaranteed-valid
     * connection -- refreshing the access token first if it's expired,
     * so every caller downstream of this never has to think about
     * expiry themselves. Throws a clear, specific exception if there's
     * no active connection at all, rather than a caller hitting a
     * confusing null-pointer or 401 further down the line.
     */
    @Transactional
    public QboConnection getValidConnection() {
        QboConnection connection = connectionRepository.findByActiveTrue()
                .orElseThrow(() -> new IllegalStateException("No active QuickBooks connection -- connect via /admin/qbo first."));

        if (connection.isAccessTokenExpired()) {
            refresh(connection);
        }
        return connection;
    }

    /**
     * Michael, 2026-08-25 -- Intuit ROTATES the refresh token on every
     * use (the response includes a new one, not the same one back) --
     * the old refresh_token becomes invalid the moment this succeeds,
     * so the new one must be stored, not just the new access token.
     */
    private void refresh(QboConnection connection) {
        log.info("QBO access token expired, refreshing (realmId={})", connection.getRealmId());

        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_FORM_URLENCODED);
        headers.setAccept(java.util.List.of(MediaType.APPLICATION_JSON));
        headers.set(HttpHeaders.AUTHORIZATION, "Basic " + Base64.getEncoder()
                .encodeToString((clientId + ":" + clientSecret).getBytes()));

        MultiValueMap<String, String> body = new LinkedMultiValueMap<>();
        body.add("grant_type", "refresh_token");
        body.add("refresh_token", connection.getRefreshToken());

        ResponseEntity<Map> response;
        try {
            response = restTemplate.postForEntity(
                    "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer",
                    new HttpEntity<>(body, headers),
                    Map.class
            );
        } catch (HttpClientErrorException e) {
            // Michael, 2026-08-25 -- a refresh token itself expires
            // (Intuit's own docs: ~100 days, though see the caution
            // already flagged during the OAuth design phase -- this
            // has reportedly changed and should be re-verified against
            // Intuit's current docs, not assumed). If refresh itself
            // fails, the connection is genuinely dead and needs a real
            // person to reconnect through /admin/qbo -- not something
            // this method can recover from on its own.
            throw new IllegalStateException(
                    "QuickBooks refresh token was rejected -- the connection likely needs to be re-authorized via /admin/qbo.", e);
        }

        Map<String, Object> tokens = response.getBody();
        if (tokens == null) {
            throw new IllegalStateException("QuickBooks token refresh returned an empty response.");
        }

        connection.setAccessToken((String) tokens.get("access_token"));
        connection.setRefreshToken((String) tokens.get("refresh_token"));
        connection.setAccessTokenExpiresAt(OffsetDateTime.now().plusSeconds(((Number) tokens.get("expires_in")).longValue()));
        connection.setRefreshTokenExpiresAt(OffsetDateTime.now().plusSeconds(((Number) tokens.get("x_refresh_token_expires_in")).longValue()));
        connectionRepository.save(connection);
    }

    private String baseUrl(QboConnection connection) {
        return "PRODUCTION".equalsIgnoreCase(connection.getEnvironment())
                ? "https://quickbooks.api.intuit.com"
                : "https://sandbox-quickbooks.api.intuit.com";
    }

    /** GET against /v3/company/{realmId}/{path} -- path should NOT include a leading slash. */
    public ResponseEntity<Map> get(String path) {
        QboConnection connection = getValidConnection();
        String url = baseUrl(connection) + "/v3/company/" + connection.getRealmId() + "/" + path;
        return restTemplate.exchange(url, HttpMethod.GET, new HttpEntity<>(authHeaders(connection)), Map.class);
    }

    /** POST against /v3/company/{realmId}/{path} -- path should NOT include a leading slash. */
    public ResponseEntity<Map> post(String path, Map<String, Object> body) {
        QboConnection connection = getValidConnection();
        String url = baseUrl(connection) + "/v3/company/" + connection.getRealmId() + "/" + path;
        return restTemplate.exchange(url, HttpMethod.POST, new HttpEntity<>(body, authHeaders(connection)), Map.class);
    }

    /**
     * Michael, 2026-09-04 -- a real, empty-body POST with
     * Content-Type: application/octet-stream, not application/json --
     * verified directly (web search, QBO's own API docs) as what QBO's
     * own "send" endpoints (invoice/send, estimate/send, etc.)
     * genuinely require, distinct from the standard, JSON-body post()
     * above used for creating/updating entities. Using the wrong
     * content type here risked a real rejection from QBO, not just a
     * cosmetic difference.
     */
    public ResponseEntity<Map> postEmpty(String path) {
        QboConnection connection = getValidConnection();
        String url = baseUrl(connection) + "/v3/company/" + connection.getRealmId() + "/" + path;
        HttpHeaders headers = authHeaders(connection);
        headers.setContentType(MediaType.APPLICATION_OCTET_STREAM);
        return restTemplate.exchange(url, HttpMethod.POST, new HttpEntity<>(headers), Map.class);
    }

    private HttpHeaders authHeaders(QboConnection connection) {
        HttpHeaders headers = new HttpHeaders();
        headers.setBearerAuth(connection.getAccessToken());
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.setAccept(java.util.List.of(MediaType.APPLICATION_JSON));
        return headers;
    }
}
