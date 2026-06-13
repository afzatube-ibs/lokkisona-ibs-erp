-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v2.4.9 Supplier Order Queue mapping type on ibs_status_mappings

ALTER TABLE ibs_status_mappings
    ADD COLUMN mapping_type VARCHAR(32) NOT NULL DEFAULT 'legacy_opencart_status' AFTER source_status;

UPDATE ibs_status_mappings
SET mapping_type = 'legacy_opencart_status'
WHERE mapping_type = '' OR mapping_type IS NULL;

ALTER TABLE ibs_status_mappings
    ADD KEY idx_status_mapping_type (mapping_type, business_source_id, is_active);
