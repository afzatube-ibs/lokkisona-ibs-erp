-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v2.4.0 Return Report / Supplier Return Statement foundation.

CREATE TABLE IF NOT EXISTS ibs_return_reports (
    return_report_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_report_reference VARCHAR(160) NOT NULL,
    supplier_id INT UNSIGNED NULL,
    business_source_id INT UNSIGNED NULL,
    return_date DATE NULL,
    total_returns INT NOT NULL DEFAULT 0,
    total_quantity INT NOT NULL DEFAULT 0,
    total_adjustment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'locked',
    locked_by INT UNSIGNED NULL,
    locked_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_return_report_reference (return_report_reference),
    KEY idx_return_reports_supplier (supplier_id),
    KEY idx_return_reports_source (business_source_id),
    KEY idx_return_reports_status (status),
    KEY idx_return_reports_return_date (return_date),
    KEY idx_return_reports_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_return_report_items (
    return_report_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_report_id BIGINT UNSIGNED NOT NULL,
    return_receive_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    manual_order_id BIGINT UNSIGNED NULL,
    order_reference VARCHAR(160) NULL,
    product_cost_snapshot DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    item_count INT NOT NULL DEFAULT 0,
    return_type VARCHAR(80) NOT NULL DEFAULT '',
    return_reason VARCHAR(80) NOT NULL DEFAULT '',
    status VARCHAR(40) NOT NULL DEFAULT 'included',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_return_report_item_receive (return_receive_id),
    KEY idx_return_report_items_report (return_report_id),
    KEY idx_return_report_items_order (order_id),
    KEY idx_return_report_items_manual_order (manual_order_id),
    KEY idx_return_report_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staging columns on return receives (skip if columns already exist).
ALTER TABLE ibs_return_receives ADD COLUMN order_id BIGINT UNSIGNED NULL AFTER business_source_id;
ALTER TABLE ibs_return_receives ADD COLUMN return_reason VARCHAR(80) NULL AFTER return_type;
ALTER TABLE ibs_return_receives ADD KEY idx_return_receive_order (order_id);
