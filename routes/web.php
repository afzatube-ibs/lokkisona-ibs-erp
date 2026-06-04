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
$router->get('/users', 'UsersController@index');
$router->get('/suppliers', 'SuppliersController@index');
$router->get('/business-sources', 'BusinessSourcesController@index');
$router->get('/product-control', 'ProductControlController@index');

$router->setNotFound(function () {
    http_response_code(404);
    view('errors.404', ['pageTitle' => 'Page Not Found']);
});
