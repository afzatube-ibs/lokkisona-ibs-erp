<?php

/**
 * Staging OpenCart config — copy to config/opencart.php on ERP staging server only.
 * Sync source: https://staging.lokkisona.com
 * Do not commit live API keys to Git.
 */
return [
    'enabled' => true,
    'demo_mode' => false,
    'api_base_url' => 'https://staging.lokkisona.com',
    'api_key' => 'REPLACE_WITH_STAGING_OC_API_KEY',
    'business_source_id' => 1,
    'default_supplier_id' => 1,
    'max_rows_per_page' => 20,
    'max_orders_per_request' => 20,
    'max_products_per_request' => 20,
    'order_api_route' => 'api/order',
    // Must join oc_dispatch_location_product (from_warehouse=1) on the OpenCart side.
    'product_api_route' => 'REPLACE_WITH_OC_WAREHOUSE_PRODUCT_ROUTE',
    'api_page_param' => 'page',
    'api_limit_param' => 'limit',
    'dispatch_location_bridge_table' => 'dispatch_location_product',
    'skip_status_ids' => ['0'],
    'skip_status_names' => ['Missing', 'missing'],
    'supplier_handled_workflow_groups' => ['workflow', 'supplier_handled'],
    'courier_only_workflow_groups' => ['courier', 'courier_reference'],
    'courier_stage_ibs_statuses' => ['out_for_delivery', 'delivered'],
    'db_readonly_allowed' => false,
    'db' => [
        'host' => '',
        'database' => '',
        'username' => '',
        'password' => '',
        'prefix' => 'oc_',
    ],
];
