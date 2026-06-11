<?php

namespace ibs;

require_once DIR_SYSTEM . 'library/ibs/connector_version.php';
require_once DIR_SYSTEM . 'library/ibs/api_settings.php';

/**
 * IBS Sync Connector — read-only API authentication.
 */
class api_auth
{
    private $request;
    private $settings;

    public function __construct($registry)
    {
        $this->request = $registry->get('request');
        $loader = new api_settings($registry);
        $this->settings = $loader->all();
    }

    public function settings(): array
    {
        return $this->settings;
    }

    public function authenticate(): ?string
    {
        $expected = trim((string) ($this->settings['api_token'] ?? ''));
        if ($expected === '') {
            return 'API token is not configured. Enable IBS Sync Connector in Extensions and save an API token.';
        }

        $provided = trim((string) ($this->request->get['api_token'] ?? ''));
        if ($provided === '') {
            return 'Missing api_token query parameter.';
        }

        if (!hash_equals($expected, $provided)) {
            return 'Invalid api_token.';
        }

        $allowedIps = $this->settings['allowed_ips'] ?? [];
        if (is_array($allowedIps) && $allowedIps !== []) {
            $clientIp = trim((string) ($this->request->server['REMOTE_ADDR'] ?? ''));
            if ($clientIp === '' || !in_array($clientIp, $allowedIps, true)) {
                return 'Client IP is not allowed for IBS read API.';
            }
        }

        return null;
    }

    public function page(): int
    {
        return max(1, (int) ($this->request->get['page'] ?? 1));
    }

    public function maxLimit(): int
    {
        $cap = (int) ($this->settings['max_limit'] ?? 20);

        return max(1, min($cap, 20));
    }

    public function limit(): int
    {
        $cap = $this->maxLimit();
        $requested = (int) ($this->request->get['limit'] ?? $cap);

        return max(1, min($requested, $cap));
    }

    public function bridgeTable(): string
    {
        $table = trim((string) ($this->settings['bridge_table'] ?? 'dispatch_location_product'));

        return $table !== '' ? $table : 'dispatch_location_product';
    }
}
