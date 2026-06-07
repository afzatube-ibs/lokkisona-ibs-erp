<?php

/**
 * Local Sync/API overrides — copy to config/opencart.local.php on the server (gitignored).
 * Do not commit real API keys to Git.
 *
 * Staging example URL: https://www.staging.lokkisona.com
 * Live example URL:     https://www.lokkisona.com
 */
return [
    'source_mode' => 'staging',
    'enabled' => true,
    'demo_mode' => false,
    'api_base_url' => 'https://www.staging.lokkisona.com',
    'api_key' => 'REPLACE_WITH_OC_API_KEY',
    'product_api_route' => 'REPLACE_WITH_OC_WAREHOUSE_PRODUCT_ROUTE',
    'order_api_route' => 'api/order',
    'read_only_lock' => true,
    'product_sync_enabled' => true,
    'order_sync_enabled' => true,
    'dispatch_bridge_required' => true,
    'max_rows_per_page' => 20,
];
