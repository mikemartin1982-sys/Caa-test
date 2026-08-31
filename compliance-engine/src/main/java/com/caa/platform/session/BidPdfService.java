package com.caa.platform.session;
 
import com.caa.platform.client.Client;
import com.caa.platform.staff.StaffUser;
import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.pdmodel.PDPage;
import org.apache.pdfbox.pdmodel.PDPageContentStream;
import org.apache.pdfbox.pdmodel.common.PDRectangle;
import org.apache.pdfbox.pdmodel.font.PDFont;
import org.apache.pdfbox.pdmodel.font.PDType1Font;
import org.apache.pdfbox.pdmodel.font.Standard14Fonts;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
 
import java.io.IOException;
import java.math.BigDecimal;
import java.math.RoundingMode;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.List;
 
/**
 * Michael, 2026-08-19 -- matches a real bid template (last_bid_sent.docx)
 * he provided, a 3-page letter sent to a Private-school host client:
 * page 1 (header/quote box/recipient/greeting/body/time-sensitive
 * notice/schedule), page 2 (cost table/competitive pricing/next
 * steps/closing/signature), page 3 (extra details). Same PDFBox
 * low-level drawing approach as CertificatePdfService, extended for a
 * multi-page letter layout instead of a single centered certificate.
 *
 * Confirmed with Michael:
 *   - Quote Number auto-generated as [StaffInitials]-[YYMMDD]-R[Revision]
 *     (e.g. "JDS-251202-R1"), using the SENDING staff member -- same
 *     per-sender pattern as the confirmation email, not whoever the
 *     session's Field Manager happens to be.
 *   - No per-staff signature image yet -- SAME GAP as
 *     CertificatePdfService (see its Javadoc, gap #2). Blank signature
 *     line, typed name beneath, exactly as that file already does it.
 *   - Output is a locked PDF, not an editable .docx.
 *
 * The "Number x $Rate ea = ?" additional-cost lines are printed as
 * literal blanks/"?" (matching the real template exactly) -- the
 * actual overage headcount isn't known at bid-generation time, only
 * once the session actually runs. This is NOT a bug or missing
 * calculation; it's the template's own intentional design.
 */
@Service
public class BidPdfService {
 
    @Value("${app.bids.output-dir:./bids}")
    private String outputDir;
 
    private final SessionPoResolutionService poResolutionService;
 
    public BidPdfService(SessionPoResolutionService poResolutionService) {
        this.poResolutionService = poResolutionService;
    }
 
    private static final float PAGE_WIDTH = PDRectangle.LETTER.getWidth();
    private static final float PAGE_HEIGHT = PDRectangle.LETTER.getHeight();
    private static final float MARGIN_LEFT = 54;
    private static final float MARGIN_RIGHT = 54;
    private static final float CONTENT_WIDTH = PAGE_WIDTH - MARGIN_LEFT - MARGIN_RIGHT;
 
    private static final DateTimeFormatter LONG_DATE = DateTimeFormatter.ofPattern("MMMM d, yyyy");
    private static final DateTimeFormatter SLASH_DATE = DateTimeFormatter.ofPattern("MM/dd/yyyy");
 
