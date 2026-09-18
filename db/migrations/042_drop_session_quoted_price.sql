-- ============================================================================
-- Migration 042: Drop sessions.quoted_price
-- Reference: Michael, 2026-09-03.
--
-- Confirmed with Michael: quotedPrice and privateCost were never the
-- same field, despite the naming/wording confusion -- two genuinely
-- separate columns. Found live: QboInvoiceService's own invoice line
-- item read quotedPrice, while the UI's "Private Cost" field, the
-- "ready to publish" gate, and bid generation itself all already,
-- correctly used privateCost -- meaning quotedPrice was silently dead
-- for any real, legitimate purpose, only ever populated by copy-
-- forward, never touched by any real UI edit. Confirmed with Michael:
-- privateCost is the one, real, single field going forward -- what
-- appears on the client bid, and (absent overage) what the invoice
-- bills. quotedPrice removed entirely, not left dormant.
-- ============================================================================

ALTER TABLE sessions DROP COLUMN IF EXISTS quoted_price;
