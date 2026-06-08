-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v1.9.6 Order Sync Mapping Safety Foundation

ALTER TABLE ibs_orders
    ADD COLUMN origin_order_status_id VARCHAR(40) NULL AFTER source_order_reference,
    ADD COLUMN origin_order_status_name VARCHAR(120) NULL AFTER origin_order_status_id,
    ADD COLUMN sync_source VARCHAR(40) NULL DEFAULT NULL AFTER origin_order_status_name,
    ADD COLUMN imported_at DATETIME NULL AFTER sync_source,
    ADD COLUMN last_synced_at DATETIME NULL AFTER imported_at;

ALTER TABLE ibs_orders
    ADD UNIQUE KEY uq_orders_source_order (business_source_id, source_order_id);

ALTER TABLE ibs_status_mappings
    ADD COLUMN notes TEXT NULL AFTER courier_status,
    ADD COLUMN last_matched_count INT NOT NULL DEFAULT 0 AFTER notes,
    ADD COLUMN last_synced_at DATETIME NULL AFTER last_matched_count;

ALTER TABLE ibs_order_items
    ADD COLUMN source_line_key VARCHAR(190) NULL AFTER source_product_id;

ALTER TABLE ibs_order_items
    ADD UNIQUE KEY uq_order_items_line_key (order_id, source_line_key);
