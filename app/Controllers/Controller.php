<?php

namespace App\Controllers;

class Controller
{
    protected function render($view, $data = [], $layout = 'layouts.admin')
    {
        $data['pageTitle'] = $data['pageTitle'] ?? config('app.name');
        $data['appName'] = config('app.name');
        $data['appVersion'] = config('app.version');
        $data['appReleaseLabel'] = config('app.release_label');
        $data['currentUser'] = \App\Auth::user();
        $data['currentPath'] = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        ob_start();
        view($view, $data);
        $content = ob_get_clean();

        view($layout, array_merge($data, ['content' => $content]));
    }
}
