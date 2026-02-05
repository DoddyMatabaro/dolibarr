-- ============================================================================
-- Credit Manager - Table of low balance alerts
-- ============================================================================

CREATE TABLE llx_credits_alerts (
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  entity           integer DEFAULT 1 NOT NULL,
  fk_soc           integer NOT NULL,
  fk_credit_type   integer NOT NULL,
  threshold_type   varchar(20) NOT NULL,
  date_alert       datetime NOT NULL,
  date_email_sent  datetime NULL,
  balance_at_alert double(24,8) NULL,
  reference_value  double(24,8) NULL,
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_credits_alerts_soc (fk_soc),
  KEY idx_credits_alerts_type (fk_credit_type),
  KEY idx_credits_alerts_date (date_alert),
  CONSTRAINT fk_credits_alerts_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid),
  CONSTRAINT fk_credits_alerts_type FOREIGN KEY (fk_credit_type) REFERENCES llx_credits_types (rowid)
) ENGINE=innodb;
