-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v1.4.0: ERP-internal supplier product category for reporting (not OpenCart category).

ALTER TABLE ibs_products
    ADD COLUMN supplier_product_category VARCHAR(120) NULL AFTER supplier_model,
    ADD KEY idx_products_supplier_category (supplier_id, supplier_product_category);
