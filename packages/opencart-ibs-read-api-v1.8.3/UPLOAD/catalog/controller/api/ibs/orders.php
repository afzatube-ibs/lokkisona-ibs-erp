<?php

/**
 * IBS read-only orders (v1.8.3).
 * Route: index.php?route=api/ibs/orders&api_token=...&page=1&limit=20
 * Optional filters: status_id, date_from (YYYY-MM-DD), date_to (YYYY-MM-DD)
 */
class ControllerApiIbsOrders extends Controller
{
    public function index()
    {
        $this->load->library('ibs/api_auth');
        $this->load->library('ibs/api_response');
        $this->load->model('api/ibs/order');

        $authError = $this->ibs_api_auth->authenticate();
        if ($authError !== null) {
            $this->ibs_api_response->error($authError, 401);

            return;
        }

        $page = $this->ibs_api_auth->page();
        $limit = $this->ibs_api_auth->limit();
        $filters = [
            'status_id' => $this->request->get['status_id'] ?? null,
            'date_from' => $this->request->get['date_from'] ?? null,
            'date_to' => $this->request->get['date_to'] ?? null,
        ];

        $result = $this->model_api_ibs_order->getPagedOrders($page, $limit, $filters);
        $total = (int) ($result['total'] ?? 0);
        $offset = ($page - 1) * $limit;

        $this->ibs_api_response->send([
            'success' => true,
            'read_only' => true,
            'page' => $page,
            'limit' => $limit,
            'has_previous' => $page > 1,
            'has_next' => ($offset + $limit) < $total,
            'orders' => $result['orders'] ?? [],
        ]);
    }
}
