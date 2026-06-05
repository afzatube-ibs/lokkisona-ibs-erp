-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v0.1.22 invoice, printing, and supplier tool draft.
-- v0.4.2.4: CREATE TABLE names use configured prefix ibs_ (config/database.php).
-- Logical relationships are enforced by the ERP service layer first; foreign keys are intentionally deferred.

CREATE TABLE IF NOT EXISTS ibs_invoices (
    invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_reference VARCHAR(160) NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    manual_order_id BIGINT UNSIGNED NULL,
    business_source_id INT UNSIGNED NULL,
    invoice_type VARCHAR(80) NOT NULL,
    customer_name VARCHAR(190) NULL,
    invoice_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    invoice_status VARCHAR(40) NOT NULL DEFAULT 'draft',
    issued_by INT UNSIGNED NULL,
    issued_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_invoices_reference (invoice_reference),
    KEY idx_invoices_order (order_id),
    KEY idx_invoices_manual_order (manual_order_id),
    KEY idx_invoices_source (business_source_id),
    KEY idx_invoices_type_status (invoice_type, invoice_status),
    KEY idx_invoices_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_invoice_items (
    invoice_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_variant_id INT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_label VARCHAR(190) NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_invoice_items_invoice (invoice_id),
    KEY idx_invoice_items_product (product_id),
    KEY idx_invoice_items_variant (product_variant_id),
    KEY idx_invoice_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_invoice_templates (
    invoice_template_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(120) NOT NULL,
    template_name VARCHAR(160) NOT NULL,
    business_source_id INT UNSIGNED NULL,
    template_type VARCHAR(80) NOT NULL,
    template_config_json TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_invoice_template_key (template_key),
    KEY idx_invoice_templates_source (business_source_id),
    KEY idx_invoice_templates_type_status (template_type, status),
    KEY idx_invoice_templates_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_packing_prints (
    packing_print_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    packing_reference VARCHAR(160) NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    manual_order_id BIGINT UNSIGNED NULL,
    dispatch_report_id BIGINT UNSIGNED NULL,
    print_type VARCHAR(80) NOT NULL,
    print_status VARCHAR(40) NOT NULL DEFAULT 'draft',
    generated_by INT UNSIGNED NULL,
    generated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_packing_print_reference (packing_reference),
    KEY idx_packing_prints_order (order_id),
    KEY idx_packing_prints_manual_order (manual_order_id),
    KEY idx_packing_prints_dispatch (dispatch_report_id),
    KEY idx_packing_prints_type_status (print_type, print_status),
    KEY idx_packing_prints_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_print_logs (
    print_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    print_reference VARCHAR(160) NOT NULL,
    printable_type VARCHAR(80) NOT NULL,
    printable_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    user_id INT UNSIGNED NULL,
    route_path VARCHAR(190) NULL,
    context_json TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_print_logs_reference (print_reference),
    KEY idx_print_logs_printable (printable_type, printable_id),
    KEY idx_print_logs_action (action),
    KEY idx_print_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_supplier_quick_invoices (
    supplier_quick_invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NULL,
    quick_invoice_reference VARCHAR(160) NOT NULL,
    supplier_name VARCHAR(180) NULL,
    invoice_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    output_status VARCHAR(40) NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    generated_at DATETIME NULL,
    downloaded_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_supplier_quick_invoice_reference (quick_invoice_reference),
    KEY idx_supplier_quick_invoices_supplier (supplier_id),
    KEY idx_supplier_quick_invoices_status (output_status),
    KEY idx_supplier_quick_invoices_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_supplier_quick_invoice_items (
    supplier_quick_invoice_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_quick_invoice_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_quick_invoice_items_invoice (supplier_quick_invoice_id),
    KEY idx_quick_invoice_items_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_supplier_quick_invoice_audits (
    supplier_quick_invoice_audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_quick_invoice_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    user_id INT UNSIGNED NULL,
    message TEXT NULL,
    context_json TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_quick_invoice_audits_invoice (supplier_quick_invoice_id),
    KEY idx_quick_invoice_audits_action (action),
    KEY idx_quick_invoice_audits_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
