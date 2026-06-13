<?php

namespace App\Support;

use App\Auth;

/**
 * Ensures one OpenCart sync request at a time (v2.4.8 SFM).
 */
class SyncRequestGuard
{
    private const SESSION_KEY = 'ibs_sync_request_lock';

    private const TTL_SECONDS = 60;

    public static function acquire(): bool
    {
        Auth::startSession();
        $lock = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_array($lock)) {
            $started = (int) ($lock['started_at'] ?? 0);
            if ($started > 0 && (time() - $started) < self::TTL_SECONDS) {
                return false;
            }
        }

        $_SESSION[self::SESSION_KEY] = [
            'started_at' => time(),
            'path' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
        ];

        return true;
    }

    public static function release(): void
    {
        Auth::startSession();
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function busyMessage(): string
    {
        return 'Sync already in progress. Wait for the current request to finish, then try again.';
    }
}
