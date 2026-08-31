-- ============================================================================
-- Migration 028: Students.phone nullable
-- Reference: Michael, 2026-08-23 -- found live while testing the VR
-- enrollment gate. StudentController.create() has always treated phone
-- as optional (CreateStudentRequest doesn't require it), but the
-- column itself was NOT NULL, so omitting it failed at the database
-- with a raw 500 rather than a clean validation error. A phone number
-- often genuinely isn't known yet when a student record is first
-- created (e.g. an organization adding an employee by name/email
-- only) -- made nullable to match how the field is actually used,
-- rather than forcing phone to become required in the API.
-- ============================================================================

ALTER TABLE students ALTER COLUMN phone DROP NOT NULL;
