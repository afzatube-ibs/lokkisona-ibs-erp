<?php

/**
 * IBS read-only warehouse products (v1.8.3).
 * Route: index.php?route=api/ibs/products&api_token=...&page=1&limit=20
 * Returns only Dispatch Location bridge rows where from_warehouse = 1.
 */
class ControllerApiIbsProducts extends Controller
{
    public function index()
    {
        $this->load->library('ibs/api_auth');
        $this->load->library('ibs/api_response');
        $this->load->model('api/ibs/product');

        $authError = $this->ibs_api_auth->authenticate();
        if ($authError !== null) {
            $this->ibs_api_response->error($authError, 401);

            return;
        }

        $bridgeTable = $this->ibs_api_auth->bridgeTable();
        $bridgeAvailable = $this->model_api_ibs_product->bridgeAvailable($bridgeTable);
        if (!$bridgeAvailable) {
            $this->ibs_api_response->send([
                'success' => false,
                'read_only' => true,
                'bridge_available' => false,
                'error' => 'Dispatch Location bridge table not found. Product sync cannot safely identify IBS supplier products.',
                'page' => $this->ibs_api_auth->page(),
                'limit' => $this->ibs_api_auth->limit(),
                'has_previous' => false,
                'has_next' => false,
                'products' => [],
            ], 503);

            return;
        }

        $page = $this->ibs_api_auth->page();
        $limit = $this->ibs_api_auth->limit();
        $result = $this->model_api_ibs_product->getPagedProducts($bridgeTable, $page, $limit);
        $total = (int) ($result['total'] ?? 0);
        $offset = ($page - 1) * $limit;

        $this->ibs_api_response->send([
            'success' => true,
            'read_only' => true,
            'bridge_available' => true,
            'page' => $page,
            'limit' => $limit,
            'has_previous' => $page > 1,
            'has_next' => ($offset + $limit) < $total,
            'products' => $result['products'] ?? [],
        ]);
    }
}