    public String generate(Session session, Client hostClient, SessionDay sessionDay, StaffUser sender) throws IOException {
        LocalDate sessionDate = sessionDay.getSessionDate();
        Path dir = Path.of(outputDir);
        Files.createDirectories(dir);
 
        // Quote Number + revision -- incremented by the caller
        // (SessionController) before this is called, so this reads
        // whatever the caller already set rather than incrementing
        // itself, keeping "what revision does this PDF represent" and
        // "generate the PDF" as two clearly separate responsibilities.
        String staffInitials = sender.getInitials() != null ? sender.getInitials() : sender.getUsername();
        String quoteNumber = staffInitials + "-" + LocalDate.now().format(DateTimeFormatter.ofPattern("yyMMdd"))
                + "-R" + session.getBidRevisionNumber();
 
        String timestamp = DateTimeFormatter.ofPattern("yyyyMMdd-HHmmss").format(java.time.LocalDateTime.now());
        String filename = "bid-" + session.getId() + "-" + timestamp + ".pdf";
        Path filePath = dir.resolve(filename);
 
        PDFont regular = new PDType1Font(Standard14Fonts.FontName.HELVETICA);
        PDFont bold = new PDType1Font(Standard14Fonts.FontName.HELVETICA_BOLD);
        PDFont italic = new PDType1Font(Standard14Fonts.FontName.HELVETICA_OBLIQUE);
        PDFont boldItalic = new PDType1Font(Standard14Fonts.FontName.HELVETICA_BOLD_OBLIQUE);
 
        String recipientName = resolveContactName(hostClient);
        String fieldLocation = resolveFieldLocation(session);
 
        try (PDDocument document = new PDDocument()) {
 
            // ================= PAGE 1 =================
            PDPage page1 = new PDPage(PDRectangle.LETTER);
            document.addPage(page1);
            try (PDPageContentStream c = new PDPageContentStream(document, page1)) {
                float y = PAGE_HEIGHT - 50;
 
                writeLine(c, MARGIN_LEFT, y, bold, 20, "Compliance Assurance Associates, Inc.");
                y -= 4;
                c.setLineWidth(1.5f);
                c.setStrokingColor(0f, 82f / 255f, 155f / 255f);
                line(c, MARGIN_LEFT, y, PAGE_WIDTH - MARGIN_RIGHT, y);
                c.setStrokingColor(0f, 0f, 0f);
                y -= 14;
                c.setNonStrokingColor(0f, 82f / 255f, 155f / 255f);
                writeLine(c, MARGIN_LEFT, y, boldItalic, 10, "Advancing VEO into the 21st Century");
                c.setNonStrokingColor(0f, 0f, 0f);
 
                // Quote box -- Michael, 2026-08-22, found live during
                // testing: sitting beside the title collided with it
                // (the title text is long enough to run straight into
                // a fixed-position right-aligned box regardless of
                // font size tweaks). Moved below the header block
                // entirely instead, so there's no collision risk no
                // matter how the title renders.
                y -= 16;
                float boxW = 190, boxH = 38;
                float boxX = PAGE_WIDTH - MARGIN_RIGHT - boxW;
                float boxY = y - boxH + 12;
                c.setLineWidth(0.75f);
                c.addRect(boxX, boxY, boxW, boxH);
                c.stroke();
                writeLine(c, boxX + 8, boxY + boxH - 14, regular, 10, "Quote Number: " + quoteNumber);
                writeLine(c, boxX + 8, boxY + boxH - 28, regular, 10, "Session ID: " + session.getId());
                y = boxY - 20;
 
                writeLine(c, MARGIN_LEFT, y, regular, 11, LocalDate.now().format(LONG_DATE));
 
                y -= 24;
                writeLine(c, MARGIN_LEFT, y, regular, 11, recipientName);
                if (hostClient.getCompany() != null) {
                    y -= 14;
                    writeLine(c, MARGIN_LEFT, y, regular, 11, hostClient.getCompany());
                }
                if (hostClient.getAddress() != null) {
                    y -= 14;
                    writeLine(c, MARGIN_LEFT, y, regular, 11, hostClient.getAddress());
                }
                String billingCityStateZip = joinCityStateZip(hostClient.getCity(), hostClient.getState(), hostClient.getZip());
                if (!billingCityStateZip.isBlank()) {
                    y -= 14;
                    writeLine(c, MARGIN_LEFT, y, regular, 11, billingCityStateZip);
                }
                // Field location line -- separate from the billing
                // address above, matching the real template's two
                // distinct city/state/zip lines (one billing, one
                // field-site).
                String fieldCityStateZip = joinCityStateZip(session.getFieldCity(), session.getFieldState(), session.getFieldZip());
                if (!fieldCityStateZip.isBlank()) {
                    y -= 14;
                    writeLine(c, MARGIN_LEFT, y, regular, 11, fieldCityStateZip);
                }
 
                y -= 20;
                if (hostClient.getPhone() != null) {
                    writeLine(c, MARGIN_LEFT, y, regular, 11, "Phone: " + hostClient.getPhone());
                    y -= 14;
                }
                if (hostClient.getEmail() != null) {
                    writeLine(c, MARGIN_LEFT, y, regular, 11, "Sent via Email: " + hostClient.getEmail());
                    y -= 14;
                }
                String senderEmail = sender.getEmail() != null ? sender.getEmail() : "";
                writeLine(c, MARGIN_LEFT, y, regular, 11, "cc: " + senderEmail + "; accounting@compliance-assurance.com");
 
                y -= 26;
                writeLine(c, MARGIN_LEFT, y, regular, 11, "Dear " + recipientName + ":");
 
                y -= 20;
                List<String> bodyLines = wrapText(
                        "Compliance Assurance Associates, Inc. (CAA) appreciates the opportunity to offer opacity "
                                + "training services to your organization via " + fieldLocation + " on "
                                + sessionDate.format(SLASH_DATE) + ". The certification training is per U.S. EPA 40 CFR 60 "
                                + "Appendix A Reference Method 9, and is subject to CAA's terms and conditions "
                                + "(see www.compliance-assurance.com/terms.php).",
                        regular, 11, CONTENT_WIDTH);
                for (String line : bodyLines) {
                    writeLine(c, MARGIN_LEFT, y, regular, 11, line);
                    y -= 14;
                }
 
                y -= 12;
                writeLine(c, MARGIN_LEFT, y, bold, 12, "Time-Sensitive Bid");
                y -= 16;
                LocalDate cutoff = sessionDate.minusDays(60);
                List<String> cutoffLines = wrapText(
                        "The dates offered below are good until " + cutoff.format(SLASH_DATE) + " (60 days prior to "
                                + "the session date). The base-cost shown in the cost section is valid up to the session "
                                + "date offered; there may be additional logistics charges after " + cutoff.format(SLASH_DATE) + ".",
                        regular, 10, CONTENT_WIDTH - 20);
                for (String line : cutoffLines) {
                    writeLine(c, MARGIN_LEFT + 20, y, regular, 10, line);
                    y -= 13;
                }
 
                y -= 12;
                writeLine(c, MARGIN_LEFT, y, bold, 12, "Schedule");
                y -= 18;
                float col1 = MARGIN_LEFT + 20, col2 = col1 + 100, col3 = col2 + 90;
                writeLine(c, col1, y, bold, 10, "Date");
                writeLine(c, col2, y, bold, 10, "Event");
                writeLine(c, col3, y, bold, 10, "Time/Schedule");
                y -= 16;
                String startEnd = formatTimeRange(sessionDay);
                writeLine(c, col1, y, regular, 10, sessionDate.format(SLASH_DATE));
                writeLine(c, col2, y, regular, 10, "Field");
                writeLine(c, col3, y, regular, 10, startEnd);
 
                // Lunch-option note removed (Michael, 2026-08-19) --
                // "we have not done a lunch option at a client site
                // since I have worked here."
 
                drawFooter(c, bold, regular, sender);
            }
 
            // ================= PAGE 2 =================
            PDPage page2 = new PDPage(PDRectangle.LETTER);
            document.addPage(page2);
            try (PDPageContentStream c = new PDPageContentStream(document, page2)) {
                float y = PAGE_HEIGHT - 50;
 
                writeLine(c, MARGIN_LEFT, y, bold, 13, "Cost");
                y -= 18;
                int fieldAttendees = session.getBidNumFieldAttendees() != null ? session.getBidNumFieldAttendees() : 0;
                int lectureAttendees = session.getBidNumSelfpacedLectureAttendees() != null ? session.getBidNumSelfpacedLectureAttendees() : 0;
                List<String> capLines = wrapText(
                        "For up to " + fieldAttendees + " attendees to the field class, and up to " + lectureAttendees
                                + " attendees using the online self-paced lecture.",
                        regular, 11, CONTENT_WIDTH - 20);
                for (String line : capLines) {
                    writeLine(c, MARGIN_LEFT + 20, y, regular, 11, line);
                    y -= 14;
                }
 
                y -= 14;
                float labelX = MARGIN_LEFT + 20;
                float amountX = PAGE_WIDTH - MARGIN_RIGHT - 70;
                writeLine(c, labelX, y, regular, 11, "Base Cost");
                rightAlignedText(c, amountX + 70, y, regular, 11, formatMoney(session.getPrivateCost()));
 
                y -= 20;
                writeLine(c, labelX, y, bold, 10, "Additional Costs");
 
                y -= 15;
                writeLine(c, labelX + 15, y, regular, 10,
                        "Additional Online Self-Paced Lecture Attendees (over " + lectureAttendees + ")");
                // selfPacedLecturePrice deliberately NOT required to be
                // set (Michael, 2026-08-19) -- defaults to $50, the
                // standard rate, overridable later once the Client
                // Page/Employees feature exists.
                java.math.BigDecimal lecturePrice = session.getSelfPacedLecturePrice() != null
                        ? session.getSelfPacedLecturePrice() : java.math.BigDecimal.valueOf(50);
                writeLine(c, amountX - 90, y, regular, 10, "Number x " + formatMoney(lecturePrice) + " ea");
                rightAlignedText(c, amountX + 70, y, italic, 10, "?");
 
                y -= 15;
                writeLine(c, labelX + 15, y, regular, 10,
                        "Additional Field Attendees (over " + fieldAttendees + ")");
                writeLine(c, amountX - 90, y, regular, 10, "Number x " + formatMoney(session.getFieldTest()) + " ea");
                rightAlignedText(c, amountX + 70, y, italic, 10, "?");
 
                y -= 18;
                writeLine(c, labelX + 15, y, regular, 10, "Additional costs subtotal");
                rightAlignedText(c, amountX + 70, y, boldItalic, 10, "0");
 
                y -= 24;
                writeLine(c, labelX, y, bold, 12, "Total Cost");
                rightAlignedText(c, amountX + 70, y, bold, 12, formatMoney(session.getPrivateCost()));
                y -= 14;
                rightAlignedText(c, amountX + 70, y, italic, 9, "(plus or minus unknowns)");
 
                // Persistent PO transparency (Michael, 2026-08-23) --
                // if the client has an active Persistent PO on file,
                // show it here so they know what's already available
                // before purchasing decides whether to issue a new,
                // session-specific PO instead. Deliberately shown ONLY
                // for a resolved PERSISTENT PO, not SESSION or NONE --
                // at bid-generation time a session essentially never
                // has its own PO yet (that's typically entered
                // afterward, once purchasing generates one from this
                // bid), and a bare "no PO on file" note would read
                // oddly on a client-facing document.
                SessionPoResolutionService.ResolvedPo resolvedPo = poResolutionService.resolve(session);
                if (resolvedPo.source() == SessionPoResolutionService.PoSource.PERSISTENT) {
                    y -= 20;
                    writeLine(c, labelX, y, regular, 10,
                            "PO on file: " + resolvedPo.poNumber() + " -- " + formatMoney(resolvedPo.amountRemaining()) + " remaining");
                }
 
                y -= 30;
                writeLine(c, MARGIN_LEFT, y, bold, 12, "Competitive Pricing");
                y -= 16;
                for (String line : wrapText(
                        "CAA is committed to offering the best value in the marketplace; we will strive to match a competitor's bid.",
                        regular, 11, CONTENT_WIDTH)) {
                    writeLine(c, MARGIN_LEFT, y, regular, 11, line);
                    y -= 14;
                }
 
                y -= 10;
                writeLine(c, MARGIN_LEFT, y, bold, 12, "Next Steps");
                y -= 16;
                String[] steps = {
                        "To finalize this commitment, we require a purchase order (PO) or credit card payment.",
                        "Enrolling your personnel via your client portal before the start-of-session helps speed up the sign-in process.",
                        "CAA will stay in contact with you prior to, and during the session.",
                        "Upon completion of the session, you will be billed for costs as outlined above.",
                };
                int stepNum = 1;
                for (String step : steps) {
                    List<String> stepLines = wrapText(step, regular, 10, CONTENT_WIDTH - 30);
                    writeLine(c, MARGIN_LEFT + 20, y, regular, 10, stepNum + ". " + stepLines.get(0));
                    y -= 13;
                    for (int i = 1; i < stepLines.size(); i++) {
                        writeLine(c, MARGIN_LEFT + 34, y, regular, 10, stepLines.get(i));
                        y -= 13;
                    }
                    stepNum++;
                }
 
                y -= 12;
                writeLine(c, MARGIN_LEFT, y, boldItalic, 10, "Please note the extra details described on next page.");
 
                y -= 24;
                for (String line : wrapText(
                        "If you have any questions or concerns, please call my cell phone (see footer below). "
                                + "I look forward to finalizing your company's training.",
                        regular, 11, CONTENT_WIDTH)) {
                    writeLine(c, MARGIN_LEFT, y, regular, 11, line);
                    y -= 14;
                }
 
                y -= 16;
                writeLine(c, MARGIN_LEFT, y, regular, 11, "Sincerely,");
                // Blank signature line -- SAME gap as CertificatePdfService
                // (no per-staff signature image mechanism yet). See this
                // class's Javadoc.
                y -= 40;
                c.setLineWidth(0.75f);
                line(c, MARGIN_LEFT, y, MARGIN_LEFT + 180, y);
                y -= 14;
                writeLine(c, MARGIN_LEFT, y, regular, 11, sender.getName());
                y -= 14;
                writeLine(c, MARGIN_LEFT, y, regular, 11, "Compliance Assurance Associates, Inc.");
 
                drawFooter(c, bold, regular, sender);
            }
 
            // ================= PAGE 3 =================
            PDPage page3 = new PDPage(PDRectangle.LETTER);
            document.addPage(page3);
            try (PDPageContentStream c = new PDPageContentStream(document, page3)) {
                float y = PAGE_HEIGHT - 50;
                writeLine(c, MARGIN_LEFT, y, bold, 12, "Extra Details:");
                y -= 20;
                String extraDetails = session.getBidExtraDetailsField() != null ? session.getBidExtraDetailsField() : "";
                for (String line : wrapText(extraDetails, regular, 11, CONTENT_WIDTH - 20)) {
                    writeLine(c, MARGIN_LEFT + 20, y, regular, 11, line);
                    y -= 14;
                }
 
                drawFooter(c, bold, regular, sender);
            }
 
            document.save(filePath.toFile());
        }
 
        session.setBidQuoteNumber(quoteNumber);
        session.setLastBidPdfPath(filePath.toString());
        return filePath.toString();
    }
 
