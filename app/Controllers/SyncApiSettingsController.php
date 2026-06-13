<?php



namespace App\Controllers;



use App\ActivityLog;

use App\Csrf;

use App\Permission;

use App\ReadFoundation\WriteGate;

use App\Services\ReadOnly\SyncApiSettingsReadService;

use App\Services\Write\SyncApiSettingsWriteService;

use App\Services\Write\SyncHubResetWriteService;

use App\Services\Write\SyncImportWriteService;

use App\Services\Write\SyncPreviewWriteService;

use App\Support\SyncRequestGuard;



class SyncApiSettingsController extends Controller

{

    private const HUB_PATH = '/sync-api-settings';



    /** @var array<int, string> */

    private const ALLOWED_TABS = ['connection', 'mapping', 'products', 'sync'];



    public function index()

    {

        $this->authorize('sync_api_settings.view');

        ActivityLog::record('sync_api_settings_access', 'Sync & Mapping Settings page viewed');



        $read = new SyncApiSettingsReadService();

        $hub = $read->hubState();

        $activeTab = $this->resolveActiveTab();

        $sourceId = (int) ($hub['entry_mapping']['business_source_id'] ?? config('opencart.business_source_id', 1));



        $this->render('sync-api-settings.index', [

            'pageTitle' => 'Sync & Mapping',

            'bodyClass' => 'sync-hub-page',

            'breadcrumbs' => [

                ['label' => 'Settings', 'active' => false],

                ['label' => 'Sync & Mapping', 'active' => true],

            ],

            'accessMode' => Permission::accessMode(),

            'activeTab' => $activeTab,

            'settings' => $hub['settings'],

            'connectionSummary' => $hub['connection'],

            'entryMapping' => $hub['entry_mapping'],

            'finalResultMapping' => $hub['final_result_mapping'],

            'productSync' => $hub['product_sync'],

            'orderSync' => $hub['order_sync'],

            'syncHistory' => $hub['sync_history'],

            'mappingAttentionCount' => (int) ($hub['mapping_attention_count'] ?? 0),

            'mappingConfigAlertNeeded' => $read->mappingConfigAlertNeeded($sourceId),

            'connectionChip' => $read->connectionChipState($hub['connection']),

            'mappingQueueLoaded' => (int) ($hub['entry_mapping']['connector_loaded_at'] ?? 0) > 0,

            'flashSuccess' => $this->pullFlash('success'),

            'flashError' => $this->pullFlash('error'),

            'csrfField' => Csrf::field(),

            'canManage' => Permission::canSyncHub(),

            'canSyncHub' => Permission::canSyncHub(),

            'canResetProductSync' => Permission::can('sync_preview.manage'),

            'productWriteGateReady' => WriteGate::productSyncImport()['ready'],

            'orderWriteGateReady' => WriteGate::syncPreviewImport()['ready'],

            'queueMapping' => $hub['entry_mapping'],

        ]);

    }



    public function save()

    {

        $this->authorize('sync_api_settings.manage');

        $this->requirePost();

        if (!$this->validateCsrf()) {

            $this->flash('error', 'Invalid security token.');

            redirect($this->hubPath('connection'));

        }



        $this->redirectWithWriteResult($this->hubPath('connection'), (new SyncApiSettingsWriteService())->save($_POST));

    }



    public function testConnection()

    {

        $this->authorize('sync_api_settings.manage');

        $this->requirePost();

        if (!$this->validateCsrf()) {

            $this->flash('error', 'Invalid security token.');

            redirect($this->hubPath('connection'));

        }



        if (!SyncRequestGuard::acquire()) {

            $this->flash('error', SyncRequestGuard::busyMessage());

            redirect($this->hubPath('connection'));

        }



        try {

            $this->redirectWithWriteResult($this->hubPath('connection'), (new SyncApiSettingsWriteService())->testConnection());

        } finally {

            SyncRequestGuard::release();

        }

    }



    public function resetDemo()

    {

        $this->authorize('sync_api_settings.manage');

        $this->requirePost();

        if (!$this->validateCsrf()) {

            $this->flash('error', 'Invalid security token.');

            redirect($this->hubPath('connection'));

        }



        $this->redirectWithWriteResult($this->hubPath('connection'), (new SyncApiSettingsWriteService())->resetToDemo());

    }



    public function loadQueueStatuses()

    {

        $this->authorize('sync_api_settings.manage');

        $this->requirePost();

        if (!$this->validateCsrf()) {

            $this->flash('error', 'Invalid security token.');

            redirect($this->hubPath('mapping'));

        }



        if (!SyncRequestGuard::acquire()) {

            $this->flash('error', SyncRequestGuard::busyMessage());

            redirect($this->hubPath('mapping'));

        }



        try {

            $this->redirectWithWriteResult($this->hubPath('mapping'), (new SyncApiSettingsWriteService())->loadQueueStatuses());

        } finally {

            SyncRequestGuard::release();

        }

    }



