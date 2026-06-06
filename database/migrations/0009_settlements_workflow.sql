-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v0.5.9 settlement workflow draft.

CREATE TABLE IF NOT EXISTS ibs_settlements (
    settlement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    settlement_reference VARCHAR(160) NOT NULL,
    period_type VARCHAR(40) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    dispatch_payable DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    invoice_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    deductions DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    payments DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    advances DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    adjustments DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    closing_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    workflow_status VARCHAR(40) NOT NULL DEFAULT 'draft',
    prepared_by INT UNSIGNED NULL,
    prepared_at DATETIME NULL,
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    paid_at DATETIME NULL,
    closed_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_settlement_reference (settlement_reference),
    KEY idx_settlements_supplier (supplier_id),
    KEY idx_settlements_period (period_start, period_end),
    KEY idx_settlements_workflow (workflow_status),
    KEY idx_settlements_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
