-- ============================================================================
-- Migration 034: Lecture Certificate Upload feature
-- Reference: Michael, 2026-08-30.
--
-- providers: third-party lecture provider lookup (competitors,
-- state-driven programs), matching real DIBs source Michael shared --
-- name + active flag only, no login/user id. Additions restricted to
-- Compliance Administrators, enforced at the application layer.
--
-- lecture_certificates: one generic certificate entity covering both
-- CAA's own future self-paced certificate generation and today's
-- third-party upload path, not something narrow built just for
-- uploads. Supersede-not-delete on re-upload -- full history retained,
-- matching Michael's own real CertificationRun screenshot pattern.
-- ============================================================================

CREATE TABLE providers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE lecture_certificates (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT NOT NULL REFERENCES students(id),
    source VARCHAR(20) NOT NULL,
    provider_id BIGINT REFERENCES providers(id),
    completion_date DATE NOT NULL,
    file_path VARCHAR(1024) NOT NULL,
    original_filename VARCHAR(255),
    uploaded_by_staff_id BIGINT NOT NULL REFERENCES staff_users(id),
    uploaded_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    superseded BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Full-history queries (findByStudentIdOrderByUploadedAtDesc) filter
-- on student_id alone, ordered by uploaded_at.
CREATE INDEX idx_lecture_certificates_student
    ON lecture_certificates (student_id);

-- Michael, 2026-08-30 -- a real, defensive guarantee, not just trusted
-- to the service layer's own logic: at most one non-superseded
-- certificate per student, enforced at the database itself. A future
-- bug in the supersede-on-reupload logic (marking the prior record
-- superseded before saving the new one) would otherwise be able to
-- silently leave two "current" certificates for the same student.
-- Also directly serves the "this student's current certificate"
-- lookup (findByStudentIdAndSupersededFalse), not just an integrity
-- check.
CREATE UNIQUE INDEX idx_lecture_certificates_one_active_per_student
    ON lecture_certificates (student_id) WHERE NOT superseded;
