<?php

/**
 * IBS Sync Connector — read-only supplier queue orders (warehouse lines only).
 * Route: index.php?route=api/ibs/orders&api_token=...&page=1&limit=20
 */
class ControllerApiIbsOrders extends Controller
{
    public function index()
    {
        require_once DIR_SYSTEM . 'library/ibs/bootstrap.php';
        list($apiAuth, $apiResponse) = ibs_sync_api_services($this->registry);

        $this->load->model('api/ibs/order');
        $this->load->model('api/ibs/product');

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
                'bridge_available' => false,
                'error' => 'Dispatch Location bridge table not found. Order sync cannot safely identify supplier products.',
                'page' => $apiAuth->page(),
                'limit' => $apiAuth->limit(),
                'has_previous' => false,
                'has_next' => false,
                'orders' => [],
                'total' => 0,
                'filter_applied' => 'bridge_missing',
            ], 503);

            return;
        }

        $page = $apiAuth->page();
        $limit = $apiAuth->limit();
        $filters = [
            'date_from' => $this->request->get['date_from'] ?? null,
            'date_to' => $this->request->get['date_to'] ?? null,
        ];

        $result = $this->model_api_ibs_order->getPagedOrders($page, $limit, $filters);
        $total = (int) ($result['total'] ?? 0);
        $offset = ($page - 1) * $limit;
        $filterApplied = (string) ($result['filter_applied'] ?? 'queue_and_warehouse');

        $payload = [
            'success' => true,
            'read_only' => true,
            'connector_version' => IBS_SYNC_CONNECTOR_VERSION,
            'bridge_available' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'has_previous' => $page > 1,
            'has_next' => ($offset + $limit) < $total,
            'filter_applied' => $filterApplied,
            'queue_status_ids' => $result['queue_status_ids'] ?? [],
            'orders' => $result['orders'] ?? [],
        ];

        if ($filterApplied === 'queue_empty') {
            $payload['warning'] = (string) ($result['warning'] ?? 'No supplier queue statuses configured in connector admin.');
        }

        $apiResponse->send($payload);
    }
}
