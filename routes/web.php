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
$router->get('/activity-log', 'ActivityLogController@index');
$router->get('/roles-permissions', 'RolesPermissionsController@index');
$router->get('/database-safety', 'DatabaseSafetyController@index');
$router->get('/migration-runner', 'MigrationRunnerController@index');
$router->get('/migration-files', 'MigrationFilesController@index');
$router->get('/migration-dry-run', 'MigrationDryRunController@index');
$router->get('/migration-approval', 'MigrationApprovalController@index');
$router->get('/migration-execution-lock', 'MigrationExecutionLockController@index');
$router->get('/supplier-opening-balances', 'SupplierOpeningBalancesController@index');
$router->get('/build-queue', 'BuildQueueController@index');
$router->get('/users', 'UsersController@index');
$router->get('/suppliers', 'SuppliersController@index');
$router->get('/business-sources', 'BusinessSourcesController@index');
$router->get('/product-control', 'ProductControlController@index');
$router->get('/order-workflow', 'OrderWorkflowController@index');
$router->get('/dispatch-reports', 'DispatchReportsController@index');
$router->get('/supplier-payables', 'SupplierPayablesController@index');
$router->get('/return-receive', 'ReturnReceiveController@index');
$router->get('/status-mapping', 'StatusMappingController@index');
$router->get('/sync-preview', 'SyncPreviewController@index');
$router->get('/invoice-printing', 'InvoicePrintingController@index');
$router->get('/supplier-tools', 'SupplierToolsController@index');
$router->get('/manual-orders', 'ManualOrdersController@index');

$router->setNotFound(function () {
    http_response_code(404);
    view('errors.404', ['pageTitle' => 'Page Not Found']);
});
