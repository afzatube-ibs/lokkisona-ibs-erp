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

$router->setNotFound(function () {
    http_response_code(404);
    view('errors.404', ['pageTitle' => 'Page Not Found']);
});
