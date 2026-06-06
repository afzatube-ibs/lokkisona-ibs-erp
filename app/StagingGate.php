<?php

namespace App;

/**
 * Optional HTTP basic auth gate for staging domains (v0.5.0).
 */
class StagingGate
{
    public static function enforce(): void
    {
        if (!config('app.staging_gate.enabled', false)) {
            return;
        }

        $user = (string) config('app.staging_gate.username', '');
        $pass = (string) config('app.staging_gate.password', '');

        if ($user === '' || $pass === '') {
            return;
        }

        $providedUser = $_SERVER['PHP_AUTH_USER'] ?? '';
        $providedPass = $_SERVER['PHP_AUTH_PW'] ?? '';

        if ($providedUser === $user && $providedPass === $pass) {
            return;
        }

        header('WWW-Authenticate: Basic realm="IBS-LK Staging"');
        http_response_code(401);
        echo 'Staging access requires authentication.';
        exit;
    }
}
