<?php

/**
 * IBS Sync Connector — supplier order queue statuses (read-only).
 * Route: index.php?route=api/ibs/order_queue_statuses&api_token=...
 */
class ControllerApiIbsOrderQueueStatuses extends Controller
{
    public function index()
    {
        require_once DIR_SYSTEM . 'library/ibs/bootstrap.php';
        list($apiAuth, $apiResponse) = ibs_sync_api_services($this->registry);

        $this->load->model('api/ibs/product');
        $this->load->model('api/ibs/order_queue_status');

        $authError = $apiAuth->authenticate();
        if ($authError !== null) {
            $apiResponse->error($authError, 401);

            return;
        }

        $bridgeTable = $apiAuth->bridgeTable();
        $bridgeAvailable = $this->model_api_ibs_product->bridgeAvailable($bridgeTable);
        if (!$bridgeAvailable) {
            $apiResponse->send([
                'success' => false,
                'read_only' => true,
                'connector_version' => IBS_SYNC_CONNECTOR_VERSION,
                'bridge_table' => $bridgeTable,
                'bridge_available' => false,
                'error' => 'Dispatch Location bridge table not found. Order queue sync cannot safely identify supplier products.',
                'queue_status_ids' => [],
                'queue_statuses' => [],
                'total_statuses' => 0,
                'selected_count' => 0,
            ], 503);

            return;
        }

        $settings = $apiAuth->settings();
        $queueStatusIds = $settings['queue_status_ids'] ?? [];
        $statuses = $this->model_api_ibs_order_queue_status->getQueueStatuses($queueStatusIds);
        $selectedCount = 0;
        foreach ($statuses as $status) {
            if (!empty($status['selected'])) {
                $selectedCount++;
            }
        }

        $apiResponse->send([
            'success' => true,
            'read_only' => true,
            'connector_version' => IBS_SYNC_CONNECTOR_VERSION,
            'bridge_table' => $bridgeTable,
            'bridge_available' => true,
            'max_limit' => $apiAuth->maxLimit(),
            'queue_status_ids' => array_values(array_map('strval', $queueStatusIds)),
            'queue_statuses' => $statuses,
            'total_statuses' => count($statuses),
            'selected_count' => $selectedCount,
        ]);
    }
}
