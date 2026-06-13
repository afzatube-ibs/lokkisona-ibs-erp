<?php

return [
    'name' => 'IBS-LK Business Manager',
    'version' => '2.5.3',
    'release_label' => 'Sync Settings Release Polish',
    'env' => 'local',
    'staging_gate' => [
        'enabled' => false,
        'username' => '',
        'password' => '',
    ],
    'timezone' => 'UTC',
    'url' => 'http://127.0.0.1:8017',
    'session_name' => 'lokkisona_ibs_erp_session',
    'auth' => [
        'username' => 'admin',
        'password' => 'admin',
        'supplier_username' => 'supplier',
        'supplier_password' => 'supplier',
        'supplier_id' => 1,
    ],
];
