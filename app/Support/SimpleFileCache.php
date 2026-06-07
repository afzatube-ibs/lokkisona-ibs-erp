<?php

namespace App\Support;

/**
 * Lightweight file cache for read-only list summaries (v1.9.1 Product Control).
 */
class SimpleFileCache
{
    private string $directory;

    public function __construct(string $subdir = 'product-control')
    {
        $this->directory = dirname(__DIR__, 2) . '/storage/cache/' . trim($subdir, '/');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key, int $ttlSeconds): ?array
    {
        $path = $this->pathForKey($key);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            @unlink($path);

            return null;
        }

        $cachedAt = (int) ($decoded['_cached_at'] ?? 0);
        if ($cachedAt <= 0 || (time() - $cachedAt) > max(1, $ttlSeconds)) {
            @unlink($path);

            return null;
        }

        $payload = $decoded['payload'] ?? null;

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function set(string $key, array $payload): void
    {
        $this->ensureDirectory();
        $path = $this->pathForKey($key);
        $data = json_encode([
            '_cached_at' => time(),
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE);

        if ($data !== false) {
            @file_put_contents($path, $data, LOCK_EX);
        }
    }

    public function forget(string $key): void
    {
        $path = $this->pathForKey($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function flush(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        foreach (glob($this->directory . '/*.json') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
    }

    private function pathForKey(string $key): string
    {
        return $this->directory . '/' . hash('sha256', $key) . '.json';
    }
}
