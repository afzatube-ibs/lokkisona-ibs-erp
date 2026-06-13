<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Services\ReadOnly\SyncApiSettingsReadService;
use App\Services\Write\SyncApiSettingsWriteService;
use App\Support\SyncRequestGuard;

class SyncApiSettingsController extends Controller
{
    public function index()
    {
        $this->authorize('sync_api_settings.view');
        ActivityLog::record('sync_api_settings_access', 'Sync Settings page viewed');

        $read = new SyncApiSettingsReadService();

        $this->render('sync-api-settings.index', [
            'pageTitle' => 'Sync Settings',
            'breadcrumbs' => [
                ['label' => 'Settings', 'active' => false],
                ['label' => 'Sync Settings', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'settings' => $read->formState(),
            'connectionSummary' => $read->connectionSummary(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'canManage' => Permission::canSyncHub(),
            'canSyncHub' => Permission::canSyncHub(),
            'canResetProductSync' => Permission::can('sync_preview.manage'),
            'productWriteGateReady' => WriteGate::productSyncImport()['ready'],
            'queueMapping' => $read->queueMappingState(),
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

        if (!SyncRequestGuard::acquire()) {
            $this->flash('error', SyncRequestGuard::busyMessage());
            redirect('/sync-api-settings');
        }

        try {
            $this->redirectWithWriteResult('/sync-api-settings', (new SyncApiSettingsWriteService())->testConnection());
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
            redirect('/sync-api-settings');
        }

        $this->redirectWithWriteResult('/sync-api-settings', (new SyncApiSettingsWriteService())->resetToDemo());
    }

    public function loadQueueStatuses()
    {
        $this->authorize('sync_api_settings.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/sync-api-settings');
        }

        if (!SyncRequestGuard::acquire()) {
            $this->flash('error', SyncRequestGuard::busyMessage());
            redirect('/sync-api-settings');
        }

        try {
            $this->redirectWithWriteResult('/sync-api-settings', (new SyncApiSettingsWriteService())->loadQueueStatuses());
        } finally {
            SyncRequestGuard::release();
        }
    }

    public function saveQueueMappings()
    {
        $this->authorize('sync_api_settings.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/sync-api-settings');
        }

        if (!Permission::canSyncHub()) {
            $this->flash('error', 'Sync Hub permission required to save queue mappings.');
            redirect('/sync-api-settings');
        }

        $this->redirectWithWriteResult('/sync-api-settings', (new SyncApiSettingsWriteService())->saveQueueMappings($_POST));
    }
}
