-- ============================================================================
-- Migration 024: Session Pricing Fields
-- Reference: Michael, 2026-08-19.
--
-- Private Cost: new flat-rate field for a Private Session, covering up
-- to 15 attendees.
--
-- Field Test: renamed from overage_rate_per_person. One field, three
-- uses depending on session type -- confirmed with Michael:
--   - Public: the standard per-person rate (management-set, typically $275)
--   - Private: the overage rate per person beyond the first 15
--   - Semi-Private: the per-person rate WITH the discount already
--     baked in by whoever sets it (e.g. $225 instead of $275) --
--     replaces a separate discount-multiplier field entirely.
--
-- bid_discount_field_for_public: dropped. Confirmed redundant with
-- Michael -- Semi-Private sessions now just get Field Test set
-- directly to the discounted number, rather than a separate
-- multiplier applied on top of the Public rate.
-- ============================================================================

ALTER TABLE sessions RENAME COLUMN overage_rate_per_person TO field_test;
ALTER TABLE sessions ADD COLUMN private_cost NUMERIC(10,2);
ALTER TABLE sessions DROP COLUMN bid_discount_field_for_public;
