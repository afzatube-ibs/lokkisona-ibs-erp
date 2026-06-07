-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v1.7.0: supplier note on products/variants + option sync state for variable products without options.

ALTER TABLE ibs_products
    ADD COLUMN supplier_note TEXT NULL AFTER low_warning_threshold,
    ADD COLUMN sync_options_state VARCHAR(20) NULL DEFAULT NULL COMMENT 'has_options|missing_options|simple' AFTER last_synced_at;

ALTER TABLE ibs_product_variants
    ADD COLUMN supplier_note TEXT NULL AFTER vendor_stock;
