package com.caa.platform.client;

import com.caa.platform.staff.StaffUser;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * Section 3c: staff-driven Inquiry -> Client conversion. No automatic
 * conversion path exists on purpose (see {@link Inquiry}'s Javadoc).
 */
@Service
public class InquiryConversionService {

    private final ClientRepository clientRepository;
    private final InquiryRepository inquiryRepository;
    private final ClientNameDerivationService nameDerivationService;
    private final BrevoSyncService brevoSyncService;

    public InquiryConversionService(ClientRepository clientRepository,
                                     InquiryRepository inquiryRepository,
                                     ClientNameDerivationService nameDerivationService,
                                     BrevoSyncService brevoSyncService) {
        this.clientRepository = clientRepository;
        this.inquiryRepository = inquiryRepository;
        this.nameDerivationService = nameDerivationService;
        this.brevoSyncService = brevoSyncService;
    }

    /**
     * Converts an Inquiry into a new Client record, performed deliberately
     * by a staff member (never automatic). Marks the Inquiry converted and
     * links it to the new Client, preserving the paper trail (Section 3c).
     */
    @Transactional
    public Client convert(Inquiry inquiry, StaffUser convertedBy) {
        Client client = new Client();
        client.setCompany(inquiry.getCompany());
        client.setFirstName(inquiry.getFirstName());
        client.setLastName(inquiry.getLastName());
        client.setEmail(inquiry.getEmail());
        client.setPhone(inquiry.getPhone());
        client.setAddress(inquiry.getCompanyAddress());
        client.setCity(inquiry.getCity());
        client.setState(inquiry.getState());
        client.setZip(inquiry.getZip());
        client.setLeadSource(inquiry.getLeadSource());
        client.setPrefNewsletter(inquiry.isPrefNewsletter());
        client.setPrefClassConfirms(inquiry.isPrefClassConfirms());
        client.setPrefCertReminders(inquiry.isPrefCertReminders());

        client.setPortalDisplayName(nameDerivationService.derivePortalDisplayName(client.getCompany()));
        client.setRecordName(nameDerivationService.deriveRecordName(
                client.getCompany(), client.getCity(), client.getState()));

        Client saved = clientRepository.save(client);

        // Section 2/3: one-directional sync, platform -> Brevo, on every
        // new Client. BrevoSyncService handles its own failure tolerance
        // internally -- a Brevo problem never blocks conversion.
        brevoSyncService.syncContact(saved);

        inquiry.setStatus(InquiryStatus.CONVERTED);
        inquiry.setConvertedClient(saved);
        inquiryRepository.save(inquiry);

        return saved;
    }
}