    private void drawFooter(PDPageContentStream c, PDFont bold, PDFont regular, StaffUser sender) throws IOException {
        c.setNonStrokingColor(150f / 255f, 150f / 255f, 150f / 255f);
        float y = 40;
        centeredText(c, regular, 8, y, "Compliance Assurance Associates, Inc.");
        y -= 11;
        centeredText(c, regular, 8, y,
                "682 Orvil Smith Rd., Harvest AL 35749    Main: 901-381-9960    Fax: 901-381-9958");
        y -= 11;
        String contactName = sender.getName() != null ? sender.getName() : "";
        String mobile = sender.getMobilePhone() != null ? sender.getMobilePhone() : "N/A";
        String email = sender.getEmail() != null ? sender.getEmail() : "N/A";
        centeredText(c, regular, 8, y,
                "Contact: " + contactName + "    Mobile: " + mobile + "    Email: " + email);
        c.setNonStrokingColor(0f, 0f, 0f);
    }
 
    private String resolveContactName(Client client) {
        String first = client.getFirstName();
        String last = client.getLastName();
        if (first != null || last != null) {
            return ((first != null ? first : "") + " " + (last != null ? last : "")).trim();
        }
        return client.getCompany() != null ? client.getCompany() : "Valued Client";
    }
 
    private String resolveFieldLocation(Session session) {
        if (session.getFieldCity() != null && session.getFieldState() != null) {
            return session.getFieldCity() + ", " + session.getFieldState();
        }
        if (session.getAddressCity() != null && session.getAddressState() != null) {
            return session.getAddressCity() + ", " + session.getAddressState();
        }
        return session.getLocationName() != null ? session.getLocationName() : "the field site";
    }
 
