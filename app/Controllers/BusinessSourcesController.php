<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class BusinessSourcesController extends Controller
{
    public function index()
    {
        $this->authorize('business_sources.view');
        ActivityLog::record('business_sources_access', 'Business Sources foundation page viewed');

        $this->render('business-sources.index', [
            'pageTitle' => 'Business Sources',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Business Sources', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'currentSource' => $this->currentSource(),
            'primarySupplierRelationship' => $this->primarySupplierRelationship(),
            'foundationSections' => $this->foundationSections(),
            'plannedFields' => $this->plannedFields(),
            'sourceTypes' => $this->sourceTypes(),
            'plannedBusinessSources' => $this->plannedBusinessSources(),
        ]);
    }

    private function currentSource()
    {
        return [
            'name' => 'Lokkisona.com',
            'type' => 'Ecommerce Website',
            'label' => 'Current primary order source',
            'summary' => 'The first workflow starts with Lokkisona.com orders, but the ERP must stay ready for other channels, manual orders, and future business sources.',
        ];
    }

    private function primarySupplierRelationship()
    {
        return [
            'supplier' => 'Iqbal & Brothers',
            'relationship' => 'Current primary supplier relationship',
            'summary' => 'Orders from Lokkisona.com currently connect to Iqbal & Brothers supplier operations, with future support for other suppliers and workflows.',
        ];
    }

    private function foundationSections()
    {
        return [
            [
                'title' => 'Future source / channel structure',
                'description' => 'Each business source will identify its business, channel name, source type, default supplier, and default workflow without hard-coding one website.',
            ],
            [
                'title' => 'Manual / offline order support',
                'description' => 'Manual orders, phone orders, showroom orders, and offline retail orders will be able to enter the same order workflow as ecommerce orders.',
            ],
            [
                'title' => 'Ecommerce channel support',
                'description' => 'Ecommerce websites such as Lokkisona.com will be modeled as channels so orders can keep their source label and workflow routing.',
            ],
            [
                'title' => 'Marketplace / channel support',
                'description' => 'Future marketplaces and external sales channels can be added as separate sources while still connecting to supplier, dispatch, return, and payable operations.',
            ],
            [
                'title' => 'Multi-business readiness',
                'description' => 'The foundation supports future businesses and channels beyond the first Lokkisona workflow, keeping supplier and accounting operations reusable.',
            ],
            [
                'title' => 'Future operations connection',
                'description' => 'Orders will later connect their source to supplier workflow, dispatch, returns, and payable so each channel can route work without custom one-off code.',
            ],
        ];
    }

    private function plannedFields()
    {
        return [
            'business name',
            'channel name',
            'source type',
            'website/domain',
            'order source label',
            'status',
            'default supplier',
            'default workflow',
            'created at',
            'updated at',
        ];
    }

    private function sourceTypes()
    {
        return [
            'Ecommerce Website',
            'Manual Order',
            'Offline Retail',
            'Marketplace',
            'Wholesale',
            'Other',
        ];
    }

    private function plannedBusinessSources()
    {
        return [
            [
                'name' => 'Lokkisona.com',
                'platform' => 'OpenCart',
                'note' => 'Primary ecommerce source — Lokkisona-specific courier mapping and ERP Lokkisona-style invoice later.',
            ],
            [
                'name' => 'Sonamoni.com.bd',
                'platform' => 'WooCommerce',
                'note' => 'Future WooCommerce source — separate courier mapping; manual/external reference entry first; direct sync later.',
            ],
            [
                'name' => 'Manual / Offline Order',
                'platform' => 'Manual',
                'note' => 'External reference order entry — ERP manual invoice later; same supplier workflow/payable/stock logic.',
            ],
        ];
    }
}
