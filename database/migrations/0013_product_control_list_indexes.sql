-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v1.9.1 Product Control list query indexes

ALTER TABLE ibs_products
    ADD KEY idx_products_supplier_model (supplier_model),
    ADD KEY idx_products_source_model (source_model),
    ADD KEY idx_products_last_synced_at (last_synced_at);

ALTER TABLE ibs_product_variants
    ADD KEY idx_variants_supplier_model (supplier_model);
