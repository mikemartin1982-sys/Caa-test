package com.caa.platform.certification;

import com.caa.platform.session.Session;
import com.caa.platform.staff.StaffUser;
import com.caa.platform.student.Student;
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
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.format.DateTimeFormatter;
import java.util.List;

/**
 * Section 4g: generates the PDF a student receives the instant their
 * Enrollment flips to CERTIFIED. Called from
 * CertificationDeterminationService right after a Certification is saved
 * with passFail=true.
 *
 * Rebuilt 2026-08-17 to match a real certificate example Michael
 * provided (nelson_raul_torres_method_9.pdf): decorative corner-bracket
 * border, "COMPLIANCE ASSURANCE ASSOCIATES INC." header + tagline,
 * "This is to acknowledge that" + underlined student name, a
 * certificate number, the exact 40 CFR 60 Appendix A Method 9 body
 * wording, White/Black smoke scores, a signature line, and a footer
 * with the company address/phone/site. Confirmed with Michael: this is
 * the FIELD MANAGER'S signature/name on the certificate, NOT the
 * student's -- the student signature captured in the live-testing flow
 * (LiveTestingService.submitSignature()) is a separate attestation
 * record and does NOT appear here.
 *
 * ============================================================================
 * TWO REAL GAPS -- READ BEFORE TRUSTING THE CERTIFICATE NUMBER OR THE
 * SIGNATURE LINE THIS PRODUCES
 * ============================================================================
 * 1. Certificate number format (Michael, 2026-08-17): [CityCode]
 *    [QboClassRefId]-[StudentId], e.g. "MAC260220-25580". Session
 *    .qboClassRefId exists but is tied to the QuickBooks integration,
 *    still blocked on credentials -- it will be null for every real
 *    session until that access exists. There's also no "city code"
 *    concept anywhere in this codebase yet (RegionType is broad,
 *    WESTERN/TEXAS, not city-level). buildCertificateNumber() below
 *    degrades GRACEFULLY when either piece is missing -- it prints
 *    "PENDING" in that segment rather than silently producing a
 *    confusingly-real-looking wrong number. Once both dependencies
 *    exist, only buildCertificateNumber() needs to change.
 * 2. The Field Manager's signature is drawn directly on the real
 *    certificate (see Rebecca Walker's example) -- but there's no
 *    mechanism ANYWHERE in this codebase for a Field Manager to
 *    provide/store their own signature image. This version leaves a
 *    blank signature line (matching a real paper form waiting to be
 *    signed) with their typed name beneath it, exactly as the layout
 *    requires, but does not attempt to draw an actual signature image.
 *    A future feature, not yet scoped.
 * ============================================================================
 *
 * ============================================================================
 * STILL PARTIALLY UNVERIFIED -- READ BEFORE RELYING ENTIRELY ON THIS FILE
 * ============================================================================
 * The original, simpler version of this file (plain text only, no
 * lines/rectangles) has now been INDIRECTLY CONFIRMED to compile and
 * run correctly -- it fired successfully during real testing tonight
 * (2026-08-17) when enrollment 5 passed twice via the live-testing
 * flow, with no compile errors ever reported. This rebuild reuses that
 * exact same proven text-drawing API (PDPageContentStream,
 * PDType1Font, Standard14Fonts.FontName) with high confidence.
 *
 * The NEW surface area -- moveTo/lineTo/stroke/setLineWidth for the
 * decorative border -- is standard, long-stable PDFBox API, but hasn't
 * been exercised in THIS codebase before, so treat it with the same
 * "compile first, in isolation" caution as everything else PDFBox in
 * this project. If it doesn't compile, paste the error the same way as
 * every other bug this session and we'll fix it the same way.
 * ============================================================================
 */
@Service
public class CertificatePdfService {

    @Value("${app.certificates.output-dir:./certificates}")
    private String outputDir;

    private static final float PAGE_WIDTH = PDRectangle.LETTER.getHeight();
    private static final float PAGE_HEIGHT = PDRectangle.LETTER.getWidth();

