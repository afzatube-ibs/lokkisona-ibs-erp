<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Domain\EntryMappingOptions;
use App\Repositories\Write\StatusMappingWriteRepository;
use App\Services\Read\OpenCartReadClient;
use App\Services\ReadOnly\SyncApiSettingsReadService;

/**
 * Sync/API settings writer (v1.8.2) — saves to gitignored config/opencart.local.php only.
 */
class SyncApiSettingsWriteService
{
    private const LOCAL_FILENAME = 'opencart.local.php';

    public function save(array $input): WriteResult
    {
        $sourceMode = strtolower(trim((string) ($input['source_mode'] ?? 'demo')));
        if (!in_array($sourceMode, ['demo', 'staging', 'live'], true)) {
            return WriteResult::fail('Source Mode must be Demo, Staging, or Live.');
        }

        $apiBaseUrl = trim((string) ($input['api_base_url'] ?? ''));
        if ($apiBaseUrl === '' && $sourceMode === 'staging') {
            $apiBaseUrl = SyncApiSettingsReadService::STAGING_URL;
        } elseif ($apiBaseUrl === '' && $sourceMode === 'live') {
            $apiBaseUrl = SyncApiSettingsReadService::LIVE_URL;
        }

        $productRoute = trim((string) ($input['product_api_route'] ?? ''));
        $orderRoute = trim((string) ($input['order_api_route'] ?? ''));
        $incomingKey = trim((string) ($input['api_key'] ?? ''));
        $existingKey = trim((string) config('opencart.api_key', ''));

        if ($sourceMode !== 'demo') {
            if ($apiBaseUrl === '') {
                return WriteResult::fail('Source URL is required for Staging or Live mode.');
            }
            if (!filter_var($apiBaseUrl, FILTER_VALIDATE_URL)) {
                return WriteResult::fail('Source URL must be a valid URL (https://...).');
            }
            if ($incomingKey === '' && $existingKey === '') {
                return WriteResult::fail('API key/token is required on first save for Staging or Live mode.');
            }
        }

        $payload = [
            'source_mode' => $sourceMode,
            'enabled' => $sourceMode !== 'demo',
            'demo_mode' => $sourceMode === 'demo',
            'api_base_url' => $apiBaseUrl,
            'product_api_route' => $productRoute,
            'order_api_route' => $orderRoute !== '' ? $orderRoute : 'api/ibs/orders',
            'order_queue_api_route' => trim((string) config('opencart.order_queue_api_route', 'api/ibs/order_queue_statuses')),
            'order_sync_mode' => 'connector_queue',
            'read_only_lock' => true,
            'max_rows_per_page' => 20,
            'max_orders_per_request' => 20,
            'max_products_per_request' => 20,
            'product_sync_enabled' => $this->boolInput($input, 'product_sync_enabled', true),
            'order_sync_enabled' => $this->boolInput($input, 'order_sync_enabled', true),
            'dispatch_bridge_required' => $this->boolInput($input, 'dispatch_bridge_required', true),
        ];

        if ($incomingKey !== '') {
            $payload['api_key'] = $incomingKey;
        } elseif ($existingKey !== '') {
            $payload['api_key'] = $existingKey;
        } else {
            $payload['api_key'] = '';
        }

        $path = $this->localConfigPath();
        if (!(new SyncApiSettingsReadService())->isLocalConfigWritable()) {
            return WriteResult::fail(
                'Cannot write config/opencart.local.php from the web UI (file or config folder not writable). '
                . 'Manual setup: copy config/opencart.local.example.php to config/opencart.local.php on the server, '
                . 'edit values via SSH/FTP, keep the file out of Git, then reload this page and use Test Connection.'
            );
        }

        $written = $this->writeLocalConfig($path, $payload);
        if (!$written) {
            return WriteResult::fail(
                'Save failed unexpectedly. Manual setup: copy config/opencart.local.example.php to config/opencart.local.php, '
                . 'set source_mode, api_base_url, api_key, and routes on the server only — do not commit API keys to Git.'
            );
        }

        config_reload();

        ActivityLog::record('sync_api_settings_saved', 'Sync/API settings saved to local config file', [
            'source_mode' => $sourceMode,
            'api_base_url' => $apiBaseUrl,
            'product_api_route' => $productRoute,
            'api_key_updated' => $incomingKey !== '',
        ]);

        return WriteResult::ok('Sync/API settings saved to config/opencart.local.php. API key status: Configured (value never displayed).');
    }

    /**
     * Read-only connection test — does not preview or import products/orders.
     */
    public function testConnection(): WriteResult
    {
        $test = (new OpenCartReadClient())->testConnection();
        if (!($test['ok'] ?? false)) {
            return WriteResult::fail('Connection test failed: ' . (string) ($test['message'] ?? 'Unknown error'));
        }

        ActivityLog::record('sync_api_connection_test', 'Sync/API connection test completed', [
            'mode' => (string) ($test['mode'] ?? ''),
            'bridge_available' => $test['bridge_available'] ?? null,
        ]);

        return WriteResult::ok('Connection test OK — ' . (string) ($test['message'] ?? ''));
    }

