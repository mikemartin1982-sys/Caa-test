-- ============================================================================
-- Migration 020: Final Answer Confirmation & Signature
-- Reference: Michael, 2026-08-17 -- once a student reaches their final
-- point, they must explicitly confirm "these answers are your own, not
-- somebody else's" before grading can happen. Only once EVERY student
-- who's reached their final point has confirmed does the Field Manager/
-- Operator get a "Grade Test" button. If they pass, a drawn signature
-- (finger/stylus, like signing for a package) is captured and stored
-- alongside their certificate.
--
-- final_answers_confirmed/_at live on certification_runs, not Session --
-- this is per-STUDENT (each has their own run), and a split-run
-- participant confirms/grades independently of full-run participants
-- who are still testing, matching how finishing already works.
--
-- signature_image_path on certifications follows the EXACT same
-- pattern as pdf_certificate_link (already on this table) -- a file
-- path, not a DB blob, matching how CertificatePdfService already
-- stores generated PDFs.
-- ============================================================================

ALTER TABLE certification_runs ADD COLUMN final_answers_confirmed BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE certification_runs ADD COLUMN final_answers_confirmed_at TIMESTAMPTZ;

ALTER TABLE certifications ADD COLUMN signature_image_path VARCHAR(500);