    public String generate(Certification certification) throws IOException {
        Student student = certification.getEnrollment().getStudent();
        Session session = certification.getEnrollment().getSession();
        StaffUser fieldManager = session.getFieldManager();

        Path dir = Path.of(outputDir);
        Files.createDirectories(dir);

        String timestamp = DateTimeFormatter.ofPattern("yyyyMMdd-HHmmss")
                .format(java.time.LocalDateTime.now());
        String filename = "certificate-" + certification.getId() + "-" + timestamp + ".pdf";
        Path filePath = dir.resolve(filename);

        try (PDDocument document = new PDDocument()) {
            PDPage page = new PDPage(new PDRectangle(PAGE_WIDTH, PAGE_HEIGHT));
            document.addPage(page);

            try (PDPageContentStream content = new PDPageContentStream(document, page)) {
                drawBorder(content);

                PDFont regular = new PDType1Font(Standard14Fonts.FontName.TIMES_ROMAN);
                PDFont bold = new PDType1Font(Standard14Fonts.FontName.TIMES_BOLD);
                PDFont italic = new PDType1Font(Standard14Fonts.FontName.TIMES_ITALIC);

                float y = 540;

                centeredText(content, bold, 22, y, "COMPLIANCE ASSURANCE ASSOCIATES INC.");
                y -= 16;
                centeredText(content, italic, 10, y, "Helping Industry Comply with Environmental Regulations");

                y -= 40;
                centeredText(content, regular, 13, y, "This is to acknowledge that");

                y -= 30;
                String studentName = student.getName();
                centeredText(content, bold, 20, y, studentName);
                underlineCentered(content, studentName, bold, 20, y - 4);

                y -= 16;
                String certNumber = buildCertificateNumber(session, student.getId());
                centeredText(content, regular, 9, y, certNumber);

                y -= 12;
                centeredText(content, italic, 8, y,
                        "Certificate verification is available at compliance-assurance.com/certs.php using the last name and "
                                + certNumber.substring(certNumber.indexOf('-') + 1));

                y -= 28;
                List<String> bodyLines = List.of(
                        "successfully participated in Visible Emissions Evaluation field training and",
                        "certification and pursuant to US EPA 40 CFR 60 Appendix A, Reference Method",
                        "9, as amended, is certified to evaluate Visible Emissions for a period of six (6)",
                        "months from the date of this certification.");
                // Michael, 2026-08-17: found live during testing -- a
                // fixed left margin looked visually off-center on the
                // wider landscape page (lines never reached anywhere
                // near the right edge). Centers the paragraph BLOCK as
                // a whole -- widest line determines a shared left
                // margin, so it still reads as normal left-aligned
                // text, but the block itself sits centered on the page.
                float maxLineWidth = 0;
                for (String line : bodyLines) {
                    maxLineWidth = Math.max(maxLineWidth, regular.getStringWidth(line) / 1000 * 12);
                }
                float bodyLeft = (PAGE_WIDTH - maxLineWidth) / 2;
                for (String line : bodyLines) {
                    writeLine(content, bodyLeft, y, regular, 12, line);
                    y -= 17;
                }

                y -= 16;
                String whiteScore = formatScore(certification.getWhiteCumulativeDeviation());
                String blackScore = formatScore(certification.getBlackCumulativeDeviation());
                writeLine(content, bodyLeft, y, italic, 11,
                        "White-smoke score: " + whiteScore + "     Black-smoke score: " + blackScore);

                // Signature row -- blank line for the Field Manager's
                // actual (pen-on-paper-style) signature; see this
                // class's Javadoc, gap #2. Three columns: signature,
                // location, date.
                y -= 60;
                float col1 = bodyLeft;
                float col2 = bodyLeft + 260;
                float col3 = bodyLeft + 400;

                content.setLineWidth(0.75f);
                line(content, col1, y, col1 + 200, y);
                line(content, col2, y, col2 + 120, y);
                line(content, col3, y, col3 + 120, y);

                y -= 12;
                String fieldManagerName = fieldManager != null ? fieldManager.getName() : "Field Manager";
                writeLine(content, col1, y, regular, 9, fieldManagerName + " - Field Manager");
                writeLine(content, col2, y, regular, 9, "Location");
                writeLine(content, col3, y, regular, 9, "Date");

                y -= 14;
                String location = resolveLocation(session);
                String issueDateText = certification.getIssueDate() != null
                        ? certification.getIssueDate().format(DateTimeFormatter.ofPattern("MM/dd/yyyy"))
                        : "N/A";
                writeLine(content, col2, y, italic, 9, location);
                writeLine(content, col3, y, italic, 9, issueDateText);

                centeredText(content, regular, 8, 40,
                        "Compliance Assurance Associates, Inc. 682 Orvil Smith Rd, Harvest, AL, 35749. 901-381-9960. compliance-assurance.com");
            }

            document.save(filePath.toFile());
        }

        return filePath.toString();
    }

