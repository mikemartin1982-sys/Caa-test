-- ============================================================================
-- Migration 025: Bid PDF Generation
-- Reference: Michael, 2026-08-19 -- matching a real bid template
-- (last_bid_sent.docx) he provided. Confirmed with Michael:
--   - Quote Number auto-generated as [StaffInitials]-[YYMMDD]-R[Revision]
--   - No per-staff signature image yet (blank line, same gap as the
--     certificate PDF) -- see BidPdfService's Javadoc.
--   - Output is a locked PDF, not an editable .docx.
--
-- bid_quote_number and last_bid_pdf_path mirror
-- Certification.pdfCertificateLink's established pattern -- file path
-- stored on the record, served via a dedicated download endpoint,
-- rather than storing the PDF bytes in the database.
-- ============================================================================

ALTER TABLE sessions ADD COLUMN bid_quote_number VARCHAR(50);
ALTER TABLE sessions ADD COLUMN last_bid_pdf_path VARCHAR(500);
