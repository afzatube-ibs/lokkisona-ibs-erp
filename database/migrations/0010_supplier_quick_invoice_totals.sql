-- DRAFT ONLY
-- DO NOT AUTO RUN
-- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
-- BACKUP DATABASE FIRST
-- NOT EXECUTED BY APPLICATION PAGE LOAD
-- v0.6.1 supplier quick invoice totals and customer fields addendum.
-- Requires 0007_invoices_printing_supplier_tools.sql applied first.

ALTER TABLE ibs_supplier_quick_invoices
    ADD COLUMN customer_name VARCHAR(190) NULL AFTER supplier_name,
    ADD COLUMN customer_phone VARCHAR(80) NULL AFTER customer_name,
    ADD COLUMN customer_address TEXT NULL AFTER customer_phone,
    ADD COLUMN subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER invoice_total,
    ADD COLUMN discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER subtotal,
    ADD COLUMN advance_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER discount_amount,
    ADD COLUMN balance_due DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER advance_amount,
    ADD COLUMN notes TEXT NULL AFTER balance_due,
    ADD COLUMN supplier_access_closed_at DATETIME NULL AFTER downloaded_at;
