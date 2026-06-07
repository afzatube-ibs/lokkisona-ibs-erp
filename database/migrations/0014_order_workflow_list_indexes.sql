-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v1.9.2 Vendor Fulfillment list performance + optional source order status column.

ALTER TABLE ibs_orders
    ADD COLUMN source_order_status VARCHAR(160) NULL AFTER courier_status;

ALTER TABLE ibs_orders
    ADD KEY idx_orders_source_order_status (source_order_status);

ALTER TABLE ibs_orders
    ADD KEY idx_orders_ordered_at (ordered_at);

ALTER TABLE ibs_order_items
    ADD KEY idx_order_items_order_product (order_id, product_id);
