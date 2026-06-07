<?php

/**
 * OpenCart / PIT connection config (v0.5.7).
 * Credentials stay on server — do not commit live API keys to Git.
 * Staging template: config/opencart.staging.example.php (api_base_url = staging.lokkisona.com).
 */
return [
    'enabled' => false,
    'demo_mode' => true,
    'api_base_url' => '',
    'api_key' => '',
    'business_source_id' => 1,
    'default_supplier_id' => 1,
    'max_orders_per_request' => 50,
    // Set to your existing OpenCart product list route (e.g. extension/api/warehouse_product). Empty = pull disabled.
    'product_api_route' => 'demo/warehouse_product',
    'demo_warehouse_products' => [
        [
            'product_id' => '501',
            'name' => 'Demo Warehouse Stroller',
            'model' => 'OC-STROLLER-501',
            'quantity' => 12,
            'from_warehouse' => 1,
        ],
        [
            'product_id' => '502',
            'name' => 'Demo Shop-Only Item',
            'model' => 'OC-SHOP-502',
            'quantity' => 99,
            'from_warehouse' => 0,
        ],
        [
            'product_id' => '503',
            'name' => 'Demo Variable Stroller',
            'model' => 'OC-VAR-503',
            'quantity' => 20,
            'from_warehouse' => 1,
            'type' => 'variable',
            'options' => [
                [
                    'option_id' => '11',
                    'option_value_id' => '101',
                    'option_name' => 'Color',
                    'option_value' => 'Blue',
                    'model' => 'OC-VAR-503-BLU',
                    'quantity' => 8,
                ],
                [
                    'option_id' => '11',
                    'option_value_id' => '102',
                    'option_name' => 'Color',
                    'option_value' => 'Red',
                    'model' => 'OC-VAR-503-RED',
                    'quantity' => 12,
                ],
            ],
        ],
        [
            'product_id' => '504',
            'name' => 'Demo Variable No Options',
            'model' => 'OC-VAR-504',
            'quantity' => 5,
            'from_warehouse' => 1,
            'type' => 'variable',
            'options' => [],
        ],
    ],
    'skip_status_ids' => ['0'],
    'skip_status_names' => ['Missing', 'missing'],
    'demo_orders' => [
        [
            'source_order_id' => '10001',
            'source_order_reference' => 'OC-10001',
            'source_invoice_reference' => 'INV-10001',
            'source_status_id' => '3',
            'source_status' => 'Supplier Processing',
            'customer_name' => 'Demo Customer A',
            'customer_phone' => '01700000001',
            'customer_address' => 'Dhaka, Bangladesh',
            'order_total' => 1500.00,
            'items' => [
                [
                    'product_id' => null,
                    'product_name' => 'Demo Lokkisona Product',
                    'variant_label' => null,
                    'quantity' => 1,
                    'selling_price' => 1500.00,
                    'sku' => 'DEMO-001',
                ],
            ],
        ],
        [
            'source_order_id' => '10002',
            'source_order_reference' => 'OC-10002',
            'source_invoice_reference' => 'INV-10002',
            'source_status_id' => '7',
            'source_status' => 'Returning',
            'customer_name' => 'Demo Customer B',
            'customer_phone' => '01700000002',
            'customer_address' => 'Chittagong, Bangladesh',
            'order_total' => 2200.00,
            'items' => [
                [
                    'product_id' => null,
                    'product_name' => 'Demo Return Candidate',
                    'variant_label' => null,
                    'quantity' => 2,
                    'selling_price' => 1100.00,
                    'sku' => 'DEMO-002',
                ],
            ],
        ],
        [
            'source_order_id' => '10003',
            'source_order_reference' => 'OC-10003',
            'source_invoice_reference' => null,
            'source_status_id' => '0',
            'source_status' => 'Missing',
            'customer_name' => 'Skipped Missing',
            'customer_phone' => '',
            'customer_address' => '',
            'order_total' => 0.00,
            'items' => [],
        ],
        [
            'source_order_id' => '10004',
            'source_order_reference' => 'OC-10004',
            'source_invoice_reference' => 'INV-10004',
            'source_status_id' => '99',
            'source_status' => 'Unmapped Custom Status',
            'customer_name' => 'Blocked Unmapped',
            'customer_phone' => '01700000004',
            'customer_address' => 'Sylhet',
            'order_total' => 500.00,
            'items' => [
                [
                    'product_id' => null,
                    'product_name' => 'Unmapped Status Item',
                    'variant_label' => null,
                    'quantity' => 1,
                    'selling_price' => 500.00,
                    'sku' => 'DEMO-004',
                ],
            ],
        ],
    ],
];
