package com.caa.platform.integration.qbo;
 
import com.caa.platform.staff.StaffUser;
import com.caa.platform.staff.StaffUserRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.*;
 
import java.time.OffsetDateTime;
import java.util.Map;
 
/**
 * Michael, 2026-08-25 -- QBO integration, layer 2. The actual OAuth
 * redirect/consent flow happens on the Laravel side (all browser-
 * facing routes live there in this project) -- this controller is
 * called by Laravel AFTER it completes the token exchange with
 * Intuit, to store the result here, alongside the rest of the QBO
 * storage slots that already exist on this side (Client.qboReferenceId,
 * Session.qboClassRefId).
 */
@RestController
@RequestMapping("/api/v1/integrations/qbo")
public class QboConnectionController {
 
    private final QboConnectionRepository connectionRepository;
    private final StaffUserRepository staffUserRepository;
    private final QboApiClient apiClient;
 
    public QboConnectionController(QboConnectionRepository connectionRepository, StaffUserRepository staffUserRepository, QboApiClient apiClient) {
        this.connectionRepository = connectionRepository;
        this.staffUserRepository = staffUserRepository;
        this.apiClient = apiClient;
    }
 
    /**
     * Michael, 2026-08-25 -- TEMPORARY diagnostic endpoint, added
     * specifically to isolate a QueryProcessingError (code 4002) hit
     * while testing Class sync -- lets a raw QBO query be tested
     * directly through our own working connection/token, without
     * needing to touch the real Class hierarchy or guess blindly.
     * Should be removed once the underlying Class-sync issue is
     * actually resolved -- this is not meant to be permanent.
     */
    @GetMapping("/raw-query")
    public ResponseEntity<?> rawQuery(@RequestParam String q) {
        String path = org.springframework.web.util.UriComponentsBuilder.fromPath("query")
                .queryParam("query", q)
                .build()
                .toUriString();
        return ResponseEntity.ok(apiClient.get(path).getBody());
    }

    /**
     * Michael, 2026-09-01 -- TEMPORARY diagnostic endpoint, added
     * specifically to test the Client Auto-Notify payment-detection
     * chain while Intuit's own sandbox UI was down (a real,
     * officially-acknowledged outage that evening) -- calling
     * QBO's real Payment API directly through our own, already-
     * valid, encrypted access token, so a live token never has to be
     * copied out and handled by hand in PowerShell. Fetches the real
     * invoice first (its own real CustomerRef/Balance), rather than
     * requiring those be supplied and risking a mismatch. Should be
     * removed once no longer needed -- not meant to be permanent.
     */
    @SuppressWarnings("unchecked")
    @PostMapping("/test-mark-invoice-paid")
    public ResponseEntity<?> testMarkInvoicePaid(@RequestParam String invoiceId) {
        java.util.Map<String, Object> invoiceBody = apiClient.get("invoice/" + invoiceId).getBody();
        if (invoiceBody == null || invoiceBody.get("Invoice") == null) {
            return ResponseEntity.badRequest().body(Map.of("error", "Invoice " + invoiceId + " not found."));
        }
        Map<String, Object> invoice = (Map<String, Object>) invoiceBody.get("Invoice");
        Map<String, Object> customerRef = (Map<String, Object>) invoice.get("CustomerRef");
        Object balanceRaw = invoice.get("Balance");
        if (customerRef == null || balanceRaw == null) {
            return ResponseEntity.badRequest().body(Map.of("error", "Invoice " + invoiceId + " is missing CustomerRef or Balance."));
        }

        java.math.BigDecimal balance = new java.math.BigDecimal(String.valueOf(balanceRaw));
        if (balance.compareTo(java.math.BigDecimal.ZERO) == 0) {
            return ResponseEntity.ok(Map.of("message", "Invoice " + invoiceId + " already has a zero balance -- nothing to do."));
        }

        Map<String, Object> paymentPayload = new java.util.HashMap<>();
        paymentPayload.put("CustomerRef", customerRef);
        paymentPayload.put("TotalAmt", balance);
        paymentPayload.put("Line", java.util.List.of(Map.of(
                "Amount", balance,
                "LinkedTxn", java.util.List.of(Map.of("TxnId", invoiceId, "TxnType", "Invoice"))
        )));

        return ResponseEntity.ok(apiClient.post("payment", paymentPayload).getBody());
    }
 
