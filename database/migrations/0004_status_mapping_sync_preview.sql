-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v0.1.22 status mapping, sync preview, import, and sync log draft.
-- Logical relationships are enforced by the ERP service layer first; foreign keys are intentionally deferred.

CREATE TABLE IF NOT EXISTS status_mappings (
    status_mapping_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_source_id INT UNSIGNED NULL,
    source_status VARCHAR(120) NOT NULL,
    ibs_status VARCHAR(120) NOT NULL,
    workflow_group VARCHAR(80) NULL,
    return_type VARCHAR(80) NULL,
    courier_status VARCHAR(120) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_status_mapping_source (business_source_id),
    KEY idx_status_mapping_source_status (source_status),
    KEY idx_status_mapping_ibs_status (ibs_status),
    KEY idx_status_mapping_active (is_active),
    KEY idx_status_mapping_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courier_status_mappings (
    courier_status_mapping_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    courier_name VARCHAR(120) NULL,
    source_courier_status VARCHAR(120) NOT NULL,
    ibs_courier_status VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_courier_mapping_name (courier_name),
    KEY idx_courier_mapping_source_status (source_courier_status),
    KEY idx_courier_mapping_ibs_status (ibs_courier_status),
    KEY idx_courier_mapping_active (is_active),
    KEY idx_courier_mapping_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_previews (
    sync_preview_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_source_id INT UNSIGNED NULL,
    preview_reference VARCHAR(120) NOT NULL,
    preview_type VARCHAR(80) NOT NULL DEFAULT 'test',
    total_found INT NOT NULL DEFAULT 0,
    total_new INT NOT NULL DEFAULT 0,
    total_existing INT NOT NULL DEFAULT 0,
    total_blocked INT NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    requested_by INT UNSIGNED NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    red_issues_summary TEXT NULL,
    UNIQUE KEY uq_sync_preview_reference (preview_reference),
    KEY idx_sync_preview_source (business_source_id),
    KEY idx_sync_preview_status (status),
    KEY idx_sync_preview_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_preview_items (
    sync_preview_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_preview_id BIGINT UNSIGNED NOT NULL,
    source_order_id VARCHAR(120) NULL,
    source_order_reference VARCHAR(160) NULL,
    source_invoice_reference VARCHAR(160) NULL,
    source_status VARCHAR(120) NULL,
    mapped_status VARCHAR(120) NULL,
    customer_name VARCHAR(190) NULL,
    order_total DECIMAL(14,2) NULL,
    item_count INT NOT NULL DEFAULT 0,
    preview_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    issue_summary TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_preview_items_preview (sync_preview_id),
    KEY idx_preview_items_source_order (source_order_id),
    KEY idx_preview_items_source_reference (source_order_reference),
    KEY idx_preview_items_status (preview_status),
    KEY idx_preview_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_imports (
    sync_import_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_preview_id BIGINT UNSIGNED NULL,
    business_source_id INT UNSIGNED NULL,
    import_reference VARCHAR(120) NOT NULL,
    total_selected INT NOT NULL DEFAULT 0,
    total_imported INT NOT NULL DEFAULT 0,
    total_failed INT NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    red_issues_summary TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sync_import_reference (import_reference),
    KEY idx_sync_import_preview (sync_preview_id),
    KEY idx_sync_import_source (business_source_id),
    KEY idx_sync_import_status (status),
    KEY idx_sync_import_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_logs (
    sync_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_source_id INT UNSIGNED NULL,
    sync_preview_id BIGINT UNSIGNED NULL,
    sync_import_id BIGINT UNSIGNED NULL,
    log_type VARCHAR(80) NOT NULL,
    status VARCHAR(40) NOT NULL,
    message TEXT NOT NULL,
    context_json TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sync_logs_source (business_source_id),
    KEY idx_sync_logs_preview (sync_preview_id),
    KEY idx_sync_logs_import (sync_import_id),
    KEY idx_sync_logs_type_status (log_type, status),
    KEY idx_sync_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
