<?php

namespace App\Services\ReadOnly;

use App\ReadFoundation\WriteGate;
use App\Services\Read\OpenCartReadClient;

/**
 * Sync/API settings read facade (v1.8.2) — config file based, no DB table.
 */
class SyncApiSettingsReadService
{
    public const STAGING_URL = 'https://www.staging.lokkisona.com';
    public const LIVE_URL = 'https://www.lokkisona.com';

    public function formState(): array
    {
        $sourceMode = $this->resolvedSourceMode();
        $apiKey = trim((string) config('opencart.api_key', ''));
        $writable = $this->isLocalConfigWritable();

        return [
            'source_mode' => $sourceMode,
            'api_base_url' => trim((string) config('opencart.api_base_url', '')),
            'api_key_set' => $apiKey !== '',
            'api_key_status' => $apiKey !== '' ? 'Configured' : 'Not configured',
            'product_api_route' => trim((string) config('opencart.product_api_route', '')),
            'order_api_route' => trim((string) config('opencart.order_api_route', '')),
            'read_only_lock' => true,
            'max_rows_per_page' => 20,
            'product_sync_enabled' => (bool) config('opencart.product_sync_enabled', true),
            'order_sync_enabled' => (bool) config('opencart.order_sync_enabled', true),
            'dispatch_bridge_required' => (bool) config('opencart.dispatch_bridge_required', true),
            'local_file_exists' => file_exists($this->localConfigPath()),
            'local_file_writable' => $writable,
            'local_file_path' => 'config/opencart.local.php',
            'example_file_path' => 'config/opencart.local.example.php',
            'default_urls' => [
                'staging' => self::STAGING_URL,
                'live' => self::LIVE_URL,
            ],
            'manual_setup' => $this->manualSetupInstructions(),
            'storage_mode' => 'config_file',
            'future_plan' => $this->futurePlan(),
            'warnings' => $this->configurationWarnings($sourceMode, $writable),
        ];
    }

    public function connectionSummary(): array
    {
        $client = new OpenCartReadClient();
        $test = $client->testConnection();
        $status = $client->productSyncStatus();
        $mode = $this->resolvedSourceMode();
        $connectionOk = (bool) ($test['ok'] ?? false);
        $apiKey = trim((string) config('opencart.api_key', ''));
        $localExists = file_exists($this->localConfigPath());
        $exampleExists = file_exists($this->exampleConfigPath());

        return [
            'source_mode' => $mode,
            'connection_ok' => $connectionOk,
            'connection_message' => (string) ($test['message'] ?? ''),
            'bridge_available' => $test['bridge_available'] ?? null,
            'product_route' => trim((string) config('opencart.product_api_route', '')),
            'order_api_route' => trim((string) config('opencart.order_api_route', '')),
            'api_base_url' => trim((string) config('opencart.api_base_url', '')),
            'read_only_lock' => true,
            'product_sync_enabled' => (bool) config('opencart.product_sync_enabled', true),
            'order_sync_enabled' => (bool) config('opencart.order_sync_enabled', true),
            'dispatch_bridge_required' => (bool) config('opencart.dispatch_bridge_required', true),
            'api_key_status' => $apiKey !== '' ? 'Configured' : 'Not configured',
            'header_badge' => $this->headerBadge($mode, $connectionOk),
            'storage' => $this->storageBadge($localExists, $exampleExists),
        ];
    }

    /**
     * @return array{label:string,class:string}
     */
    private function headerBadge(string $mode, bool $connectionOk): array
    {
        if ($mode === 'demo') {
            return ['label' => 'Demo Mode', 'class' => 'badge-info'];
        }

        if (!$connectionOk) {
            return ['label' => 'Error', 'class' => 'badge-warn'];
        }

        if ($mode === 'live') {
            return ['label' => 'Live Read-Only', 'class' => 'badge-ok'];
        }

        return ['label' => 'Staging Read-Only', 'class' => 'badge-ok'];
    }

    /**
     * @return array{label:string,class:string}
     */
    private function storageBadge(bool $localExists, bool $exampleExists): array
    {
        if (!$exampleExists) {
            return ['label' => 'Template missing', 'class' => 'badge-warn'];
        }

        if ($localExists) {
            return ['label' => 'Local config file', 'class' => 'badge-ok'];
        }

        return ['label' => 'Local config file', 'class' => 'badge-muted'];
    }

