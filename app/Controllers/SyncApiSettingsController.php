<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Permission;
use App\Services\ReadOnly\SyncApiSettingsReadService;
use App\Services\Write\SyncApiSettingsWriteService;

class SyncApiSettingsController extends Controller
{
    public function index()
    {
        $this->authorize('sync_api_settings.view');
        ActivityLog::record('sync_api_settings_access', 'Sync/API Settings page viewed');

        $read = new SyncApiSettingsReadService();

        $this->render('sync-api-settings.index', [
            'pageTitle' => 'Sync/API Settings',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Sync/API Settings', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'settings' => $read->formState(),
            'connectionSummary' => $read->connectionSummary(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'canManage' => Permission::can('sync_api_settings.manage'),
        ]);
    }

    public function save()
    {
        $this->authorize('sync_api_settings.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/sync-api-settings');
        }

        $this->redirectWithWriteResult('/sync-api-settings', (new SyncApiSettingsWriteService())->save($_POST));
    }

    public function testConnection()
    {
        $this->authorize('sync_api_settings.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/sync-api-settings');
        }

        $this->redirectWithWriteResult('/sync-api-settings', (new SyncApiSettingsWriteService())->testConnection());
    }

    public function resetDemo()
    {
        $this->authorize('sync_api_settings.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/sync-api-settings');
        }

        $this->redirectWithWriteResult('/sync-api-settings', (new SyncApiSettingsWriteService())->resetToDemo());
    }
}
