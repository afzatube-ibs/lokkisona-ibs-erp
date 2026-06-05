<?php

namespace App\ReadFoundation;

/**
 * Documents owner-approved write paths for checkpoint whitelist (v0.2.9 planning).
 * No writes exist until v0.3.1+; this class performs no filesystem or database actions.
 */
class WritePathWhitelist
{
    public static function allowedDirectories(): array
    {
        return [
            'app/Services/Write',
            'app/Repositories/Write',
        ];
    }

    public static function isWhitelistedPath(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);

        foreach (self::allowedDirectories() as $directory) {
            if (str_starts_with($normalized, $directory . '/') || $normalized === $directory) {
                return true;
            }
        }

        return false;
    }

    public static function rules(): array
    {
        return [
            'Mutation SQL is allowed only inside app/Services/Write/ and app/Repositories/Write/ PHP files (checkpoint whitelist from v0.3.1).',
            'All other runtime PHP under app/, public/, resources/, and routes/ must remain mutation-free until explicitly whitelisted.',
            'Models stay metadata-only; writes never belong in app/Models/.',
            'Page-load DDL and migration auto-apply remain forbidden everywhere.',
            'Each write service version must document which tables it may mutate.',
        ];
    }
}
