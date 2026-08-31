package com.caa.platform.client;

import com.caa.platform.session.Session;
import com.caa.platform.session.SessionRepository;
import com.caa.platform.student.Student;
import com.caa.platform.student.StudentRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

/**
 * Backs /clients in api-contract/openapi.yaml.
 */
@RestController
@RequestMapping("/api/v1/clients")
public class ClientController {

    private final ClientRepository clientRepository;
    private final BrevoSyncService brevoSyncService;
    private final ClientNameDerivationService nameDerivationService;
    private final StudentRepository studentRepository;
    private final PasswordEncoder passwordEncoder;
    private final PurchaseOrderRepository purchaseOrderRepository;
    private final PurchaseOrderSessionRepository purchaseOrderSessionRepository;
    private final SessionRepository sessionRepository;
    private final com.caa.platform.enrollment.EnrollmentRepository enrollmentRepository;
    private final com.caa.platform.enrollment.RosterService rosterService;
    private final com.caa.platform.integration.qbo.QboCustomerSyncService qboCustomerSyncService;

    public ClientController(ClientRepository clientRepository, BrevoSyncService brevoSyncService,
                             ClientNameDerivationService nameDerivationService, StudentRepository studentRepository,
                             PasswordEncoder passwordEncoder, PurchaseOrderRepository purchaseOrderRepository,
                             PurchaseOrderSessionRepository purchaseOrderSessionRepository, SessionRepository sessionRepository,
                             com.caa.platform.enrollment.EnrollmentRepository enrollmentRepository,
                             com.caa.platform.enrollment.RosterService rosterService,
                             com.caa.platform.integration.qbo.QboCustomerSyncService qboCustomerSyncService) {
        this.clientRepository = clientRepository;
        this.brevoSyncService = brevoSyncService;
        this.nameDerivationService = nameDerivationService;
        this.studentRepository = studentRepository;
        this.passwordEncoder = passwordEncoder;
        this.purchaseOrderRepository = purchaseOrderRepository;
        this.purchaseOrderSessionRepository = purchaseOrderSessionRepository;
        this.sessionRepository = sessionRepository;
        this.enrollmentRepository = enrollmentRepository;
        this.rosterService = rosterService;
        this.qboCustomerSyncService = qboCustomerSyncService;
    }

    /**
     * GET /clients/{clientId}/current-enrollments -- Michael, 2026-08-24,
     * client-facing portal's own "Current Enrollments" page: every
     * enrollment for this client's own employees (via
     * Student.employerClient, same definition "Manage Employees"
     * already uses), across every session -- not scoped to one
     * session like the admin Roster page. Same underlying data shape
     * staff see on a session's own Roster, minus the billing/staff-
     * facing columns (payment status, company name -- this client
     * already knows who they are).
     */
    @GetMapping("/{clientId}/current-enrollments")
    @org.springframework.transaction.annotation.Transactional(readOnly = true)
    public ResponseEntity<List<com.caa.platform.enrollment.RosterService.ClientEnrollmentEntry>> currentEnrollments(@PathVariable Long clientId) {
        List<com.caa.platform.enrollment.Enrollment> enrollments = enrollmentRepository.findByStudentEmployerClientId(clientId);
        return ResponseEntity.ok(rosterService.buildClientEnrollmentsView(enrollments));
    }

