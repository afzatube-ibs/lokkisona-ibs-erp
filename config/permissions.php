<?php

return [
    'default_role' => 'owner',
    'roles' => [
        'owner' => [
            'label' => 'Owner',
            'description' => 'Full business owner access across the standalone ERP foundation.',
        ],
        'admin' => [
            'label' => 'Admin',
            'description' => 'Operational administrator access for system and workflow management.',
        ],
        'staff' => [
            'label' => 'Staff',
            'description' => 'Day-to-day operational access with limited management permissions.',
        ],
        'supplier' => [
            'label' => 'Supplier',
            'description' => 'Future supplier-facing access for order and return visibility.',
        ],
    ],
    'groups' => [
        'Core System' => [
            'dashboard.view',
            'health.view',
            'version.view',
            'activity_log.view',
            'roles_permissions.view',
            'database_safety.view',
            'users.view',
            'users.manage',
            'suppliers.view',
            'suppliers.manage',
            'business_sources.view',
            'business_sources.manage',
            'settings.view',
            'settings.manage',
        ],
        'User Management' => [
            'users.view',
            'users.manage',
        ],
        'Supplier Management' => [
            'suppliers.view',
            'suppliers.manage',
        ],
        'Business Sources' => [
            'business_sources.view',
            'business_sources.manage',
        ],
        'Orders' => [
            'orders.view',
            'orders.manage',
        ],
        'Order Workflow' => [
            'order_workflow.view',
            'order_workflow.manage',
        ],
        'Product Control' => [
            'product_control.view',
            'product_control.manage',
        ],
        'Dispatch' => [
            'dispatch.view',
            'dispatch.manage',
        ],
        'Dispatch Reports' => [
            'dispatch_reports.view',
            'dispatch_reports.manage',
        ],
        'Returns' => [
            'returns.view',
            'returns.manage',
        ],
        'Payable' => [
            'payable.view',
            'payable.manage',
        ],
    ],
    'role_permissions' => [
        'owner' => ['*'],
        'admin' => [
            'dashboard.view',
            'health.view',
            'version.view',
            'activity_log.view',
            'roles_permissions.view',
            'database_safety.view',
            'users.view',
            'users.manage',
            'suppliers.view',
            'suppliers.manage',
            'business_sources.view',
            'business_sources.manage',
            'orders.view',
            'orders.manage',
            'order_workflow.view',
            'order_workflow.manage',
            'product_control.view',
            'product_control.manage',
            'dispatch.view',
            'dispatch.manage',
            'dispatch_reports.view',
            'dispatch_reports.manage',
            'returns.view',
            'returns.manage',
            'payable.view',
            'payable.manage',
            'settings.view',
            'settings.manage',
        ],
        'staff' => [
            'dashboard.view',
            'orders.view',
            'order_workflow.view',
            'product_control.view',
            'dispatch.view',
            'returns.view',
        ],
        'supplier' => [
            'dashboard.view',
            'orders.view',
            'returns.view',
        ],
    ],
];
