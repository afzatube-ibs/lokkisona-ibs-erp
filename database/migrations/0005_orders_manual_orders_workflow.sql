-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v0.1.22 orders, manual orders, and workflow history draft.
-- Logical relationships are enforced by the ERP service layer first; foreign keys are intentionally deferred.

CREATE TABLE IF NOT EXISTS orders (
    order_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_source_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NULL,
    source_order_id VARCHAR(120) NULL,
    source_order_reference VARCHAR(160) NULL,
    source_invoice_reference VARCHAR(160) NULL,
    order_reference VARCHAR(160) NOT NULL,
    customer_name VARCHAR(190) NULL,
    customer_phone VARCHAR(80) NULL,
    customer_address TEXT NULL,
    payment_method VARCHAR(120) NULL,
    order_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    ibs_status VARCHAR(120) NOT NULL DEFAULT 'new_order',
    courier_name VARCHAR(120) NULL,
    tracking_number VARCHAR(160) NULL,
    courier_status VARCHAR(120) NULL,
    cost_snapshot_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    ordered_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_orders_reference (order_reference),
    KEY idx_orders_source_reference (source_order_reference),
    KEY idx_orders_source_order_id (source_order_id),
    KEY idx_orders_business_source (business_source_id),
    KEY idx_orders_supplier (supplier_id),
    KEY idx_orders_ibs_status (ibs_status),
    KEY idx_orders_tracking (tracking_number),
    KEY idx_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_variant_id INT UNSIGNED NULL,
    source_product_id VARCHAR(120) NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_label VARCHAR(190) NULL,
    quantity INT NOT NULL DEFAULT 1,
    selling_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    supplier_cost_snapshot DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    KEY idx_order_items_variant (product_variant_id),
    KEY idx_order_items_source_product (source_product_id),
    KEY idx_order_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS manual_orders (
    manual_order_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_source_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NULL,
    manual_order_reference VARCHAR(160) NOT NULL,
    external_order_reference VARCHAR(160) NULL,
    external_invoice_reference VARCHAR(160) NULL,
    customer_name VARCHAR(190) NULL,
    customer_phone VARCHAR(80) NULL,
    customer_address TEXT NULL,
    order_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    ibs_status VARCHAR(120) NOT NULL DEFAULT 'new_order',
    entry_status VARCHAR(40) NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    confirmed_by INT UNSIGNED NULL,
    confirmed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_manual_order_reference (manual_order_reference),
    KEY idx_manual_external_reference (external_order_reference),
    KEY idx_manual_source (business_source_id),
    KEY idx_manual_supplier (supplier_id),
    KEY idx_manual_status (entry_status),
    KEY idx_manual_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS manual_order_items (
    manual_order_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    manual_order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_variant_id INT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_label VARCHAR(190) NULL,
    quantity INT NOT NULL DEFAULT 1,
    selling_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    supplier_cost_snapshot DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_manual_items_manual_order (manual_order_id),
    KEY idx_manual_items_product (product_id),
    KEY idx_manual_items_variant (product_variant_id),
    KEY idx_manual_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_workflow_histories (
    order_workflow_history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NULL,
    manual_order_id BIGINT UNSIGNED NULL,
    from_status VARCHAR(120) NULL,
    to_status VARCHAR(120) NOT NULL,
    action_note TEXT NULL,
    changed_by INT UNSIGNED NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_workflow_order (order_id),
    KEY idx_workflow_manual_order (manual_order_id),
    KEY idx_workflow_to_status (to_status),
    KEY idx_workflow_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