    /**
     * [CityCode][QboClassRefId]-[StudentId] (Michael, 2026-08-17) --
     * degrades gracefully when either the city code (no such concept
     * exists yet) or the QBO reference (blocked integration) is
     * missing, printing "PENDING" in that segment rather than a
     * confusingly-real-looking wrong number. Once both real data
     * sources exist, only this method needs to change.
     */
    private String buildCertificateNumber(Session session, Long studentId) {
        // No city-code concept exists yet -- see this class's Javadoc, gap #1.
        String cityCode = "PENDING";
        String qboRef = session.getQboClassRefId() != null ? session.getQboClassRefId() : "PENDING";
        return cityCode + qboRef + "-" + studentId;
    }

    /**
     * Michael, 2026-08-17: found live during testing -- White and
     * Black cumulative deviation were showing as "0" vs "0.00", an
     * inconsistent BigDecimal scale, not a data problem (same
     * mathematical value either way). Normalizes both to a plain
     * whole number, matching the real certificate's "12"/"22" style.
     */
    private String formatScore(java.math.BigDecimal value) {
        if (value == null) return "N/A";
        return value.setScale(0, java.math.RoundingMode.HALF_UP).toPlainString();
    }

    private String resolveLocation(Session session) {
        if (session.getFieldCity() != null && session.getFieldState() != null) {
            return session.getFieldCity() + ", " + session.getFieldState();
        }
        if (session.getAddressCity() != null && session.getAddressState() != null) {
            return session.getAddressCity() + ", " + session.getAddressState();
        }
        return session.getLocationName() != null ? session.getLocationName() : "N/A";
    }

    /**
     * A simplified stand-in for the real certificate's ornate corner
     * brackets -- two nested rectangles with thicker corner accents.
     * Not a pixel-exact match; a reasonable first pass to refine once
     * Michael can see the actual rendered output.
     */
    private void drawBorder(PDPageContentStream content) throws IOException {
        float margin = 28;
        float inset = 40;

        content.setLineWidth(2f);
        content.addRect(margin, margin, PAGE_WIDTH - 2 * margin, PAGE_HEIGHT - 2 * margin);
        content.stroke();

        content.setLineWidth(1f);
        content.addRect(margin + 6, margin + 6, PAGE_WIDTH - 2 * (margin + 6), PAGE_HEIGHT - 2 * (margin + 6));
        content.stroke();

        // Corner accents -- thicker "L" brackets just inside the outer border.
        float accentLen = 55;
        content.setLineWidth(4f);
        float[][] corners = {
                {inset, PAGE_HEIGHT - inset},          // top-left
                {PAGE_WIDTH - inset, PAGE_HEIGHT - inset}, // top-right
                {inset, inset},                         // bottom-left
                {PAGE_WIDTH - inset, inset},             // bottom-right
        };
        for (float[] corner : corners) {
            float cx = corner[0];
            float cy = corner[1];
            boolean left = cx < PAGE_WIDTH / 2;
            boolean top = cy > PAGE_HEIGHT / 2;
            float hx = left ? cx + accentLen : cx - accentLen;
            float vy = top ? cy - accentLen : cy + accentLen;
            line(content, cx, cy, hx, cy);
            line(content, cx, cy, cx, vy);
        }
    }

    private void line(PDPageContentStream content, float x1, float y1, float x2, float y2) throws IOException {
        content.moveTo(x1, y1);
        content.lineTo(x2, y2);
        content.stroke();
    }

    private void writeLine(PDPageContentStream content, float x, float y, PDFont font, float size, String text) throws IOException {
        content.beginText();
        content.setFont(font, size);
        content.newLineAtOffset(x, y);
        content.showText(text);
        content.endText();
    }

    private void centeredText(PDPageContentStream content, PDFont font, float size, float y, String text) throws IOException {
        float textWidth = font.getStringWidth(text) / 1000 * size;
        float x = (PAGE_WIDTH - textWidth) / 2;
        writeLine(content, x, y, font, size, text);
    }

    private void underlineCentered(PDPageContentStream content, String text, PDFont font, float size, float y) throws IOException {
        float textWidth = font.getStringWidth(text) / 1000 * size;
        float x = (PAGE_WIDTH - textWidth) / 2;
        content.setLineWidth(1f);
        line(content, x, y, x + textWidth, y);
    }
}