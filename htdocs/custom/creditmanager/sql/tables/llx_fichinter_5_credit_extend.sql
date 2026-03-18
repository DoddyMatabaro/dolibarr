-- ============================================================================
-- Credit Manager - Extend llx_fichinter with credit management fields
-- ============================================================================

ALTER TABLE llx_fichinter
  ADD COLUMN fk_credit_type INTEGER NULL,
  ADD COLUMN credit_status VARCHAR(20) DEFAULT 'SUBMITTED',
  ADD COLUMN credit_debit_reference VARCHAR(50) NULL,
  ADD COLUMN credit_debit_date DATETIME NULL,
  ADD COLUMN credit_debit_amount DECIMAL(15,2) NULL,
  ADD COLUMN credit_approval_date DATETIME NULL;
