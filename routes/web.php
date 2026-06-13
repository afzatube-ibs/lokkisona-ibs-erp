<?php

use App\Router;

/** @var Router $router */

$router->get('/', function () {
    if (\App\Auth::check()) {
        redirect('/dashboard');
    }
    redirect('/login');
});

$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

$router->get('/dashboard', 'DashboardController@index');
$router->get('/health', 'HealthController@index');
$router->get('/version', 'VersionController@index');
$router->get('/sync-api-settings', 'SyncApiSettingsController@index');
$router->post('/sync-api-settings/save', 'SyncApiSettingsController@save');
$router->post('/sync-api-settings/test-connection', 'SyncApiSettingsController@testConnection');
$router->post('/sync-api-settings/load-queue-statuses', 'SyncApiSettingsController@loadQueueStatuses');
$router->post('/sync-api-settings/save-queue-mappings', 'SyncApiSettingsController@saveQueueMappings');
$router->post('/sync-api-settings/reset-demo', 'SyncApiSettingsController@resetDemo');
$router->get('/activity-log', 'ActivityLogController@index');
$router->get('/roles-permissions', 'RolesPermissionsController@index');
$router->get('/database-safety', 'DatabaseSafetyController@index');
$router->get('/dev-db-activation', 'DevDatabaseActivationController@index');
$router->get('/migration-runner', 'MigrationRunnerController@index');
$router->get('/migration-files', 'MigrationFilesController@index');
$router->get('/migration-dry-run', 'MigrationDryRunController@index');
$router->get('/migration-approval', 'MigrationApprovalController@index');
$router->get('/migration-execution-lock', 'MigrationExecutionLockController@index');
$router->get('/supplier-opening-balances', 'SupplierOpeningBalancesController@index');
$router->post('/supplier-opening-balances/create', 'SupplierOpeningBalancesController@create');
$router->post('/supplier-opening-balances/approve', 'SupplierOpeningBalancesController@approve');
$router->post('/supplier-opening-balances/launch-lock', 'SupplierOpeningBalancesController@launchLock');
$router->get('/build-queue', 'BuildQueueController@index');
$router->get('/users', 'UsersController@index');
$router->get('/suppliers', 'SuppliersController@index');
$router->post('/suppliers/create', 'SuppliersController@create');
$router->post('/suppliers/edit', 'SuppliersController@edit');
$router->get('/business-sources', 'BusinessSourcesController@index');
$router->post('/business-sources/create', 'BusinessSourcesController@create');
$router->post('/business-sources/edit', 'BusinessSourcesController@edit');
$router->get('/product-control', 'ProductControlController@index');
$router->get('/product-control/workspace', 'ProductControlController@workspaceJson');
$router->get('/product-control/history', 'ProductControlController@historyJson');
$router->post('/product-control/product/create', 'ProductControlController@createProduct');
$router->post('/product-control/product/edit', 'ProductControlController@editProduct');
$router->post('/product-control/workspace/save', 'ProductControlController@saveWorkspace');
$router->post('/product-control/refresh-products', 'ProductControlController@refreshProducts');
$router->post('/product-control/variant/create', 'ProductControlController@createVariant');
$router->post('/product-control/cost-stock', 'ProductControlController@updateCostStock');
$router->get('/order-workflow', 'OrderWorkflowController@index');
$router->get('/order-workflow/history', 'OrderWorkflowController@historyJson');
$router->get('/order-workflow/selection-preview', 'OrderWorkflowController@selectionPreview');
$router->post('/order-workflow/action', 'OrderWorkflowController@action');
$router->post('/order-workflow/bulk-action', 'OrderWorkflowController@bulkAction');
$router->post('/order-workflow/note', 'OrderWorkflowController@note');
$router->post('/order-workflow/create', 'OrderWorkflowController@create');
$router->get('/dispatch-reports', 'DispatchReportsController@index');
$router->get('/dispatch-reports/view', 'DispatchReportsController@view');
$router->get('/dispatch-report/{batch}/print', 'DispatchReportsController@printStatement');
$router->get('/dispatch-report/{batch}', 'DispatchReportsController@show');
$router->post('/dispatch-reports/create', 'DispatchReportsController@create');
$router->get('/supplier-payables', 'SupplierPayablesController@index');
$router->post('/supplier-payables/create', 'SupplierPayablesController@createManual');
$router->post('/supplier-payables/approve', 'SupplierPayablesController@approve');
$router->post('/supplier-payables/reject', 'SupplierPayablesController@reject');
$router->post('/supplier-payables/post-dispatch', 'SupplierPayablesController@postFromDispatch');
$router->post('/supplier-payables/post-return-batch', 'SupplierPayablesController@postFromReturnBatch');
$router->get('/reports', 'ReportsController@index');
$router->get('/return-receive', 'ReturnReceiveController@index');
$router->post('/return-receive/confirm', 'ReturnReceiveController@confirm');
$router->post('/return-receive/create-batch', 'ReturnReceiveController@createBatch');
$router->post('/return-receive/approve-batch', 'ReturnReceiveController@approveBatch');
$router->get('/return-reports', 'ReturnReportsController@index');
$router->post('/return-reports/create', 'ReturnReportsController@create');
$router->get('/return-report/{batch}/print', 'ReturnReportsController@printStatement');
$router->get('/return-report/{batch}', 'ReturnReportsController@show');
$router->post('/supplier-tools/quick-invoice', 'SupplierToolsController@quickInvoice');
$router->get('/supplier-tools/quick-invoice/print/{id}', 'SupplierToolsController@printQuickInvoice');
$router->post('/supplier-tools/quick-invoice/download', 'SupplierToolsController@logDownload');
$router->get('/status-mapping', 'StatusMappingController@index');
$router->post('/status-mapping/create', 'StatusMappingController@create');
$router->post('/status-mapping/toggle', 'StatusMappingController@toggle');
$router->post('/status-mapping/seed-defaults', 'StatusMappingController@seedDefaults');
$router->get('/sync-preview', 'SyncPreviewController@index');
$router->post('/sync-preview/preview-products', 'SyncPreviewController@previewProducts');
$router->post('/sync-preview/import-products', 'SyncPreviewController@importProducts');
$router->post('/sync-preview/reset-product-sync', 'SyncPreviewController@resetProductSync');
$router->post('/sync-preview/run-test-sync', 'SyncPreviewController@runTestSync');
$router->post('/sync-preview/pull-warehouse-products', 'SyncPreviewController@pullWarehouseProducts');
$router->post('/sync-preview/import', 'SyncPreviewController@import');
$router->get('/invoice-printing', 'InvoicePrintingController@index');
$router->post('/invoice-printing/generate', 'InvoicePrintingController@generate');
$router->post('/invoice-printing/log-print', 'InvoicePrintingController@logPrint');
$router->get('/settlements', 'SettlementsController@index');
$router->post('/settlements/prepare', 'SettlementsController@prepare');
$router->post('/settlements/approve', 'SettlementsController@approve');
$router->post('/settlements/mark-paid', 'SettlementsController@markPaid');
$router->post('/settlements/close', 'SettlementsController@close');
$router->get('/supplier-tools', 'SupplierToolsController@index');
$router->get('/manual-orders', 'ManualOrdersController@index');
$router->post('/manual-orders/create', 'ManualOrdersController@create');

$router->setNotFound(function () {
    http_response_code(404);
    view('errors.404', ['pageTitle' => 'Page Not Found']);
});
