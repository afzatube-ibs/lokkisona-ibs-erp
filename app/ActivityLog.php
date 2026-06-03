<?php

namespace App;

class ActivityLog
{
    const LOG_FILE = 'activity.log';

    public static function record($action, $message, $context = [])
    {
        $dir = IBS_STORAGE . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $entry = [
            'time' => date('Y-m-d H:i:s'),
            'action' => $action,
            'message' => $message,
            'user' => $context['user'] ?? (Auth::user() ?: 'guest'),
            'role' => $context['role'] ?? Auth::role(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
        ];

        $line = json_encode(array_merge($entry, ['context' => $context]));
        if ($line === false) {
            return;
        }

        @file_put_contents($dir . '/' . self::LOG_FILE, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function recent($limit = 100)
    {
        $path = IBS_STORAGE . '/logs/' . self::LOG_FILE;
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $lines = array_slice(array_reverse($lines), 0, $limit);
        $entries = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }
}
