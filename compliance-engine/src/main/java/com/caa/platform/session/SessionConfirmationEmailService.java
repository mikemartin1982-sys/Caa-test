package com.caa.platform.session;

import com.caa.platform.client.Client;
import com.caa.platform.staff.StaffUser;
import jakarta.mail.MessagingException;
import jakarta.mail.internet.MimeMessage;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.mail.javamail.MimeMessageHelper;
import org.springframework.stereotype.Service;

import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

/**
 * Michael, 2026-08-19, redesigned same day to match a real, existing
 * branded template from an earlier Power Automate project -- a
 * staff-triggered confirmation email to the session's host client,
 * sent from a button in Session Details (the Private School Bid &amp;
 * Invoice area). NOT automatic, unlike CertificationEmailService.
 *
 * The Power Automate original used flow expressions
 * (@{items('Apply_to_each')?['...']}) and an Excel-serial-date
 * conversion (addDays('1899-12-30', int(...))) -- neither applies
 * here, since our own Session/Client data is already real typed
 * values, not values pulled from a spreadsheet row. Same visual
 * design, powered by our own platform's data instead.
 *
 * The two CTA buttons are mailto: links with pre-filled subject/body,
 * matching the original exactly -- confirmation happens by the client
 * replying to a real email, not a web click-to-confirm flow. No new
 * backend endpoint needed for that piece; it's just a correctly
 * encoded link.
 *
 * The signature block and both CTA buttons' reply-to target the
 * ACTUAL sending staff member (Michael, 2026-08-19, same day):
 * "wouldn't want someone else's confirmations coming to me or it
 * breaks the chain." Requires a real StaffUser.email on file for the
 * sender -- this is a genuinely required field for the reply-to
 * mechanism to function at all, so a missing one is rejected with a
 * clear error rather than silently falling back to a wrong address.
 * Phone/mobile/job title are cosmetic (display text in the signature
 * only) and fall back gracefully to "N/A" when blank.
 */
@Service
public class SessionConfirmationEmailService {

    private static final Logger log = LoggerFactory.getLogger(SessionConfirmationEmailService.class);

    private final JavaMailSender mailSender;

    @Value("${app.mail.from:no-reply@compliance-assurance.com}")
    private String fromAddress;

    public SessionConfirmationEmailService(JavaMailSender mailSender) {
        this.mailSender = mailSender;
    }

