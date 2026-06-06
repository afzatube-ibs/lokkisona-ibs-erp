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
    'max_orders_per_request' => 50,
    // Set to your OpenCart warehouse product route. Empty = pull button disabled.
    'product_api_route' => 'REPLACE_WITH_OC_WAREHOUSE_PRODUCT_ROUTE',
    'skip_status_ids' => ['0'],
    'skip_status_names' => ['Missing', 'missing'],
];