    /**
     * GET /clients?q= -- matches on Company, Record Name, or Portal
     * Display Name. Scaffold-scale in-memory filter; swap for a real
     * query (e.g. a Postgres full-text or ILIKE search) before production.
     */
    /**
     * Michael, 2026-08-23 -- added email to the match criteria. Backs
     * both the Client Page's own search and the "Set Host
     * Client"/"Clients to be Notified" flyouts elsewhere in the app,
     * so this one change reaches all of them.
     */
    @GetMapping
    public ResponseEntity<List<Client>> search(@RequestParam(required = false) String q) {
        if (q == null || q.isBlank()) {
            return ResponseEntity.ok(clientRepository.findAll());
        }

        // Michael, 2026-08-23 -- a purely numeric query means the
        // person is looking for a client by ID, not searching by name.
        // Previously matched text fields too, so typing "2" surfaced
        // any client whose email happened to contain that digit
        // anywhere (e.g. "...1982@gmail.com") alongside the actual
        // client #2 -- confusing and not what was actually being
        // searched for. Numeric input now matches ID only.
        if (q.chars().allMatch(Character::isDigit)) {
            String idQuery = q;
            List<Client> idResults = clientRepository.findAll().stream()
                    .filter(c -> c.getId() != null && c.getId().toString().startsWith(idQuery))
                    .toList();
            return ResponseEntity.ok(idResults);
        }

        String needle = q.toLowerCase();
        List<Client> results = clientRepository.findAll().stream()
                .filter(c -> containsIgnoreCase(c.getCompany(), needle)
                        || containsIgnoreCase(c.getRecordName(), needle)
                        || containsIgnoreCase(c.getPortalDisplayName(), needle)
                        || containsIgnoreCase(c.getEmail(), needle))
                .toList();
        return ResponseEntity.ok(results);
    }

    /** GET /clients/{id} -- fetch a single Client, e.g. for session re-hydration after a client login (Michael, 2026-08-19). */
    @GetMapping("/{clientId}")
    public ResponseEntity<Client> get(@PathVariable Long clientId) {
        return clientRepository.findById(clientId)
                .map(ResponseEntity::ok)
                .orElse(ResponseEntity.notFound().build());
    }

    /**
     * Michael, 2026-08-22 -- found live during review: there was no way
     * to edit an existing Client's fields at all before this. Backs the
     * Client Page rebuild. Partial update -- only fields actually sent
     * are touched, matching the same pattern already established on
     * Session's own update(). recordName/portalDisplayName are
     * deliberately NOT settable here -- they stay auto-derived (see
     * ClientNameDerivationService), same reasoning as register() above
     * never setting them directly either.
     */
    public record UpdateClientRequest(String company, String firstName, String lastName,
                                       String address, String city, String state, String zip,
                                       String phone, String email, String leadSource,
                                       Boolean prefNewsletter, Boolean prefClassConfirms, Boolean prefCertReminders,
                                       Boolean vrClient, java.math.BigDecimal vrPricingOverrideRate, Boolean vrPricingNoCost,
                                       String billingContactName, String billingEmail, String billingPhone) {}

