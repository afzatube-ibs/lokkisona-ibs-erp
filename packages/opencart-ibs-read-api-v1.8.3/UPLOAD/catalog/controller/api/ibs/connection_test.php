<?php

/**
 * IBS read-only connection test (v1.8.3).
 * Route: index.php?route=api/ibs/connection_test&api_token=...
 */
class ControllerApiIbsConnectionTest extends Controller
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

        $this->ibs_api_response->send([
            'success' => true,
            'read_only' => true,
            'message' => $bridgeAvailable
                ? 'IBS read-only API OK. Dispatch Location bridge detected.'
                : 'IBS read-only API OK, but Dispatch Location bridge table was not detected.',
            'bridge_available' => $bridgeAvailable,
            'bridge_table' => DB_PREFIX . $bridgeTable,
            'max_limit' => $this->ibs_api_auth->maxLimit(),
            'routes' => [
                'connection_test' => 'api/ibs/connection_test',
                'products' => 'api/ibs/products',
                'orders' => 'api/ibs/orders',
            ],
            'version' => '1.8.3',
        ]);
    }
}