    private String joinCityStateZip(String city, String state, String zip) {
        String cityState = java.util.stream.Stream.of(city, state).filter(s -> s != null && !s.isBlank())
                .reduce((a, b) -> a + ", " + b).orElse("");
        if (zip == null || zip.isBlank()) return cityState;
        return cityState.isBlank() ? zip : cityState + "  " + zip;
    }
 
    private String formatTimeRange(SessionDay sessionDay) {
        if (sessionDay.getStartTime() == null || sessionDay.getEndTime() == null) {
            return "TBD";
        }
        DateTimeFormatter timeFormat = DateTimeFormatter.ofPattern("hh:mm a");
        return sessionDay.getStartTime().format(timeFormat).toLowerCase()
                + " - " + sessionDay.getEndTime().format(timeFormat).toLowerCase();
    }
 
    private String formatMoney(BigDecimal value) {
        if (value == null) return "$0";
        BigDecimal rounded = value.setScale(0, RoundingMode.HALF_UP);
        return "$" + rounded.toPlainString();
    }
 
    private void line(PDPageContentStream c, float x1, float y1, float x2, float y2) throws IOException {
        c.moveTo(x1, y1);
        c.lineTo(x2, y2);
        c.stroke();
    }
 
    private void writeLine(PDPageContentStream c, float x, float y, PDFont font, float size, String text) throws IOException {
        c.beginText();
        c.setFont(font, size);
        c.newLineAtOffset(x, y);
        c.showText(text);
        c.endText();
    }
 
