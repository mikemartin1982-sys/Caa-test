package com.caa.platform.client;

import com.caa.platform.staff.StaffUser;
import com.caa.platform.staff.StaffUserRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

/**
 * Backs /inquiries in api-contract/openapi.yaml. Section 3c: does NOT
 * create a Client on submission -- a deliberate security decision against
 * bot/bad-actor abuse of a public form. Conversion is a separate,
 * staff-driven step via InquiryConversionService.
 */
@RestController
@RequestMapping("/api/v1/inquiries")
public class InquiryController {

    private final InquiryRepository inquiryRepository;
    private final StaffUserRepository staffUserRepository;
    private final InquiryConversionService conversionService;

    public InquiryController(InquiryRepository inquiryRepository,
                              StaffUserRepository staffUserRepository,
                              InquiryConversionService conversionService) {
        this.inquiryRepository = inquiryRepository;
        this.staffUserRepository = staffUserRepository;
        this.conversionService = conversionService;
    }

    public record InquiryCreateRequest(String company, String firstName, String lastName, String email,
                                        String phone, String companyAddress, String city, String state,
                                        String zip, String leadSource, Boolean prefNewsletter,
                                        Boolean prefClassConfirms, Boolean prefCertReminders) {}

    @PostMapping
    public ResponseEntity<Inquiry> create(@RequestBody InquiryCreateRequest req) {
        Inquiry inquiry = new Inquiry();
        inquiry.setCompany(req.company());
        inquiry.setFirstName(req.firstName());
        inquiry.setLastName(req.lastName());
        inquiry.setEmail(req.email());
        inquiry.setPhone(req.phone());
        inquiry.setCompanyAddress(req.companyAddress());
        inquiry.setCity(req.city());
        inquiry.setState(req.state());
        inquiry.setZip(req.zip());
        inquiry.setLeadSource(req.leadSource());
        inquiry.setPrefNewsletter(Boolean.TRUE.equals(req.prefNewsletter()));
        inquiry.setPrefClassConfirms(Boolean.TRUE.equals(req.prefClassConfirms()));
        inquiry.setPrefCertReminders(Boolean.TRUE.equals(req.prefCertReminders()));

        return ResponseEntity.status(HttpStatus.CREATED).body(inquiryRepository.save(inquiry));
    }

    public record ConvertRequest(Long convertedByStaffId) {}

    /** Section 3c: staff-driven conversion of an Inquiry into a Client. */
    @PostMapping("/{inquiryId}/convert")
    public ResponseEntity<Client> convert(@PathVariable Long inquiryId, @RequestBody ConvertRequest req) {
        Inquiry inquiry = inquiryRepository.findById(inquiryId)
                .orElseThrow(() -> new IllegalArgumentException("Inquiry not found: " + inquiryId));
        StaffUser convertedBy = staffUserRepository.findById(req.convertedByStaffId())
                .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.convertedByStaffId()));

        Client client = conversionService.convert(inquiry, convertedBy);
        return ResponseEntity.status(HttpStatus.CREATED).body(client);
    }
}
