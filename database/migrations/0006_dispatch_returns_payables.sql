-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v0.1.22 dispatch, returns, payables, and settlement draft.
-- v0.4.2.4: CREATE TABLE names use configured prefix ibs_ (config/database.php).
-- Logical relationships are enforced by the ERP service layer first; foreign keys are intentionally deferred.

CREATE TABLE IF NOT EXISTS ibs_dispatch_reports (
    dispatch_report_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispatch_reference VARCHAR(160) NOT NULL,
    supplier_id INT UNSIGNED NULL,
    business_source_id INT UNSIGNED NULL,
    dispatch_date DATE NULL,
    total_orders INT NOT NULL DEFAULT 0,
    total_product_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    locked_by INT UNSIGNED NULL,
    locked_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_dispatch_reference (dispatch_reference),
    KEY idx_dispatch_supplier (supplier_id),
    KEY idx_dispatch_source (business_source_id),
    KEY idx_dispatch_status (status),
    KEY idx_dispatch_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_dispatch_report_items (
    dispatch_report_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispatch_report_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    manual_order_id BIGINT UNSIGNED NULL,
    order_reference VARCHAR(160) NULL,
    product_cost_snapshot DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    item_count INT NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'included',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_dispatch_items_report (dispatch_report_id),
    KEY idx_dispatch_items_order (order_id),
    KEY idx_dispatch_items_manual_order (manual_order_id),
    KEY idx_dispatch_items_reference (order_reference),
    KEY idx_dispatch_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_return_receives (
    return_receive_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_reference VARCHAR(160) NOT NULL,
    supplier_id INT UNSIGNED NULL,
    business_source_id INT UNSIGNED NULL,
    return_type VARCHAR(80) NOT NULL,
    total_items INT NOT NULL DEFAULT 0,
    total_cost_snapshot DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    received_by INT UNSIGNED NULL,
    received_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_return_receive_reference (return_reference),
    KEY idx_return_receive_supplier (supplier_id),
    KEY idx_return_receive_source (business_source_id),
    KEY idx_return_receive_type_status (return_type, status),
    KEY idx_return_receive_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_return_batches (
    return_batch_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_batch_reference VARCHAR(160) NOT NULL,
    supplier_id INT UNSIGNED NULL,
    total_returns INT NOT NULL DEFAULT 0,
    total_adjustment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_return_batch_reference (return_batch_reference),
    KEY idx_return_batches_supplier (supplier_id),
    KEY idx_return_batches_status (status),
    KEY idx_return_batches_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_return_batch_items (
    return_batch_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_batch_id BIGINT UNSIGNED NOT NULL,
    return_receive_id BIGINT UNSIGNED NULL,
    order_id BIGINT UNSIGNED NULL,
    manual_order_id BIGINT UNSIGNED NULL,
    product_id INT UNSIGNED NULL,
    product_variant_id INT UNSIGNED NULL,
    quantity INT NOT NULL DEFAULT 1,
    cost_snapshot DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    adjustment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_return_batch_items_batch (return_batch_id),
    KEY idx_return_batch_items_receive (return_receive_id),
    KEY idx_return_batch_items_order (order_id),
    KEY idx_return_batch_items_manual_order (manual_order_id),
    KEY idx_return_batch_items_product (product_id),
    KEY idx_return_batch_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_payable_ledgers (
    payable_ledger_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    ledger_reference VARCHAR(160) NOT NULL,
    ledger_type VARCHAR(80) NOT NULL,
    source_reference VARCHAR(160) NULL,
    debit_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    credit_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    balance_after DECIMAL(14,2) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payable_ledger_reference (ledger_reference),
    KEY idx_payable_ledger_supplier (supplier_id),
    KEY idx_payable_ledger_type_status (ledger_type, status),
    KEY idx_payable_ledger_source_reference (source_reference),
    KEY idx_payable_ledger_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_supplier_invoices (
    supplier_invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    invoice_reference VARCHAR(160) NOT NULL,
    invoice_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    invoice_status VARCHAR(40) NOT NULL DEFAULT 'draft',
    invoice_date DATE NULL,
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_supplier_invoice_reference (invoice_reference),
    KEY idx_supplier_invoices_supplier (supplier_id),
    KEY idx_supplier_invoices_status (invoice_status),
    KEY idx_supplier_invoices_date (invoice_date),
    KEY idx_supplier_invoices_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_supplier_payments (
    supplier_payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    payment_reference VARCHAR(160) NOT NULL,
    payment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(120) NULL,
    payment_status VARCHAR(40) NOT NULL DEFAULT 'draft',
    paid_by INT UNSIGNED NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_supplier_payment_reference (payment_reference),
    KEY idx_supplier_payments_supplier (supplier_id),
    KEY idx_supplier_payments_status (payment_status),
    KEY idx_supplier_payments_paid_at (paid_at),
    KEY idx_supplier_payments_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_payable_adjustments (
    payable_adjustment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    adjustment_reference VARCHAR(160) NOT NULL,
    adjustment_type VARCHAR(80) NOT NULL,
    source_reference VARCHAR(160) NULL,
    adjustment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_payable_adjustment_reference (adjustment_reference),
    KEY idx_payable_adjustments_supplier (supplier_id),
    KEY idx_payable_adjustments_type_status (adjustment_type, status),
    KEY idx_payable_adjustments_source_reference (source_reference),
    KEY idx_payable_adjustments_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
