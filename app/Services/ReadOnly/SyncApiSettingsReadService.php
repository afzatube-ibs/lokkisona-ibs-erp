<?php

namespace App\Services\ReadOnly;

use App\ActivityLog;
use App\Database\Connection;
use App\Database\QueryGuard;
use App\Database\TableName;
use App\Domain\EntryMappingOptions;
use App\Domain\FinalResultMappingOptions;
use App\Models\Order;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\StatusMappingWriteRepository;
use App\Services\Read\OpenCartReadClient;
use PDO;

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
            'api_key_mask' => $this->maskedApiKeyHint($apiKey),
            'product_api_route' => trim((string) config('opencart.product_api_route', '')),
            'order_api_route' => trim((string) config('opencart.order_api_route', '')),
            'order_queue_api_route' => trim((string) config('opencart.order_queue_api_route', 'api/ibs/order_queue_statuses')),
            'order_sync_mode' => trim((string) config('opencart.order_sync_mode', 'connector_queue')),
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
        $productStatus = $client->productSyncStatus();
        $mode = $this->resolvedSourceMode();
        $connectionOk = (bool) ($test['ok'] ?? false);

        return $this->buildConnectionSummary(
            $mode,
            $connectionOk,
            (string) ($test['message'] ?? ''),
            $test['bridge_available'] ?? null,
            $productStatus
        );
    }

    /**
     * Lightweight hub page summary — no live OpenCart HTTP on GET.
     */
    public function connectionSummaryForPage(): array
    {
        $mode = $this->resolvedSourceMode();
        $lastConnectionTest = $this->latestActivityEntry('sync_api_connection_test');
        $connectionOk = $this->cachedConnectionOk($mode, $lastConnectionTest);
        $message = $lastConnectionTest !== null
            ? (string) ($lastConnectionTest['message'] ?? '')
            : ($mode === 'demo' ? 'Demo mode — local sample data.' : 'Not tested yet — use Test connection.');

        return $this->buildConnectionSummary($mode, $connectionOk, $message, null, []);
    }

    /**
     * @param array<string, mixed> $productStatus
     */
    private function buildConnectionSummary(
        string $mode,
        bool $connectionOk,
        string $connectionMessage,
        $bridgeAvailable,
        array $productStatus
    ): array {
        $apiKey = trim((string) config('opencart.api_key', ''));
        $localExists = file_exists($this->localConfigPath());
        $exampleExists = file_exists($this->exampleConfigPath());
        $lastConnectionTest = $this->latestActivityEntry('sync_api_connection_test');
        $productRoute = trim((string) config('opencart.product_api_route', ''));
        $orderRoute = trim((string) config('opencart.order_api_route', ''));

        return [
            'source_mode' => $mode,
            'connection_ok' => $connectionOk,
            'connection_message' => $connectionMessage,
            'bridge_available' => $bridgeAvailable,
            'bridge_status' => $this->bridgeStatusLabel($bridgeAvailable),
            'product_route' => $productRoute,
            'order_api_route' => $orderRoute,
            'order_queue_api_route' => trim((string) config('opencart.order_queue_api_route', 'api/ibs/order_queue_statuses')),
            'api_base_url' => trim((string) config('opencart.api_base_url', '')),
            'saved_api_base_url' => trim((string) config('opencart.api_base_url', '')),
            'read_only_lock' => true,
            'product_sync_enabled' => (bool) config('opencart.product_sync_enabled', true),
            'order_sync_enabled' => (bool) config('opencart.order_sync_enabled', true),
            'dispatch_bridge_required' => (bool) config('opencart.dispatch_bridge_required', true),
            'api_key_status' => $apiKey !== '' ? 'Configured' : 'Not configured',
            'api_key_mask' => $this->maskedApiKeyHint($apiKey),
            'product_api_status' => $this->productApiStatusLabelForPage($productStatus, $connectionOk, $mode),
            'order_api_status' => $this->orderApiStatusLabelForPage($connectionOk, $mode),
            'queue_api_status' => $this->queueApiStatusLabelForPage($connectionOk, $mode),
            'last_connection_test_at' => (string) ($lastConnectionTest['time'] ?? ''),
            'last_connection_test_message' => (string) ($lastConnectionTest['message'] ?? ''),
            'last_connection_test_ok' => $lastConnectionTest !== null && $this->lastConnectionTestPassed($lastConnectionTest),
            'last_product_sync_at' => $this->lastProductSyncAt(),
            'last_order_sync_at' => $this->lastOrderSyncAt(),
            'header_badge' => $this->headerBadge($mode, $connectionOk),
            'storage' => $this->storageBadge($localExists, $exampleExists),
        ];
    }

    /**
     * @param array<string, mixed>|null $lastConnectionTest
     */
    private function cachedConnectionOk(string $mode, ?array $lastConnectionTest): bool
    {
        if ($mode === 'demo') {
            return true;
        }

        return $lastConnectionTest !== null && $this->lastConnectionTestPassed($lastConnectionTest);
    }

    /**
     * @param array<string, mixed> $lastConnectionTest
     */
    private function lastConnectionTestPassed(array $lastConnectionTest): bool
    {
        $message = strtolower(trim((string) ($lastConnectionTest['message'] ?? '')));
        if ($message !== '' && (str_contains($message, 'failed') || str_contains($message, 'error'))) {
            return false;
        }

        return true;
    }

    private function productApiStatusLabelForPage(array $productStatus, bool $connectionOk, string $mode): string
    {
        if ($productStatus !== []) {
            return $this->productApiStatusLabel($productStatus, $connectionOk);
        }

        if (!(bool) config('opencart.product_sync_enabled', true)) {
            return 'Off';
        }

        $route = trim((string) config('opencart.product_api_route', ''));
        if ($route === '') {
            return 'Route not configured';
        }

        if ($mode === 'demo') {
            return 'Demo — ready';
        }

        return $connectionOk ? 'Configured — ready' : 'Not tested';
    }

    private function orderApiStatusLabelForPage(bool $connectionOk, string $mode): string
    {
        if (!(bool) config('opencart.order_sync_enabled', true)) {
            return 'Off';
        }

        $route = trim((string) config('opencart.order_api_route', ''));
        if ($route === '') {
            return 'Route not configured';
        }

        if ($mode === 'demo') {
            return 'Demo — ready';
        }

        return $connectionOk ? 'Configured — ready' : 'Not tested';
    }

    private function queueApiStatusLabelForPage(bool $connectionOk, string $mode): string
    {
        $route = trim((string) config('opencart.order_queue_api_route', 'api/ibs/order_queue_statuses'));
        if ($route === '') {
            return 'Route not configured';
        }

        if ($mode === 'demo') {
            return 'Demo — ready';
        }

        $session = $_SESSION['ibs_connector_queue_statuses'] ?? null;
        if (is_array($session) && (int) ($session['loaded_at'] ?? 0) > 0) {
            return 'Loaded';
        }

        return $connectionOk ? 'Configured — ready' : 'Not tested';
    }

    private function lastProductSyncAt(): string
    {
        foreach (['product_sync_refresh', 'product_sync_preview', 'product_sync_reset'] as $action) {
            $entry = $this->latestActivityEntry($action);
            if ($entry !== null && ($entry['time'] ?? '') !== '') {
                return (string) $entry['time'];
            }
        }

        return '';
    }

    public function queueMappingState(?int $businessSourceId = null): array
    {
        return $this->entryMappingState($businessSourceId);
    }

    public function entryMappingState(?int $businessSourceId = null): array
    {
        $sourceId = ($businessSourceId ?? 0) > 0
            ? (int) $businessSourceId
            : (int) config('opencart.business_source_id', 1);
        $repo = new StatusMappingWriteRepository();
        $saved = $repo->findActiveQueueMappings($sourceId);
        $savedByStatus = [];
        foreach ($saved as $row) {
            $key = trim((string) ($row['source_status'] ?? ''));
            if ($key !== '') {
                $savedByStatus[$key] = $row;
            }
        }

        $session = $_SESSION['ibs_connector_queue_statuses'] ?? null;
        $connectorStatuses = is_array($session) ? ($session['statuses'] ?? []) : [];
        if (!is_array($connectorStatuses)) {
            $connectorStatuses = [];
        }

        $queueStatuses = [];
        foreach ($connectorStatuses as $status) {
            if (is_array($status) && !empty($status['selected'])) {
                $queueStatuses[] = $status;
            }
        }

        return [
            'business_source_id' => $sourceId,
            'saved_mappings' => $saved,
            'saved_by_status' => $savedByStatus,
            'mapping_count' => count($saved),
            'connector_statuses' => $connectorStatuses,
            'queue_statuses' => $queueStatuses,
            'all_statuses' => $connectorStatuses,
            'connector_queue_status_ids' => is_array($session) ? ($session['queue_status_ids'] ?? []) : [],
            'connector_loaded_at' => is_array($session) ? (int) ($session['loaded_at'] ?? 0) : 0,
            'entry_options' => EntryMappingOptions::dropdownOptions(),
            'mapping_attention_count' => $this->mappingAttentionCount($queueStatuses, $savedByStatus),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $queueStatuses
     * @param array<string, array<string, mixed>> $savedByStatus
     */
    public function mappingAttentionCount(array $queueStatuses, array $savedByStatus): int
    {
        $count = 0;
        foreach ($queueStatuses as $status) {
            $id = trim((string) ($status['status_id'] ?? ''));
            if ($id === '') {
                continue;
            }
            if (!isset($savedByStatus[$id])) {
                $count++;
            }
        }

        return $count;
    }

    public function finalResultMappingState(?int $businessSourceId = null): array
    {
        $sourceId = ($businessSourceId ?? 0) > 0
            ? (int) $businessSourceId
            : (int) config('opencart.business_source_id', 1);
        $repo = new StatusMappingWriteRepository();
        $pickers = $repo->finalResultPickerState($sourceId);
        $entry = $this->entryMappingState($sourceId);

        return array_merge($pickers, [
            'business_source_id' => $sourceId,
            'connector_statuses' => $entry['connector_statuses'],
            'connector_loaded_at' => $entry['connector_loaded_at'],
            'target_options' => FinalResultMappingOptions::targetOptions(),
        ]);
    }

    public function hubState(?int $businessSourceId = null): array
    {
        $sourceId = ($businessSourceId ?? 0) > 0
            ? (int) $businessSourceId
            : (int) config('opencart.business_source_id', 1);
        $orderSession = $_SESSION['ibs_order_sync_preview'] ?? null;
        $productSession = $_SESSION['ibs_product_sync_preview'] ?? null;
        $entryMapping = $this->entryMappingState($sourceId);

        return [
            'connection' => $this->connectionSummaryForPage(),
            'settings' => $this->formState(),
            'entry_mapping' => $entryMapping,
            'final_result_mapping' => $this->finalResultMappingState($sourceId),
            'product_sync' => $this->productSyncHubState($productSession),
            'order_sync' => [
                'preview' => is_array($orderSession) ? $this->orderPreviewFromSession($orderSession) : null,
                'session_active' => is_array($orderSession),
            ],
            'sync_history' => $this->syncHistoryEntries(5),
            'mapping_attention_count' => (int) ($entryMapping['mapping_attention_count'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed>|null $productSession
     * @return array{state: string, rows: array<int, array<string, mixed>>, importable: bool, session_active: bool}
     */
    public function productSyncHubState(?array $productSession): array
    {
        if (!is_array($productSession) || !isset($productSession['fetched_at'])) {
            return [
                'state' => 'initial',
                'rows' => [],
                'importable' => false,
                'session_active' => false,
            ];
        }

        $rows = $this->normalizeProductPreviewRows($productSession);
        if ($rows === []) {
            return [
                'state' => 'empty_result',
                'rows' => [],
                'importable' => false,
                'session_active' => true,
            ];
        }

        return [
            'state' => 'has_rows',
            'rows' => $rows,
            'importable' => true,
            'session_active' => true,
        ];
    }

    /**
     * @param array<string, mixed> $productSession
     * @return array<int, array<string, mixed>>
     */
    private function normalizeProductPreviewRows(array $productSession): array
    {
        $display = is_array($productSession['display'] ?? null) ? $productSession['display'] : [];
        $importRows = is_array($productSession['products'] ?? null) ? $productSession['products'] : [];
        $warehouseBySourceId = [];

        foreach ($importRows as $importRow) {
            if (!is_array($importRow)) {
                continue;
            }
            $sourceProductId = trim((string) ($importRow['source_product_id'] ?? ''));
            if ($sourceProductId === '') {
                continue;
            }
            $warehouseBySourceId[$sourceProductId] = OpenCartReadClient::isStrictSupplierProduct($importRow);
        }

        $normalized = [];
        foreach ($display['rows'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $sourceProductId = trim((string) ($row['source_product_id'] ?? ''));
            $model = trim((string) ($row['source_model'] ?? $row['model'] ?? ''));
            $name = trim((string) ($row['product_name'] ?? $row['name'] ?? ''));
            if ($model === '' && $name === '' && $sourceProductId === '') {
                continue;
            }

            $optionCount = (int) ($row['option_count'] ?? 0);
            if ($optionCount === 0) {
                $optionCount = count(is_array($row['options'] ?? null) ? $row['options'] : []);
            }

            $fromWarehouse = $warehouseBySourceId[$sourceProductId]
                ?? OpenCartReadClient::isStrictSupplierProduct($row);

            $normalized[] = [
                'model' => $model,
                'name' => $name,
                'from_warehouse' => $fromWarehouse,
                'option_count' => $optionCount,
            ];
        }

        return array_slice($normalized, 0, 20);
    }

    public function mappingConfigAlertNeeded(int $businessSourceId): bool
    {
        $repo = new StatusMappingWriteRepository();

        return $repo->countActiveQueueMappings($businessSourceId) === 0;
    }

    /**
     * @return array{label: string, class: string}
     */
    public function connectionChipState(array $connectionSummary): array
    {
        $mode = (string) ($connectionSummary['source_mode'] ?? 'demo');
        $lastTestOk = !empty($connectionSummary['last_connection_test_ok']);
        $lastTestAt = trim((string) ($connectionSummary['last_connection_test_at'] ?? ''));

        if ($mode === 'demo') {
            return ['label' => 'Connection OK', 'class' => 'is-ok'];
        }

        if ($lastTestOk) {
            return ['label' => 'Connection OK', 'class' => 'is-ok'];
        }

        if ($lastTestAt === '') {
            return ['label' => 'Not tested', 'class' => 'is-untested'];
        }

        return ['label' => 'Not ready', 'class' => 'is-warn'];
    }

    /**
     * @param array<string, mixed> $session
     * @return array<string, mixed>
     */
    private function orderPreviewFromSession(array $session): array
    {
        $counts = is_array($session['counts'] ?? null) ? $session['counts'] : [];
        $labeled = \App\Support\OrderSyncPreviewPresenter::labeledPreviewCounts($counts);

        return [
            'display_rows' => is_array($session['display_rows'] ?? null) ? $session['display_rows'] : [],
            'preview_counts' => $labeled,
            'importable_count' => (int) ($counts['eligible'] ?? 0) + (int) ($counts['updated_snapshot'] ?? 0),
            'active_preview_id' => (int) ($session['preview_id'] ?? 0),
            'pagination' => is_array($session['pagination'] ?? null) ? $session['pagination'] : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function syncHistoryEntries(int $limit = 20): array
    {
        $actions = [
            'sync_api_settings_saved',
            'sync_api_connection_test',
            'sync_queue_statuses_loaded',
            'sync_entry_mappings_saved',
            'sync_final_result_mappings_saved',
            'sync_queue_mappings_saved',
            'product_sync_preview',
            'product_sync_refresh',
            'product_sync_reset',
            'sync_test_preview',
            'sync_import',
        ];

        $entries = [];
        foreach (ActivityLog::recent(200) as $entry) {
            if (!in_array((string) ($entry['action'] ?? ''), $actions, true)) {
                continue;
            }
            $entries[] = $entry;
            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    private function maskedApiKeyHint(string $apiKey): string
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            return '';
        }

        $suffix = strlen($apiKey) >= 4 ? substr($apiKey, -4) : $apiKey;

        return 'Token saved ••••••••' . $suffix;
    }

    private function productApiStatusLabel(array $productStatus, bool $connectionOk): string
    {
        if (!(bool) config('opencart.product_sync_enabled', true)) {
            return 'Off';
        }

        $route = trim((string) config('opencart.product_api_route', ''));
        if ($route === '') {
            return 'Route not configured';
        }

        if (!($productStatus['product_pull_available'] ?? false)) {
            return $connectionOk ? 'Configured — preview unavailable' : 'Not ready';
        }

        $mode = (string) ($productStatus['mode'] ?? 'off');

        return $connectionOk ? ucfirst($mode) . ' — ready' : 'Not connected';
    }

    private function orderApiStatusLabel(bool $connectionOk): string
    {
        if (!(bool) config('opencart.order_sync_enabled', true)) {
            return 'Off';
        }

        $route = trim((string) config('opencart.order_api_route', ''));
        if ($route === '') {
            return 'Route not configured';
        }

        if (!$connectionOk) {
            return 'Not connected';
        }

        return 'Configured — ready';
    }

    /**
     * @param bool|null $bridgeAvailable
     */
    private function bridgeStatusLabel($bridgeAvailable): string
    {
        if (!(bool) config('opencart.dispatch_bridge_required', true)) {
            return 'Not required';
        }

        if ($bridgeAvailable === null) {
            return 'Unknown';
        }

        return $bridgeAvailable ? 'Available' : 'Unavailable';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestActivityEntry(string $action): ?array
    {
        foreach (ActivityLog::recent(200) as $entry) {
            if (($entry['action'] ?? '') === $action) {
                return $entry;
            }
        }

        return null;
    }

    private function lastOrderSyncAt(): string
    {
        $importEntry = $this->latestActivityEntry('sync_import');
        if ($importEntry !== null && ($importEntry['time'] ?? '') !== '') {
            return (string) $importEntry['time'];
        }

        try {
            $pdo = Connection::pdo();
            $table = TableName::forModel(Order::class);
            $sql = 'SELECT MAX(created_at) AS latest FROM `' . str_replace('`', '``', $table) . '`';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->query($sql);
            if ($stmt === false) {
                return '';
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return trim((string) ($row['latest'] ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
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
