<?php

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\Router;

$tz = config('app.timezone', 'UTC');
if ($tz) {
    date_default_timezone_set($tz);
}

Auth::startSession();

\App\StagingGate::enforce();

$router = new Router();
require IBS_ROOT . '/routes/web.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);
