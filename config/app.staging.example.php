<?php

/**
 * Staging app config — merge into config/app.php on ERP staging server only.
 * Do not commit staging passwords to Git.
 */
return [
    'env' => 'staging',
    'staging_gate' => [
        'enabled' => true,
        'username' => 'REPLACE_STAGING_GATE_USER',
        'password' => 'REPLACE_STAGING_GATE_PASSWORD',
    ],
    'auth' => [
        'username' => 'REPLACE_OWNER_USER',
        'password' => 'REPLACE_OWNER_PASSWORD',
        'supplier_username' => 'REPLACE_SUPPLIER_USER',
        'supplier_password' => 'REPLACE_SUPPLIER_PASSWORD',
        'supplier_id' => 1,
    ],
];
