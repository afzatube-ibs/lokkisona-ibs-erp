-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v0.2.0 supplier opening balance, adjustment, audit, and launch cutover draft.
-- Logical relationships are enforced by the ERP service layer first; foreign keys are intentionally deferred.

CREATE TABLE IF NOT EXISTS supplier_opening_balances (
    supplier_opening_balance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    business_source_id INT UNSIGNED NULL,
    applies_to_all_sources TINYINT(1) NOT NULL DEFAULT 0,
    balance_type VARCHAR(80) NOT NULL DEFAULT 'payable_to_supplier',
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    currency_code VARCHAR(10) NOT NULL DEFAULT 'BDT',
    cutoff_date DATE NULL,
    calculation_summary TEXT NULL,
    reference_note TEXT NULL,
    proof_file_path VARCHAR(255) NULL,
    proof_file_name VARCHAR(190) NULL,
    owner_approval_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    owner_approved_by INT UNSIGNED NULL,
    owner_approved_at DATETIME NULL,
    entered_by INT UNSIGNED NULL,
    entered_at DATETIME NULL,
    locked_after_launch TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_opening_balances_supplier (supplier_id),
    KEY idx_opening_balances_source (business_source_id),
    KEY idx_opening_balances_balance_type (balance_type),
    KEY idx_opening_balances_approval_status (owner_approval_status),
    KEY idx_opening_balances_status (status),
    KEY idx_opening_balances_cutoff_date (cutoff_date),
    KEY idx_opening_balances_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_opening_balance_adjustments (
    adjustment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_opening_balance_id BIGINT UNSIGNED NOT NULL,
    adjustment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    reason TEXT NULL,
    adjusted_by INT UNSIGNED NULL,
    adjusted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_opening_adjustments_balance (supplier_opening_balance_id),
    KEY idx_opening_adjustments_adjusted_by (adjusted_by),
    KEY idx_opening_adjustments_adjusted_at (adjusted_at),
    KEY idx_opening_adjustments_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_opening_balance_audits (
    audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_opening_balance_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(120) NOT NULL,
    changed_by INT UNSIGNED NULL,
    changed_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_opening_audits_balance (supplier_opening_balance_id),
    KEY idx_opening_audits_action (action),
    KEY idx_opening_audits_changed_by (changed_by),
    KEY idx_opening_audits_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS launch_cutovers (
    cutover_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    go_live_date DATE NULL,
    cutoff_date DATE NULL,
    supplier_id INT UNSIGNED NULL,
    confirmed_by INT UNSIGNED NULL,
    confirmed_at DATETIME NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_launch_cutovers_supplier (supplier_id),
    KEY idx_launch_cutovers_status (status),
    KEY idx_launch_cutovers_go_live_date (go_live_date),
    KEY idx_launch_cutovers_cutoff_date (cutoff_date),
    KEY idx_launch_cutovers_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
