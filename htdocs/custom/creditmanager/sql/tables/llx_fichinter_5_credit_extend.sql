-- ============================================================================
-- Credit Manager - Extend llx_fichinter with credit management fields
-- ============================================================================

ALTER TABLE llx_fichinter ADD COLUMN fk_credit_type integer NULL;
ALTER TABLE llx_fichinter ADD COLUMN credit_status varchar(20) DEFAULT 'SUBMITTED';
ALTER TABLE llx_fichinter ADD COLUMN credit_debit_reference varchar(50) NULL;
ALTER TABLE llx_fichinter ADD COLUMN credit_debit_date datetime NULL;
ALTER TABLE llx_fichinter ADD COLUMN credit_debit_amount decimal(15,2) NULL;
ALTER TABLE llx_fichinter ADD COLUMN credit_approval_date datetime NULL;