    private void centeredText(PDPageContentStream c, PDFont font, float size, float y, String text) throws IOException {
        float textWidth = font.getStringWidth(text) / 1000 * size;
        float x = (PAGE_WIDTH - textWidth) / 2;
        writeLine(c, x, y, font, size, text);
    }
 
    private void rightAlignedText(PDPageContentStream c, float rightEdge, float y, PDFont font, float size, String text) throws IOException {
        float textWidth = font.getStringWidth(text) / 1000 * size;
        writeLine(c, rightEdge - textWidth, y, font, size, text);
    }
 
    /** Simple greedy word-wrap to a fixed pixel width, since PDFBox has no built-in text-flow support. */
    private List<String> wrapText(String text, PDFont font, float size, float maxWidth) throws IOException {
        List<String> lines = new java.util.ArrayList<>();
        StringBuilder current = new StringBuilder();
        for (String word : text.split("\\s+")) {
            String candidate = current.isEmpty() ? word : current + " " + word;
            float width = font.getStringWidth(candidate) / 1000 * size;
            if (width > maxWidth && !current.isEmpty()) {
                lines.add(current.toString());
                current = new StringBuilder(word);
            } else {
                current = new StringBuilder(candidate);
            }
        }
        if (!current.isEmpty()) lines.add(current.toString());
        return lines;
    }
}