    public function saveQueueMappings()

    {

        return $this->saveEntryMappings();

    }



    public function saveEntryMappings()

    {

        $this->authorize('sync_api_settings.manage');

        $this->requirePost();

        if (!$this->validateCsrf()) {

            $this->flash('error', 'Invalid security token.');

            redirect($this->hubPath('mapping'));

        }



        if (!Permission::canSyncHub()) {

            $this->flash('error', 'Sync Hub permission required to save mappings.');

            redirect($this->hubPath('mapping'));

        }



        $this->redirectWithWriteResult($this->hubPath('mapping'), (new SyncApiSettingsWriteService())->saveEntryMappings($_POST));

    }



    public function saveFinalResultMappings()

    {

        $this->authorize('sync_api_settings.manage');

        $this->requirePost();

        if (!$this->validateCsrf()) {

            $this->flash('error', 'Invalid security token.');

            redirect($this->hubPath('mapping'));

        }



        if (!Permission::canSyncHub()) {

            $this->flash('error', 'Sync Hub permission required to save mappings.');

            redirect($this->hubPath('mapping'));

        }



        $this->redirectWithWriteResult($this->hubPath('mapping'), (new SyncApiSettingsWriteService())->saveFinalResultMappings($_POST));

    }



    public function previewProducts()

    {

        $this->hubSyncAction('sync_preview.manage', 'products', function () {

            return (new SyncPreviewWriteService())->previewProducts($_POST);

        });

    }



    public function importProducts()

    {

        $this->hubSyncAction('sync_preview.manage', 'products', function () {

            return (new SyncPreviewWriteService())->importProductsFromPreview($_POST);

        });

    }



    public function runOrderPreview()

    {

        $this->hubSyncAction('sync_preview.manage', 'sync', function () {

            return (new SyncPreviewWriteService())->runTestSync($_POST);

        });

    }



    public function importOrders()

    {

        $this->hubSyncAction('sync_preview.manage', 'sync', function () {

            return (new SyncImportWriteService())->importFromPreview($_POST);

        });

    }



    public function resetClearProductPreview()

    {

        $this->hubResetAction(function () {

            return (new SyncHubResetWriteService())->clearProductPreviewSession($_POST);

        });

    }



    public function resetProductData()

    {

        $this->hubResetAction(function () {

            return (new SyncHubResetWriteService())->resetProductData($_POST);

        });

    }



    public function resetClearOrderPreview()

    {

        $this->hubResetAction(function () {

            return (new SyncHubResetWriteService())->clearOrderPreviewSession($_POST);

        });

    }



    public function resetDemoOrders()

    {

        $this->hubResetAction(function () {

            return (new SyncHubResetWriteService())->cleanDemoOrders($_POST);

        });

    }



    public function resetEntryMappings()

    {

        $this->hubResetAction(function () {

            return (new SyncHubResetWriteService())->clearEntryMappings($_POST);

        });

    }



    public function resetFinalResultMappings()

    {

        $this->hubResetAction(function () {

            return (new SyncHubResetWriteService())->clearFinalResultMappings($_POST);

        });

    }



    /**

     * @param callable(): \App\Services\Write\WriteResult $callback

     */

    private function hubSyncAction(string $permission, string $tab, callable $callback): void

    {

        $this->authorize($permission);

        $this->requirePost();

        if (!$this->validateCsrf()) {

            $this->flash('error', 'Invalid security token.');

            redirect($this->hubPath($tab));

        }



        if (!SyncRequestGuard::acquire()) {

            $this->flash('error', SyncRequestGuard::busyMessage());

            redirect($this->hubPath($tab));

        }



        try {

            $this->redirectWithWriteResult($this->hubPath($tab), $callback());

        } finally {

            SyncRequestGuard::release();

        }

    }



    /**

     * @param callable(): \App\Services\Write\WriteResult $callback

     */

    private function hubResetAction(callable $callback): void

    {

        $this->authorize('sync_api_settings.manage');

        $this->requirePost();

        if (!$this->validateCsrf()) {

            $this->flash('error', 'Invalid security token.');

            redirect($this->hubPath('sync'));

        }



        if (!Permission::canSyncHub()) {

            $this->flash('error', 'Sync Hub permission required.');

            redirect($this->hubPath('sync'));

        }



        $this->redirectWithWriteResult($this->hubPath('sync'), $callback());

    }



    private function resolveActiveTab(): string

    {

        $tab = strtolower(trim((string) ($_GET['tab'] ?? 'connection')));



        return in_array($tab, self::ALLOWED_TABS, true) ? $tab : 'connection';

    }



    private function hubPath(string $tab): string

    {

        $tab = in_array($tab, self::ALLOWED_TABS, true) ? $tab : 'connection';



        return self::HUB_PATH . '?tab=' . rawurlencode($tab);

    }

}


