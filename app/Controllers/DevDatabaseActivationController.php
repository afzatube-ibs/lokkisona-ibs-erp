<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Migration\DevDatabaseActivation;
use App\Migration\MigrationActivationGuide;
use App\Permission;

class DevDatabaseActivationController extends Controller
{
    public function index()
    {
        $this->authorize('dev_db_activation.view');
        ActivityLog::record('dev_db_activation_access', 'Dev DB Activation helper page viewed');

        $this->render('dev-db-activation.index', [
            'pageTitle' => 'Dev DB Activation',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Dev DB Activation', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'databaseStatus' => Database::check(),
            'activationStatus' => DevDatabaseActivation::activationStatus(),
            'tableGroups' => DevDatabaseActivation::tableGroupsWithReadiness(),
            'applyTestFlow' => DevDatabaseActivation::applyTestFlow(),
            'globalBlocked' => DevDatabaseActivation::globalBlocked(),
            'applyOrder' => MigrationActivationGuide::applyOrder(),
        ]);
    }
}
