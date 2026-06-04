<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class SuppliersController extends Controller
{
    public function index()
    {
        $this->authorize('suppliers.view');
        ActivityLog::record('suppliers_access', 'Suppliers foundation page viewed');

        $this->render('suppliers.index', [
            'pageTitle' => 'Suppliers',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Suppliers', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'primarySupplier' => $this->primarySupplier(),
            'operationPurpose' => $this->operationPurpose(),
            'foundationSections' => $this->foundationSections(),
            'plannedFields' => $this->plannedFields(),
            'accountingTerms' => $this->accountingTerms(),
        ]);
    }

    private function primarySupplier()
    {
        return [
            'name' => 'Iqbal & Brothers',
            'role' => 'Current primary supplier',
            'channel' => 'Default supplier operations with Lokkisona order workflow',
            'summary' => 'Operations start with Iqbal & Brothers, but the architecture stays channel-neutral for other suppliers, sales channels, and businesses.',
        ];
    }

    private function operationPurpose()
    {
        return [
            'Maintain a clear record of each supplier the business buys product from.',
            'Track product cost payable and settlement per supplier without hard-coding one channel.',
            'Connect supplier operations to orders, returns, and payable workflows in future releases.',
            'Stay ready for multiple suppliers, sales channels, manual/offline orders, and multi-business expansion.',
        ];
    }

    private function foundationSections()
    {
        return [
            [
                'title' => 'Future supplier account structure',
                'description' => 'Each supplier will have its own account profile, contact details, payment terms, and status, independent of any single sales channel.',
            ],
            [
                'title' => 'Future payable / settlement link',
                'description' => 'Supplier accounts will link to a payable ledger covering product cost payable, supplier invoices, additional payable, payments made, and net payable to supplier.',
            ],
            [
                'title' => 'Future product cost / stock link',
                'description' => 'Suppliers will connect to product cost and stock records so product cost payable is calculated from real purchase and stock movement data.',
            ],
            [
                'title' => 'Future order fulfillment link',
                'description' => 'Suppliers will link to order fulfillment so each order or dispatch knows which supplier provided the product, across manual, offline, and channel orders.',
            ],
            [
                'title' => 'Future return / damage deduction link',
                'description' => 'Supplier returns and damage will reduce the net payable through a clear return/damage deduction, separate from owner-side returns.',
            ],
            [
                'title' => 'Future multi-supplier / multi-business readiness',
                'description' => 'The structure supports many suppliers, multiple businesses, and multiple sales channels so no single supplier or channel is hard-coded.',
            ],
        ];
    }

    private function plannedFields()
    {
        return [
            'supplier name',
            'contact person',
            'phone',
            'email',
            'address',
            'payment terms',
            'payable balance',
            'status',
            'linked business/channel',
            'created at',
            'updated at',
        ];
    }

    private function accountingTerms()
    {
        return [
            'Product Cost Payable',
            'Supplier Invoice',
            'Additional Payable',
            'Return/Damage Deduction',
            'Payment Made to Supplier',
            'Advance Received from Supplier',
            'Net Payable to Supplier',
        ];
    }
}
