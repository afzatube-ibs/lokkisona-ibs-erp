<?php

namespace App\Support;

/**
 * Dev-only request timing laps (v1.9.1 Product Control).
 */
class RequestTimer
{
    private float $startedAt;

    /** @var array<string, float> */
    private array $laps = [];

    public function __construct()
    {
        $this->startedAt = microtime(true);
    }

    public function lap(string $label): void
    {
        $this->laps[$label] = round((microtime(true) - $this->startedAt) * 1000, 2);
    }

    public function totalMs(): float
    {
        return round((microtime(true) - $this->startedAt) * 1000, 2);
    }

    /**
     * @return array<string, float>
     */
    public function laps(): array
    {
        $out = $this->laps;
        $out['total'] = $this->totalMs();

        return $out;
    }

    public function isEnabled(): bool
    {
        return config('app.env', 'local') === 'local';
    }

    public function log(string $context): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $parts = [];
        foreach ($this->laps() as $label => $ms) {
            $parts[] = $label . '=' . $ms . 'ms';
        }

        error_log('[IBS timing] ' . $context . ' ' . implode(' ', $parts));
    }
}