    /**
     * Michael, 2026-08-25 -- deliberately never includes the actual
     * access/refresh token values, even though they're encrypted at
     * rest -- encrypted-at-rest protects the database, not whatever
     * gets serialized back out over this API. Any caller of this
     * endpoint would otherwise receive the real, decrypted token
     * (QboTokenEncryptionConverter decrypts transparently on read),
     * which defeats the point of encrypting it in the first place.
     */
    public record ConnectionStatus(boolean connected, String realmId, String environment,
                                    OffsetDateTime accessTokenExpiresAt, boolean accessTokenExpired,
                                    String connectedByStaffName, OffsetDateTime connectedAt) {}
 
    @GetMapping("/connection")
    @Transactional(readOnly = true)
    public ResponseEntity<ConnectionStatus> getConnectionStatus() {
        return connectionRepository.findByActiveTrue()
                .map(c -> ResponseEntity.ok(new ConnectionStatus(
                        true, c.getRealmId(), c.getEnvironment(),
                        c.getAccessTokenExpiresAt(), c.isAccessTokenExpired(),
                        c.getConnectedByStaff() != null ? c.getConnectedByStaff().getName() : null,
                        c.getCreatedAt()
                )))
                .orElse(ResponseEntity.ok(new ConnectionStatus(false, null, null, null, false, null, null)));
    }
 
    public record StoreConnectionRequest(String realmId, String accessToken, String refreshToken,
                                          Integer expiresInSeconds, Integer refreshTokenExpiresInSeconds,
                                          String environment, Long connectedByStaffId) {}
 
    /**
     * Michael, 2026-08-25 -- called once by Laravel right after a
     * successful token exchange with Intuit. Deactivates any prior
     * active connection first (rather than deleting it) -- past
     * connections stay visible as history, matching migration 032's
     * own reasoning; the DB's partial unique index only allows one
     * active=true row at a time regardless, so this insert would fail
     * outright if the deactivation step were skipped.
     */
    @PostMapping("/connection")
    @Transactional
    public ResponseEntity<?> storeConnection(@RequestBody StoreConnectionRequest req) {
        if (req.realmId() == null || req.accessToken() == null || req.refreshToken() == null
                || req.expiresInSeconds() == null || req.refreshTokenExpiresInSeconds() == null) {
            return ResponseEntity.unprocessableEntity()
                    .body(Map.of("error", "realmId, accessToken, refreshToken, expiresInSeconds, and refreshTokenExpiresInSeconds are all required."));
        }
 
        connectionRepository.findByActiveTrue().ifPresent(existing -> {
            existing.setActive(false);
            connectionRepository.save(existing);
        });
 
        StaffUser connectedBy = null;
        if (req.connectedByStaffId() != null) {
            connectedBy = staffUserRepository.findById(req.connectedByStaffId())
                    .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.connectedByStaffId()));
        }
 
        QboConnection connection = new QboConnection();
        connection.setRealmId(req.realmId());
        connection.setAccessToken(req.accessToken());
        connection.setRefreshToken(req.refreshToken());
        connection.setAccessTokenExpiresAt(OffsetDateTime.now().plusSeconds(req.expiresInSeconds()));
        connection.setRefreshTokenExpiresAt(OffsetDateTime.now().plusSeconds(req.refreshTokenExpiresInSeconds()));
        connection.setEnvironment(req.environment() != null ? req.environment() : "SANDBOX");
        connection.setConnectedByStaff(connectedBy);
        connection.setActive(true);
 
        connectionRepository.save(connection);
        return ResponseEntity.status(HttpStatus.CREATED).build();
    }
 
    /** Michael, 2026-08-25 -- lets staff explicitly disconnect, e.g. before reconnecting to a different QBO company. */
    @DeleteMapping("/connection")
    @Transactional
    public ResponseEntity<?> disconnect() {
        connectionRepository.findByActiveTrue().ifPresent(existing -> {
            existing.setActive(false);
            connectionRepository.save(existing);
        });
        return ResponseEntity.noContent().build();
    }
}