    @PatchMapping("/{clientId}")
    public ResponseEntity<?> update(@PathVariable Long clientId, @RequestBody UpdateClientRequest req) {
        Client client = clientRepository.findById(clientId)
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + clientId));

        if (req.company() != null) client.setCompany(req.company());
        if (req.firstName() != null) client.setFirstName(req.firstName());
        if (req.lastName() != null) client.setLastName(req.lastName());
        if (req.address() != null) client.setAddress(req.address());
        if (req.city() != null) client.setCity(req.city());
        if (req.state() != null) client.setState(req.state());
        if (req.zip() != null) client.setZip(req.zip());
        if (req.phone() != null) client.setPhone(req.phone());
        if (req.email() != null) client.setEmail(req.email());
        if (req.leadSource() != null) client.setLeadSource(req.leadSource());
        if (req.prefNewsletter() != null) client.setPrefNewsletter(req.prefNewsletter());
        if (req.prefClassConfirms() != null) client.setPrefClassConfirms(req.prefClassConfirms());
        if (req.prefCertReminders() != null) client.setPrefCertReminders(req.prefCertReminders());
        if (req.vrClient() != null) client.setVrClient(req.vrClient());
        if (req.vrPricingOverrideRate() != null) client.setVrPricingOverrideRate(req.vrPricingOverrideRate());
        if (req.vrPricingNoCost() != null) client.setVrPricingNoCost(req.vrPricingNoCost());
        if (req.billingContactName() != null) client.setBillingContactName(req.billingContactName());
        if (req.billingEmail() != null) client.setBillingEmail(req.billingEmail());
        if (req.billingPhone() != null) client.setBillingPhone(req.billingPhone());

        // Company/city/state changing means the auto-derived display
        // names are now stale -- recompute them the same way register()
        // does, rather than leaving yesterday's name on screen.
        client.setPortalDisplayName(nameDerivationService.derivePortalDisplayName(client.getCompany()));
        client.setRecordName(nameDerivationService.deriveRecordName(client.getCompany(), client.getCity(), client.getState()));

        return ResponseEntity.ok(clientRepository.save(client));
    }

    /**
     * Manual re-sync -- useful for testing the Brevo integration directly,
     * or re-pushing a Client after fixing bad data, without needing to
     * go through the Inquiry conversion flow again.
     */
    @PostMapping("/{clientId}/sync-brevo")
    public ResponseEntity<?> syncBrevo(@PathVariable Long clientId) {
        Client client = clientRepository.findById(clientId)
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + clientId));
        brevoSyncService.syncContact(client);
        return ResponseEntity.ok(Map.of("synced", true));
    }

    /**
     * Michael, 2026-08-25 -- QBO integration, layer 4. Manual
     * on-demand sync -- matches syncBrevo() above's own shape and
     * reasoning. Idempotent (see QboCustomerSyncService's own
     * docblock): if this client already has a qboReferenceId, no QBO
     * API call happens at all -- safe to call repeatedly, including as
     * an automatic prerequisite step from the future invoice
     * generation flow, without worrying about duplicate Customer
     * creation on a re-run.
     */
    @PostMapping("/{clientId}/sync-qbo")
    public ResponseEntity<?> syncQbo(@PathVariable Long clientId) {
        Client client = clientRepository.findById(clientId)
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + clientId));
        String qboCustomerId = qboCustomerSyncService.syncCustomer(client);
        return ResponseEntity.ok(Map.of("synced", true, "qboReferenceId", qboCustomerId));
    }

    public record RegisterClientRequest(ClientType clientType, String firstName, String lastName,
                                         String company, String email, String phone, String password,
                                         boolean vrPreferred) {}

    /**
     * Public, self-serve account creation (Michael, 2026-08-19) --
     * general prospective-client registration, independent of which
     * testing path (VR or traditional staff-scheduled smoke school)
     * they end up on; vrPreferred just sets the existing vrClient flag
     * (Section 4b) to their choice at signup, rather than adding a
     * redundant new column for the same concept.
     *
     * Individual accounts are simultaneously the account holder AND the
     * test-taker -- a linked Student record is created immediately so
     * they can start their lecture right away, matching the planning
     * doc's Individual flow. Organizations add employees separately,
     * later, through the org portal (not yet built) -- no Student is
     * created here for an Organization registration.
     */
    @PostMapping("/register")
    public ResponseEntity<?> register(@RequestBody RegisterClientRequest req) {
        if (req.email() == null || req.email().isBlank()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "email is required"));
        }
        if (req.password() == null || req.password().isBlank()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "password is required"));
        }
        if (req.clientType() == null) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "clientType is required"));
        }
        if (req.clientType() == ClientType.ORGANIZATION && (req.company() == null || req.company().isBlank())) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "company is required for an Organization account"));
        }
        if (clientRepository.findByEmail(req.email()).isPresent()) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", "An account with this email already exists"));
        }

        Client client = new Client();
        client.setClientType(req.clientType());
        client.setFirstName(req.firstName());
        client.setLastName(req.lastName());
        client.setCompany(req.clientType() == ClientType.ORGANIZATION
                ? req.company()
                : (req.firstName() + " " + req.lastName()));
        client.setEmail(req.email());
        client.setPhone(req.phone());
        client.setPasswordHash(passwordEncoder.encode(req.password()));
        client.setVrClient(req.vrPreferred());
        client.setPortalDisplayName(nameDerivationService.derivePortalDisplayName(client.getCompany()));
        client.setRecordName(nameDerivationService.deriveRecordName(client.getCompany(), client.getCity(), client.getState()));

        Client saved = clientRepository.save(client);

        if (req.clientType() == ClientType.INDIVIDUAL) {
            Student student = new Student();
            student.setEmployerClient(saved);
            student.setName(req.firstName() + " " + req.lastName());
            student.setPhone(req.phone());
            student.setEmail(req.email());
            student.setStudentNumber(generateStudentNumber());
            studentRepository.save(student);
        }

        return ResponseEntity.status(HttpStatus.CREATED).body(saved);
    }

    // -----------------------------------------------------------------
    // Purchase Orders (Michael, 2026-08-22) -- clients often run one PO
    // across a full year or multiple seasons, not per-session.
    // -----------------------------------------------------------------

    /** GET /clients/{clientId}/purchase-orders -- most recent first. */
    @GetMapping("/{clientId}/purchase-orders")
    public ResponseEntity<List<PurchaseOrder>> listPurchaseOrders(@PathVariable Long clientId) {
        return ResponseEntity.ok(purchaseOrderRepository.findByClientIdOrderByCreatedAtDesc(clientId));
    }

    public record CreatePurchaseOrderRequest(String poNumber, Boolean active, java.time.LocalDate expirationDate,
                                               String shortDescription, String contactFirstName, String contactLastName,
                                               String contactEmail, java.math.BigDecimal startingAmount,
                                               java.math.BigDecimal amountUsed, java.math.BigDecimal thresholdAmount) {}

    @PostMapping("/{clientId}/purchase-orders")
    public ResponseEntity<?> createPurchaseOrder(@PathVariable Long clientId, @RequestBody CreatePurchaseOrderRequest req) {
        Client client = clientRepository.findById(clientId)
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + clientId));

        PurchaseOrder po = new PurchaseOrder();
        po.setClient(client);
        po.setPoNumber(req.poNumber());
        po.setActive(req.active() != null ? req.active() : false);
        po.setExpirationDate(req.expirationDate());
        po.setShortDescription(req.shortDescription());
        po.setContactFirstName(req.contactFirstName());
        po.setContactLastName(req.contactLastName());
        po.setContactEmail(req.contactEmail());
        po.setStartingAmount(req.startingAmount() != null ? req.startingAmount() : java.math.BigDecimal.ZERO);
        po.setAmountUsed(req.amountUsed() != null ? req.amountUsed() : java.math.BigDecimal.ZERO);
        po.setThresholdAmount(req.thresholdAmount() != null ? req.thresholdAmount() : java.math.BigDecimal.ZERO);

        return ResponseEntity.status(HttpStatus.CREATED).body(purchaseOrderRepository.save(po));
    }

    public record UpdatePurchaseOrderRequest(String poNumber, Boolean active, java.time.LocalDate expirationDate,
                                               String shortDescription, String contactFirstName, String contactLastName,
                                               String contactEmail, java.math.BigDecimal startingAmount,
                                               java.math.BigDecimal amountUsed, java.math.BigDecimal thresholdAmount) {}

    /** Partial update -- only fields actually sent are touched, same pattern as Client/Session's own update(). */
    @PatchMapping("/{clientId}/purchase-orders/{poId}")
    public ResponseEntity<?> updatePurchaseOrder(@PathVariable Long clientId, @PathVariable Long poId, @RequestBody UpdatePurchaseOrderRequest req) {
        PurchaseOrder po = purchaseOrderRepository.findById(poId)
                .orElseThrow(() -> new IllegalArgumentException("Purchase order not found: " + poId));

        if (req.poNumber() != null) po.setPoNumber(req.poNumber());
        if (req.active() != null) po.setActive(req.active());
        if (req.expirationDate() != null) po.setExpirationDate(req.expirationDate());
        if (req.shortDescription() != null) po.setShortDescription(req.shortDescription());
        if (req.contactFirstName() != null) po.setContactFirstName(req.contactFirstName());
        if (req.contactLastName() != null) po.setContactLastName(req.contactLastName());
        if (req.contactEmail() != null) po.setContactEmail(req.contactEmail());
        if (req.startingAmount() != null) po.setStartingAmount(req.startingAmount());
        if (req.amountUsed() != null) po.setAmountUsed(req.amountUsed());
        if (req.thresholdAmount() != null) po.setThresholdAmount(req.thresholdAmount());

        return ResponseEntity.ok(purchaseOrderRepository.save(po));
    }

    /**
     * GET /clients/{clientId}/purchase-orders/{poId}/sessions -- which
     * sessions this PO covers. Replaces DIBs' comma-separated
     * PO_assoc_sessions text field with a real join table (see
     * PurchaseOrderSession). session is initialized explicitly before
     * serialization -- it's a lazy relationship, same class of bug
     * (LazyInitializationException) hit and fixed several times
     * elsewhere in this project when this step was skipped.
     */
    @GetMapping("/{clientId}/purchase-orders/{poId}/sessions")
    @Transactional(readOnly = true)
    public ResponseEntity<List<PurchaseOrderSession>> listPurchaseOrderSessions(@PathVariable Long clientId, @PathVariable Long poId) {
        List<PurchaseOrderSession> entries = purchaseOrderSessionRepository.findByPurchaseOrderId(poId);
        entries.forEach(e -> org.hibernate.Hibernate.initialize(e.getSession()));
        return ResponseEntity.ok(entries);
    }

    /** POST /clients/{clientId}/purchase-orders/{poId}/sessions/{sessionId} -- associate a session with this PO. */
    @PostMapping("/{clientId}/purchase-orders/{poId}/sessions/{sessionId}")
    public ResponseEntity<?> addPurchaseOrderSession(@PathVariable Long clientId, @PathVariable Long poId, @PathVariable Long sessionId) {
        if (purchaseOrderSessionRepository.existsByPurchaseOrderIdAndSessionId(poId, sessionId)) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", "This session is already associated with this PO."));
        }
        PurchaseOrder po = purchaseOrderRepository.findById(poId)
                .orElseThrow(() -> new IllegalArgumentException("Purchase order not found: " + poId));
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        PurchaseOrderSession entry = new PurchaseOrderSession();
        entry.setPurchaseOrder(po);
        entry.setSession(session);
        return ResponseEntity.status(HttpStatus.CREATED).body(purchaseOrderSessionRepository.save(entry));
    }

    /** DELETE /clients/{clientId}/purchase-orders/{poId}/sessions/{sessionId} -- remove the association (does not touch the Session itself). */
    @DeleteMapping("/{clientId}/purchase-orders/{poId}/sessions/{sessionId}")
    @Transactional
    public ResponseEntity<?> removePurchaseOrderSession(@PathVariable Long clientId, @PathVariable Long poId, @PathVariable Long sessionId) {
        List<PurchaseOrderSession> entries = purchaseOrderSessionRepository.findByPurchaseOrderId(poId);
        PurchaseOrderSession match = entries.stream()
                .filter(e -> e.getSession().getId().equals(sessionId))
                .findFirst()
                .orElse(null);
        if (match == null) {
            return ResponseEntity.notFound().build();
        }
        purchaseOrderSessionRepository.delete(match);
        return ResponseEntity.noContent().build();
    }

    // Placeholder scheme, matching StudentController's own -- real
    // numbering convention TBD alongside the other deferred numbering
    // decisions (Section 9, and the certificate-number gap flagged
    // 2026-08-17).
    private String generateStudentNumber() {
        return "S" + System.currentTimeMillis();
    }

    private boolean containsIgnoreCase(String haystack, String needleLower) {
        return haystack != null && haystack.toLowerCase().contains(needleLower);
    }
}
