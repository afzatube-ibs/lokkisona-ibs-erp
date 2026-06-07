<?php

return [
    'name' => 'IBS-LK Business Manager',
    'version' => '1.9.1',
    'release_label' => 'Product Control Performance',
    'env' => 'local',
    'staging_gate' => [
        'enabled' => false,
        'username' => '',
        'password' => '',
    ],
    'timezone' => 'UTC',
    'url' => '',
    'session_name' => 'lokkisona_ibs_erp_session',
    'auth' => [
        'username' => 'admin',
        'password' => 'admin',
        'supplier_username' => 'supplier',
        'supplier_password' => 'supplier',
        'supplier_id' => 1,
    ],
];
