-- ============================================================================
-- Credit Manager - Table of credit types (must run first: other tables depend on it)
-- ============================================================================

CREATE TABLE llx_credits_types (
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  entity             integer DEFAULT 1 NOT NULL,
  code               varchar(32) NOT NULL,
  label              varchar(255) NOT NULL,
  unit               varchar(32) DEFAULT 'hour',
  auto_debit         tinyint DEFAULT 0 NOT NULL,
  debit_delay_days   integer NULL,
  active             tinyint DEFAULT 1 NOT NULL,
  precision_unit     varchar(10) DEFAULT 'hour',
  tms                timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  date_creation      datetime NULL,
  fk_user_creat      integer NULL,
  fk_user_modif      integer NULL,
  UNIQUE KEY uk_credits_types_code_entity (code, entity),
  KEY idx_credits_types_entity (entity)
) ENGINE=innodb;
