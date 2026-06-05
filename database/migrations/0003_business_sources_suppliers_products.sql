-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v0.1.22 business source, supplier, product, variant, cost, and stock draft.
-- v0.4.2.4: CREATE TABLE names use configured prefix ibs_ (config/database.php).
-- Logical relationships are enforced by the ERP service layer first; foreign keys are intentionally deferred.

CREATE TABLE IF NOT EXISTS ibs_businesses (
    business_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_name VARCHAR(160) NOT NULL,
    business_code VARCHAR(80) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_businesses_code (business_code),
    KEY idx_businesses_status (status),
    KEY idx_businesses_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_business_sources (
    business_source_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NULL,
    source_name VARCHAR(160) NOT NULL,
    source_type VARCHAR(80) NOT NULL,
    website_domain VARCHAR(190) NULL,
    order_source_label VARCHAR(120) NULL,
    default_supplier_id INT UNSIGNED NULL,
    default_workflow VARCHAR(120) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_business_sources_business_id (business_id),
    KEY idx_business_sources_supplier_id (default_supplier_id),
    KEY idx_business_sources_type (source_type),
    KEY idx_business_sources_status (status),
    KEY idx_business_sources_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_suppliers (
    supplier_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(180) NOT NULL,
    contact_person VARCHAR(160) NULL,
    phone VARCHAR(80) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    payment_terms VARCHAR(160) NULL,
    payable_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    linked_business_source_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_suppliers_name (supplier_name),
    KEY idx_suppliers_phone (phone),
    KEY idx_suppliers_status (status),
    KEY idx_suppliers_source (linked_business_source_id),
    KEY idx_suppliers_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_product_id VARCHAR(120) NULL,
    product_name VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NULL,
    business_source_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NULL,
    source_model VARCHAR(120) NULL,
    source_stock INT NULL,
    supplier_model VARCHAR(120) NULL,
    product_cost DECIMAL(14,2) NULL,
    vendor_stock INT NOT NULL DEFAULT 0,
    low_warning_threshold INT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    last_synced_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_products_source_product_id (source_product_id),
    KEY idx_products_source (business_source_id),
    KEY idx_products_supplier (supplier_id),
    KEY idx_products_status (status),
    KEY idx_products_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_product_variants (
    product_variant_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    option_name VARCHAR(160) NULL,
    option_value VARCHAR(160) NULL,
    source_option_id VARCHAR(120) NULL,
    source_option_value_id VARCHAR(120) NULL,
    source_model VARCHAR(120) NULL,
    source_stock INT NULL,
    supplier_model VARCHAR(120) NULL,
    product_cost DECIMAL(14,2) NULL,
    vendor_stock INT NOT NULL DEFAULT 0,
    option_image_path VARCHAR(255) NULL,
    image_reference_note VARCHAR(255) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_variants_product_id (product_id),
    KEY idx_variants_source_option (source_option_id, source_option_value_id),
    KEY idx_variants_status (status),
    KEY idx_variants_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_supplier_product_costs (
    supplier_product_cost_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_variant_id INT UNSIGNED NULL,
    product_cost DECIMAL(14,2) NOT NULL,
    effective_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    changed_by INT UNSIGNED NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_supplier_cost_supplier (supplier_id),
    KEY idx_supplier_cost_product (product_id),
    KEY idx_supplier_cost_variant (product_variant_id),
    KEY idx_supplier_cost_effective (effective_from),
    KEY idx_supplier_cost_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_product_stock_histories (
    product_stock_history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    product_variant_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NULL,
    old_stock INT NULL,
    new_stock INT NOT NULL,
    change_type VARCHAR(80) NOT NULL,
    reference_type VARCHAR(80) NULL,
    reference_id VARCHAR(120) NULL,
    changed_by INT UNSIGNED NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_stock_history_product (product_id),
    KEY idx_stock_history_variant (product_variant_id),
    KEY idx_stock_history_supplier (supplier_id),
    KEY idx_stock_history_reference (reference_type, reference_id),
    KEY idx_stock_history_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ibs_product_cost_histories (
    product_cost_history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    product_variant_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NULL,
    old_cost DECIMAL(14,2) NULL,
    new_cost DECIMAL(14,2) NOT NULL,
    changed_by INT UNSIGNED NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cost_history_product (product_id),
    KEY idx_cost_history_variant (product_variant_id),
    KEY idx_cost_history_supplier (supplier_id),
    KEY idx_cost_history_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
