-- IBS-LK Business Manager planned database foundation.
-- Documentation only: do not execute as an install script.
-- Future real migrations must be reviewed, backed up, and applied manually.
-- No page-load CREATE/ALTER/schema repair is allowed.

-- Planned identity and access tables:
-- users
-- roles
-- user_roles
-- activity_logs

-- Planned multi-business and channel tables:
-- businesses
-- sales_channels
-- settings

-- Planned supplier and product tables:
-- suppliers
-- products
-- product_variants
-- supplier_product_costs
-- product_stock_histories
-- product_cost_histories

-- Planned order workflow tables:
-- orders
-- order_items
-- order_status_mappings
-- status_mappings
-- courier_status_mappings
-- sync_previews
-- sync_logs

-- Planned dispatch and return workflow tables:
-- dispatch_reports
-- dispatch_report_items
-- supplier_returns
-- owner_returns

-- Planned payable workflow tables:
-- payable_ledgers
-- supplier_invoices
-- supplier_payments