    private function exampleConfigPath(): string
    {
        if (defined('IBS_CONFIG')) {
            return IBS_CONFIG . '/opencart.local.example.php';
        }

        return dirname(__DIR__, 3) . '/config/opencart.local.example.php';
    }

    public function resolvedSourceMode(): string
    {
        $mode = strtolower(trim((string) config('opencart.source_mode', '')));
        if (in_array($mode, ['demo', 'staging', 'live'], true)) {
            return $mode;
        }

        if ((bool) config('opencart.demo_mode', false)) {
            return 'demo';
        }

        if ((bool) config('opencart.enabled', false)) {
            return 'staging';
        }

        return 'demo';
    }

    /**
     * @return array<int, string>
     */
    private function configurationWarnings(string $sourceMode, bool $writable): array
    {
        $warnings = [];
        if (!$writable) {
            $warnings[] = 'config/opencart.local.php is not writable from the web UI. Use the manual setup steps below — do not commit API keys to Git.';
        }

        $url = trim((string) config('opencart.api_base_url', ''));
        $productRoute = trim((string) config('opencart.product_api_route', ''));
        $orderRoute = trim((string) config('opencart.order_api_route', ''));
        $apiKey = trim((string) config('opencart.api_key', ''));

        if ($sourceMode !== 'demo') {
            if ($url === '') {
                $warnings[] = 'Source URL is missing. Set staging or live URL before testing connection.';
            }
            if ($apiKey === '') {
                $warnings[] = 'API key/token is not saved yet. Enter it once — status will show Configured only (key never displayed).';
            }
        }

        if ((bool) config('opencart.product_sync_enabled', true) && $productRoute === '') {
            $warnings[] = 'Product API route is missing. Product sync preview cannot load warehouse products.';
        }

        if ((bool) config('opencart.order_sync_enabled', true) && $orderRoute === '') {
            $warnings[] = 'Order API route is missing. Order sync preview cannot load supplier orders.';
        }

        if (!(bool) config('opencart.read_only_lock', true)) {
            $warnings[] = 'Read-only lock should remain enabled. OpenCart writes are not supported in this ERP build.';
        }

        $gate = WriteGate::productSyncImport();
        if ((bool) config('opencart.product_sync_enabled', true) && !($gate['ready'] ?? false)) {
            $warnings[] = 'ERP product sync tables are not ready: ' . implode(', ', $gate['missing_tables'] ?? []);
        }

        return $warnings;
    }

    private function futurePlan(): array
    {
        return [
            'title' => 'Future: database-backed System Settings',
            'summary' => 'v1.8.2 stores Sync/API settings in config/opencart.local.php on the server. A future owner-approved migration may add ibs_system_settings for UI edits without file writes.',
            'points' => [
                'No CREATE/ALTER on page load in this build',
                'API key remains server-only and never re-displayed after save',
                'Staging → live switch is Source Mode + Source URL only',
                'Read-only sync lock stays enforced in code',
            ],
        ];
    }

    public function isLocalConfigWritable(): bool
    {
        $path = $this->localConfigPath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            return false;
        }

        if (file_exists($path)) {
            return is_writable($path);
        }

        return is_writable($dir);
    }

    /**
     * @return array{title:string,steps:array<int,string>}
     */
    public function manualSetupInstructions(): array
    {
        return [
            'title' => 'Manual setup (when UI save is not available)',
            'steps' => [
                'Copy config/opencart.local.example.php to config/opencart.local.php on the server.',
                'Edit config/opencart.local.php via SSH/FTP — set source_mode, api_base_url, api_key, and product_api_route.',
                'Staging URL: ' . self::STAGING_URL . ' · Live URL: ' . self::LIVE_URL,
                'Keep config/opencart.local.php out of Git (already in .gitignore). Never commit real API keys.',
                'Reload this page and use Test Connection (read-only — no import).',
            ],
        ];
    }

    private function localConfigPath(): string
    {
        if (defined('IBS_CONFIG')) {
            return IBS_CONFIG . '/opencart.local.php';
        }

        return dirname(__DIR__, 3) . '/config/opencart.local.php';
    }
}
