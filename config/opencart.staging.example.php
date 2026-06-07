<?php

/**
 * Staging OpenCart config — copy values into config/opencart.local.php on ERP staging server only.
 * Sync source example: https://www.staging.lokkisona.com
 * Do not commit live API keys to Git.
 */
return [
    'source_mode' => 'staging',
    'enabled' => true,
    'demo_mode' => false,
    'api_base_url' => 'https://www.staging.lokkisona.com',
    'api_key' => 'REPLACE_WITH_STAGING_OC_API_KEY',
    'business_source_id' => 1,
    'default_supplier_id' => 1,
    'read_only_lock' => true,
    'product_sync_enabled' => true,
    'order_sync_enabled' => true,
    'dispatch_bridge_required' => true,
    'max_rows_per_page' => 20,
    'max_orders_per_request' => 20,
    'max_products_per_request' => 20,
    'order_api_route' => 'api/ibs/orders',
    'connection_test_api_route' => 'api/ibs/connection_test',
    // OpenCart IBS read-only connector (v1.8.3 package): api/ibs/products
    'product_api_route' => 'api/ibs/products',
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