    public function loadQueueStatuses(): WriteResult
    {
        $fetch = (new OpenCartReadClient())->fetchOrderQueueStatuses();
        if (!($fetch['ok'] ?? false)) {
            return WriteResult::fail((string) ($fetch['message'] ?? 'Failed to load queue statuses from connector.'));
        }

        $_SESSION['ibs_connector_queue_statuses'] = [
            'loaded_at' => time(),
            'statuses' => $fetch['statuses'] ?? [],
            'queue_status_ids' => $fetch['queue_status_ids'] ?? [],
            'bridge_available' => $fetch['bridge_available'] ?? null,
        ];

        $selectedCount = count($fetch['queue_status_ids'] ?? []);
        ActivityLog::record('sync_queue_statuses_loaded', 'Supplier order queue statuses loaded from connector', [
            'selected_count' => $selectedCount,
            'total_count' => is_array($fetch['statuses'] ?? null) ? count($fetch['statuses']) : 0,
        ]);

        return WriteResult::ok(
            'Loaded queue statuses from connector. '
            . $selectedCount . ' selected in OpenCart admin · '
            . (is_array($fetch['statuses'] ?? null) ? count($fetch['statuses']) : 0) . ' total statuses.'
        );
    }

    public function saveQueueMappings(array $input): WriteResult
    {
        return $this->saveEntryMappings($input);
    }

    public function saveEntryMappings(array $input): WriteResult
    {
        $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
        $mappings = $input['entry_mapping'] ?? $input['queue_mapping'] ?? [];
        if (!is_array($mappings) || $mappings === []) {
            return WriteResult::fail('No entry mappings submitted.');
        }

        $repo = new StatusMappingWriteRepository();
        $saved = 0;
        $savedStatusIds = [];

        foreach ($mappings as $queueStatusId => $action) {
            $queueStatusId = trim((string) $queueStatusId);
            $action = trim((string) $action);
            if ($queueStatusId === '' || $action === '') {
                continue;
            }

            if (!EntryMappingOptions::isValid($action)) {
                return WriteResult::fail('Queue status #' . $queueStatusId . ': choose Import as NEW or Ignore.');
            }

            if ($action === EntryMappingOptions::IGNORE) {
                $existing = $repo->resolveQueueMapping($sourceId, $queueStatusId);
                if ($existing !== null) {
                    $repo->setActive((int) ($existing['status_mapping_id'] ?? 0), false);
                }
                continue;
            }

            $repo->upsertQueueMapping($sourceId, $queueStatusId, EntryMappingOptions::IBS_STATUS_FOR_IMPORT);
            $savedStatusIds[] = $queueStatusId;
            $saved++;
        }

        if ($saved === 0 && $savedStatusIds === []) {
            $hasIgnoreOnly = false;
            foreach ($mappings as $action) {
                if (trim((string) $action) === EntryMappingOptions::IGNORE) {
                    $hasIgnoreOnly = true;
                    break;
                }
            }
            if (!$hasIgnoreOnly) {
                return WriteResult::fail('Select Import as NEW for at least one OpenCart queue status.');
            }
        }

        $repo->deactivateQueueMappingsNotIn($sourceId, $savedStatusIds);

        ActivityLog::record('sync_entry_mappings_saved', 'Entry status mappings saved (Import as NEW / Ignore)', [
            'business_source_id' => $sourceId,
            'import_new_count' => $saved,
        ]);

        return WriteResult::ok('Saved entry mappings: ' . $saved . ' Import as NEW' . ($saved === 1 ? '' : ' statuses') . '.');
    }

    public function saveFinalResultMappings(array $input): WriteResult
    {
        $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
        $deliveredStatusId = trim((string) ($input['final_delivered_status_id'] ?? ''));
        $returnedStatusId = trim((string) ($input['final_returned_status_id'] ?? ''));

        if ($deliveredStatusId === '' && $returnedStatusId === '') {
            (new StatusMappingWriteRepository())->deactivateAllFinalResultMappings($sourceId);

            return WriteResult::ok('Final result mappings cleared.');
        }

        $repo = new StatusMappingWriteRepository();

        try {
            $repo->saveFinalResultMappings($sourceId, $deliveredStatusId, $returnedStatusId);
        } catch (\InvalidArgumentException $e) {
            return WriteResult::fail($e->getMessage());
        }

        ActivityLog::record('sync_final_result_mappings_saved', 'Final result mappings saved (Delivered / Returned)', [
            'business_source_id' => $sourceId,
            'delivered_status_id' => $deliveredStatusId,
            'returned_status_id' => $returnedStatusId,
        ]);

        return WriteResult::ok('Final result mapping saved. Applies only after order is Dispatched in IBS.');
    }

    public function resetToDemo(): WriteResult
    {
        $productRoute = trim((string) config('opencart.product_api_route', ''));
        if ($productRoute === '') {
            $productRoute = 'demo/warehouse_product';
        }

        return $this->save([
            'source_mode' => 'demo',
            'api_base_url' => '',
            'api_key' => '',
            'product_api_route' => $productRoute,
            'order_api_route' => trim((string) config('opencart.order_api_route', '')) ?: 'api/ibs/orders',
            'product_sync_enabled' => '1',
            'order_sync_enabled' => '1',
            'dispatch_bridge_required' => '1',
        ]);
    }

    private function boolInput(array $input, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $input)) {
            return $default;
        }

        return in_array((string) $input[$key], ['1', 'on', 'yes', 'true'], true);
    }

    private function localConfigPath(): string
    {
        if (defined('IBS_CONFIG')) {
            return IBS_CONFIG . '/opencart.local.php';
        }

        return dirname(__DIR__, 3) . '/config/opencart.local.php';
    }

    private function writeLocalConfig(string $path, array $payload): bool
    {
        $export = var_export($payload, true);
        $contents = "<?php\n\n/**\n * Local Sync/API overrides — gitignored. Do not commit API keys.\n */\nreturn "
            . $export . ";\n";

        return @file_put_contents($path, $contents, LOCK_EX) !== false;
    }
}
