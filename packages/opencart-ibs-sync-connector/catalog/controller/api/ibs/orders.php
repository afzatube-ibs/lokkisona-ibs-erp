<?php



/**

 * IBS Sync Connector — read-only orders + courier fields when available.

 * Route: index.php?route=api/ibs/orders&api_token=...&page=1&limit=20

 */

class ControllerApiIbsOrders extends Controller

{

    public function index()

    {

        require_once DIR_SYSTEM . 'library/ibs/bootstrap.php';

        list($apiAuth, $apiResponse) = ibs_sync_api_services($this->registry);



        $this->load->model('api/ibs/order');



        $authError = $apiAuth->authenticate();

        if ($authError !== null) {

            $apiResponse->error($authError, 401);



            return;

        }



        $page = $apiAuth->page();

        $limit = $apiAuth->limit();

        $filters = [

            'status_id' => $this->request->get['status_id'] ?? null,

            'date_from' => $this->request->get['date_from'] ?? null,

            'date_to' => $this->request->get['date_to'] ?? null,

        ];



        $result = $this->model_api_ibs_order->getPagedOrders($page, $limit, $filters);

        $total = (int) ($result['total'] ?? 0);

        $offset = ($page - 1) * $limit;



        $apiResponse->send([

            'success' => true,

            'read_only' => true,

            'connector_version' => IBS_SYNC_CONNECTOR_VERSION,

            'page' => $page,

            'limit' => $limit,

            'has_previous' => $page > 1,

            'has_next' => ($offset + $limit) < $total,

            'orders' => $result['orders'] ?? [],

        ]);

    }

}

