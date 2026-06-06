<?php

define('IBS_ROOT', dirname(__DIR__));
define('IBS_APP', IBS_ROOT . '/app');
define('IBS_CONFIG', IBS_ROOT . '/config');
define('IBS_RESOURCES', IBS_ROOT . '/resources');
define('IBS_STORAGE', IBS_ROOT . '/storage');
define('IBS_PUBLIC', IBS_ROOT . '/public');

date_default_timezone_set('UTC'); // overridden after config load in index if needed

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = IBS_APP . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

function config($key, $default = null)
{
    static $configs = [];

    $parts = explode('.', $key);
    $file = $parts[0];

    if (!isset($configs[$file])) {
        $path = IBS_CONFIG . '/' . $file . '.php';
        $configs[$file] = file_exists($path) ? require $path : [];
    }

    $value = $configs[$file];
    $count = count($parts);

    for ($i = 1; $i < $count; $i++) {
        if (!is_array($value) || !array_key_exists($parts[$i], $value)) {
            return $default;
        }
        $value = $value[$parts[$i]];
    }

    return $value;
}

function view($name, $data = [])
{
    extract($data, EXTR_SKIP);
    $path = IBS_RESOURCES . '/views/' . str_replace('.', '/', $name) . '.php';

    if (!file_exists($path)) {
        http_response_code(500);
        echo 'View not found: ' . htmlspecialchars($name);
        return;
    }

    require $path;
}

function redirect($path)
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }
    header('Location: ' . $base . $path);
    exit;
}

function url($path = '')
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }
    return $base . $path;
}

function asset($path)
{
    $version = (string) config('app.version', '1');
    $query = $version !== '' ? '?v=' . rawurlencode($version) : '';

    return url('/assets/' . ltrim($path, '/') . $query);
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_log($message, $level = 'info')
{
    $dir = IBS_STORAGE . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
    @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}
