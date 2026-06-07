<?php

/**
 * IBS read-only API config — copy to OpenCart system/config/ibs_api.php (server only).
 * Do not commit real tokens to Git.
 */
return [
    'api_token' => 'REPLACE_WITH_LONG_RANDOM_SECRET',
    'max_limit' => 20,
    'allowed_ips' => [],
    'bridge_table' => 'dispatch_location_product',
    'order_field_map' => [
        'courier_status' => ['courier_status', 'shipping_status'],
        'consignment_id' => ['consignment_id', 'tracking_number', 'tracking_no'],
        'courier_name' => ['courier_name', 'shipping_method'],
    ],
];