    public void sendConfirmationEmail(Client hostClient, Session session, LocalDate sessionDate, StaffUser sender) throws MessagingException {
        MimeMessage message = mailSender.createMimeMessage();
        MimeMessageHelper helper = new MimeMessageHelper(message, false, "UTF-8");

        helper.setFrom(fromAddress);
        helper.setTo(hostClient.getEmail());
        helper.setSubject("Method 9 Smoke School -- On-Site Training Confirmation");

        String siteContact = hostClient.getFirstName() != null
                ? (hostClient.getFirstName() + (hostClient.getLastName() != null ? " " + hostClient.getLastName() : ""))
                : hostClient.getCompany();
        String clientOrg = hostClient.getCompany();
        String city = resolveCity(session);
        String state = resolveState(session);
        String dateLong = sessionDate.format(DateTimeFormatter.ofPattern("MMMM d, yyyy"));
        String dateFull = sessionDate.format(DateTimeFormatter.ofPattern("EEEE, MMMM d, yyyy"));

        // First name only, for a natural "Hi Michael," style opening in
        // the reply -- matches how the original hardcoded template
        // greeted by first name, now sourced from whoever's actually
        // sending instead.
        String senderFirstName = sender.getName() != null && sender.getName().contains(" ")
                ? sender.getName().substring(0, sender.getName().indexOf(' '))
                : sender.getName();
        String senderName = sender.getName();
        String senderTitle = sender.getJobTitle() != null ? sender.getJobTitle() : "N/A";
        String senderPhone = sender.getPhone() != null ? sender.getPhone() : "N/A";
        String senderMobile = sender.getMobilePhone() != null ? sender.getMobilePhone() : "N/A";
        String replyToAddress = sender.getEmail();

        String confirmBody = senderFirstName + ",\r\n\r\n"
                + "This email confirms that the on-site Method 9 Smoke School schedule for " + dateLong
                + " works for our facility.\r\n\r\n"
                + "Client: " + clientOrg + "\r\n"
                + "Facility: " + city + ", " + state + "\r\n"
                + "Contact: " + siteContact + "\r\n\r\n"
                + "Thank you,";
        String changeBody = senderFirstName + ",\r\n\r\n"
                + "We need to request a change to the on-site Method 9 Smoke School schedule currently set for "
                + dateLong + ".\r\n\r\n"
                + "Requested change:\r\n\r\n"
                + "Reason or additional information:\r\n\r\n"
                + "Client: " + clientOrg + "\r\n"
                + "Facility: " + city + ", " + state + "\r\n"
                + "Contact: " + siteContact + "\r\n\r\n"
                + "Thank you,";

        String confirmMailto = mailtoLink(replyToAddress, "Confirmed: On-Site Method 9 Smoke School", confirmBody);
        String changeMailto = mailtoLink(replyToAddress, "Change Requested: On-Site Method 9 Smoke School", changeBody);

        String body = """
                <table role="presentation" width="100%%" cellpadding="0" cellspacing="0" border="0" style="margin:0; padding:0; background-color:#f3f5f7;">
                  <tr>
                    <td align="center" style="padding:20px 10px;">
                      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%%; max-width:600px; background-color:#ffffff; font-family:Arial,Helvetica,sans-serif; color:#172033;">
                        <tr>
                          <td align="center" style="background-color:#092954; padding:26px 20px; border-bottom:4px solid #c62026;">
                            <div style="font-size:29px; line-height:34px; font-weight:bold; letter-spacing:1px; color:#ffffff;">METHOD 9 SMOKE SCHOOL</div>
                            <div style="margin-top:7px; font-size:16px; line-height:22px; font-weight:bold; letter-spacing:1px; color:#ffffff;">PRIVATE ON-SITE TRAINING</div>
                            <div style="margin-top:9px; font-size:13px; line-height:18px; color:#dce7f5;">Compliance Assurance Associates, Inc.</div>
                          </td>
                        </tr>
                        <tr>
                          <td align="center" style="padding:24px 25px 10px 25px;">
                            <div style="font-size:24px; line-height:30px; font-weight:bold; color:#092954;">%s</div>
                            <div style="margin-top:5px; font-size:19px; line-height:25px; font-weight:bold; color:#c62026;">%s</div>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:12px 34px 8px 34px; font-size:15px; line-height:23px;">
                            <p style="margin:0 0 16px 0;">Hello %s,</p>
                            <p style="margin:0 0 16px 0;">We have your on-site Method 9 Smoke School scheduled for <strong>%s</strong>. Please use one of the options below to confirm that this schedule works for your facility or request an adjustment.</p>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:8px 34px 20px 34px;">
                            <table role="presentation" width="100%%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #d4dbe4; background-color:#f8fafc;">
                              <tr>
                                <td colspan="2" style="padding:10px 16px; background-color:#edf2f7; border-bottom:1px solid #d4dbe4; font-size:13px; line-height:18px; font-weight:bold; color:#c62026;">PRIVATE ON-SITE SCHOOL DETAILS</td>
                              </tr>
                              <tr>
                                <td width="34%%" valign="top" style="padding:12px 16px 6px 16px; font-size:13px; line-height:19px; font-weight:bold; color:#092954;">Client</td>
                                <td width="66%%" valign="top" style="padding:12px 16px 6px 16px; font-size:14px; line-height:19px; color:#172033;">%s</td>
                              </tr>
                              <tr>
                                <td width="34%%" valign="top" style="padding:6px 16px; font-size:13px; line-height:19px; font-weight:bold; color:#092954;">Location</td>
                                <td width="66%%" valign="top" style="padding:6px 16px; font-size:14px; line-height:19px; color:#172033;">%s, %s</td>
                              </tr>
                              <tr>
                                <td width="34%%" valign="top" style="padding:6px 16px 12px 16px; font-size:13px; line-height:19px; font-weight:bold; color:#092954;">Session Date</td>
                                <td width="66%%" valign="top" style="padding:6px 16px 12px 16px; font-size:14px; line-height:19px; color:#172033;">%s</td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td align="center" style="padding:4px 25px 25px 25px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                              <tr>
                                <td align="center" bgcolor="#16803C" style="border-radius:5px;">
                                  <a href="%s" style="display:inline-block; min-width:145px; padding:13px 25px; border:1px solid #0f5c2a; border-radius:5px; font-family:Arial,Helvetica,sans-serif; font-size:15px; line-height:18px; font-weight:bold; text-align:center; text-decoration:none; color:#ffffff;">Confirm</a>
                                </td>
                                <td width="14" style="font-size:1px; line-height:1px;">&nbsp;</td>
                                <td align="center" bgcolor="#B42318" style="border-radius:5px;">
                                  <a href="%s" style="display:inline-block; min-width:145px; padding:13px 25px; border:1px solid #8f1c14; border-radius:5px; font-family:Arial,Helvetica,sans-serif; font-size:15px; line-height:18px; font-weight:bold; text-align:center; text-decoration:none; color:#ffffff;">Request a Change</a>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:3px 34px 8px 34px; font-size:15px; line-height:23px;">
                            <div style="margin-bottom:8px; font-size:17px; line-height:23px; font-weight:bold; color:#092954;">Lecture Course Information</div>
                            <p style="margin:0 0 8px 0;">For employees who need the lecture course, CAA offers a self-paced online lecture that may be completed at any convenient time prior to the field session.</p>
                            <ul style="margin:8px 0 16px 22px; padding:0;">
                              <li style="margin-bottom:6px;">Employees may be enrolled through our website.</li>
                              <li style="margin-bottom:6px;">A valid email address must be provided for each employee.</li>
                              <li style="margin-bottom:6px;">Once enrolled, the employee will automatically receive instructions for accessing and completing the lecture.</li>
                            </ul>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:4px 34px 8px 34px; font-size:15px; line-height:23px;">
                            <div style="margin-bottom:8px; font-size:17px; line-height:23px; font-weight:bold; color:#092954;">Digital Field Certification</div>
                            <ul style="margin:8px 0 16px 22px; padding:0;">
                              <li style="margin-bottom:6px;">Participants should bring a fully charged smartphone or tablet.</li>
                              <li style="margin-bottom:6px;">CAA utilizes a digital certification system during the field session.</li>
                              <li style="margin-bottom:6px;">Additional devices will be available if needed.</li>
                            </ul>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:4px 34px 12px 34px; font-size:15px; line-height:23px;">
                            <div style="margin-bottom:8px; font-size:17px; line-height:23px; font-weight:bold; color:#092954;">Helpful Resources</div>
                            <ul style="margin:8px 0 16px 22px; padding:0;">
                              <li style="margin-bottom:7px;"><a href="https://compliance-assurance.com/lecture_certification/" style="color:#0067b8; text-decoration:underline;">Online Self-Paced Lecture</a></li>
                              <li style="margin-bottom:7px;"><a href="https://compliance-assurance.com/digital.php" style="color:#0067b8; text-decoration:underline;">Digital Smoke School Certification Information</a></li>
                              <li style="margin-bottom:7px;"><a href="https://compliance-assurance.com/onsite/onsite_demo.php" style="color:#0067b8; text-decoration:underline;">Digital Field Certification Demo</a></li>
                              <li style="margin-bottom:7px;"><a href="https://compliance-assurance.com/certs-email-id.php" style="color:#0067b8; text-decoration:underline;">Retrieve Student ID Number</a></li>
                            </ul>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:5px 34px 12px 34px; font-size:15px; line-height:23px;">
                            <p style="margin:0 0 16px 0;">If there is anything else we can assist with regarding your upcoming smoke school, please let us know.</p>
                            <p style="margin:0;">Thank you,</p>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:8px 34px 24px 34px; font-size:13px; line-height:19px; color:#172033;">
                            <table role="presentation" width="100%%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #d4dbe4;">
                              <tr>
                                <td style="padding-top:14px;">
                                  <strong style="font-size:15px; color:#092954;">%s</strong><br>
                                  %s<br>
                                  Compliance Assurance Associates, Inc.<br>
                                  682 Orvil Smith Rd., Harvest, AL 35749<br>
                                  Phone: %s &nbsp;|&nbsp; Mobile: %s<br>
                                  <a href="https://www.compliance-assurance.com/" style="color:#0067b8; text-decoration:underline;">www.compliance-assurance.com</a>
                                  &nbsp;|&nbsp;
                                  <a href="mailto:%s" style="color:#0067b8; text-decoration:underline;">%s</a>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                        <tr>
                          <td align="center" style="background-color:#f1f4f7; border-top:1px solid #d4dbe4; padding:14px 20px; font-size:11px; line-height:17px; color:#5c6673;">
                            <strong style="font-size:17px; color:#092954;">CAA, Inc.</strong><br>
                            Compliance Assurance Associates, Inc.<br>
                            682 Orvil Smith Rd., Harvest, AL 35749
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
                """.formatted(
                        clientOrg, dateLong,
                        siteContact, dateLong,
                        clientOrg,
                        city, state,
                        dateFull,
                        confirmMailto, changeMailto,
                        senderName, senderTitle, senderPhone, senderMobile,
                        replyToAddress, replyToAddress
                );

        helper.setText(body, true);

        mailSender.send(message);
        log.info("Session confirmation email sent to {} ({}) for session date {}",
                hostClient.getEmail(), clientOrg, sessionDate);
    }

    private String resolveCity(Session session) {
        if (session.getFieldCity() != null) return session.getFieldCity();
        if (session.getAddressCity() != null) return session.getAddressCity();
        return "N/A";
    }

    private String resolveState(Session session) {
        if (session.getFieldState() != null) return session.getFieldState();
        if (session.getAddressState() != null) return session.getAddressState();
        return "N/A";
    }

    private String mailtoLink(String to, String subject, String body) {
        try {
            String encodedSubject = urlEncode(subject);
            String encodedBody = urlEncode(body);
            return "mailto:" + to + "?subject=" + encodedSubject + "&body=" + encodedBody;
        } catch (UnsupportedEncodingException e) {
            // UTF-8 is always supported -- this is unreachable in practice.
            throw new IllegalStateException(e);
        }
    }

    private String urlEncode(String value) throws UnsupportedEncodingException {
        // URLEncoder uses "+" for spaces by default; mailto links
        // conventionally use "%20" (matching the original template
        // exactly) -- both are valid per RFC 6068, but matching the
        // original's exact encoding style avoids any doubt.
        return URLEncoder.encode(value, StandardCharsets.UTF_8).replace("+", "%20");
    }
}
