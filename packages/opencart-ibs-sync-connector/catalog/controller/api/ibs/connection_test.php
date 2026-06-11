<?php



/**

 * IBS Sync Connector — connection test + diagnostics.

 * Route: index.php?route=api/ibs/connection_test&api_token=...

 */

class ControllerApiIbsConnectionTest extends Controller

{

    public function index()

    {

        require_once DIR_SYSTEM . 'library/ibs/bootstrap.php';

        list($apiAuth, $apiResponse) = ibs_sync_api_services($this->registry);



        $this->load->model('api/ibs/product');

        $this->load->model('api/ibs/connector');



        $authError = $apiAuth->authenticate();

        if ($authError !== null) {

            $apiResponse->error($authError, 401);



            return;

        }



        $bridgeTable = $apiAuth->bridgeTable();

        $bridgeAvailable = $this->model_api_ibs_product->bridgeAvailable($bridgeTable);

        $compatibility = $this->model_api_ibs_connector->getCompatibilityReport();

        $optionImageProbe = $this->model_api_ibs_product->probeOptionImageSources();

        $productCountProbe = $this->model_api_ibs_connector->getProductCountProbe($bridgeTable);

        $orderCountProbe = $this->model_api_ibs_connector->getOrderCountProbe();



        $message = $bridgeAvailable

            ? 'IBS Sync Connector OK. Dispatch Location bridge detected.'

            : 'IBS Sync Connector OK, but Dispatch Location bridge table was not detected.';

        if (($optionImageProbe['join_active'] ?? false) && (int) ($optionImageProbe['sample_images_non_empty'] ?? 0) === 0) {

            $message .= ' Option image join is active but sample POIP rows are empty.';

        } elseif ((int) ($optionImageProbe['sample_images_non_empty'] ?? 0) > 0) {

            $message .= ' Option image probe found ' . (int) $optionImageProbe['sample_images_non_empty'] . ' sample image(s).';

        }



        $apiResponse->send([

            'success' => true,

            'read_only' => true,

            'message' => $message,

            'connector_version' => IBS_SYNC_CONNECTOR_VERSION,

            'version' => IBS_SYNC_CONNECTOR_VERSION,

            'bridge_available' => $bridgeAvailable,

            'bridge_table' => DB_PREFIX . $bridgeTable,

            'max_limit' => $apiAuth->maxLimit(),

            'compatibility' => $compatibility,

            'option_image_probe' => $optionImageProbe,

            'product_count_probe' => $productCountProbe,

            'order_count_probe' => $orderCountProbe,

            'routes' => [

                'connection_test' => 'api/ibs/connection_test',

                'version' => 'api/ibs/version',

                'products' => 'api/ibs/products',

                'orders' => 'api/ibs/orders',

            ],

        ]);

    }

}

