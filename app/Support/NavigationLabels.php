<?php

namespace App\Support;

class NavigationLabels
{
    /**
     * @return array{pageTitle: string, breadcrumbs: array<int, array<string, mixed>>}|null
     */
    public static function resolve(string $path, array $query = []): ?array
    {
        $path = rtrim($path, '/') ?: '/';
        $reportKey = trim((string) ($query['report'] ?? ''));

        $reportTitles = [
            'supplier_ledger' => 'Supplier Ledger',
            'dispatch_payable' => 'Dispatch Summary',
            'product_dispatch' => 'Product Summary',
            'supplier_statement' => 'Supplier Summary',
            'hub_return' => 'Return Summary',
            'product_sales' => 'Sales Report',
            'monthly_payable' => 'Balance Sheet',
        ];

        $reportParents = [
            'supplier_ledger' => ['Accounts', 'Supplier Ledger'],
            'dispatch_payable' => ['Fulfillment', 'Daily Dispatch'],
            'hub_return' => ['Fulfillment', 'Returns'],
            'supplier_statement' => ['Accounts', 'Supplier Ledger'],
            'product_dispatch' => ['Future Plan', 'Product Summary'],
            'product_sales' => ['Future Plan', 'Sales'],
            'monthly_payable' => ['Accounts', 'Balance Summary'],
        ];

        if ($path === '/reports' && $reportKey !== '' && isset($reportTitles[$reportKey])) {
            $title = $reportTitles[$reportKey];
            $parent = $reportParents[$reportKey] ?? ['Future Plan', $title];

            return [
                'pageTitle' => $title,
                'breadcrumbs' => self::crumbs($parent[0], $parent[1] ?? $title),
            ];
        }

        $map = [
            '/dashboard' => ['Dashboard', 'Dashboard', 'Dashboard'],
            '/order-workflow' => ['Orders', 'Order List', 'Order List'],
            '/manual-orders' => ['Future Plan', 'Sales', 'Sales'],
            '/dispatch-reports' => ['Fulfillment', 'Daily Dispatch', 'Daily Dispatch'],
            '/return-receive' => ['Fulfillment', 'Returns', 'Returns'],
            '/product-control' => ['Catalog', 'Products', 'Products'],
            '/supplier-payables' => ['Accounts', 'Supplier Payments', 'Supplier Payments'],
            '/supplier-opening-balances' => ['Accounts', 'Balance Summary', 'Balance Summary'],
            '/suppliers' => ['Future Plan', 'Suppliers', 'Suppliers'],
            '/business-sources' => ['Settings', 'Business Sources', 'Business Sources'],
            '/users' => ['Settings', 'Users', 'Users'],
            '/roles-permissions' => ['Settings', 'Roles', 'Roles'],
            '/sync-api-settings' => ['Settings', 'Sync & Mapping', 'Sync & Mapping'],
            '/sync-preview' => ['Future Plan', 'Sync Preview', 'Sync Preview'],
            '/status-mapping' => ['Future Plan', 'Status Mapping', 'Status Mapping'],
            '/activity-log' => ['Settings', 'Activity Log', 'Activity Log'],
            '/health' => ['Settings', 'Health Check', 'Health Check'],
            '/database-safety' => ['Future Plan', 'Database Safety', 'Database Safety'],
            '/version' => ['Settings', 'Version', 'Version'],
            '/settlements' => ['Future Plan', 'Settlements', 'Settlements'],
            '/supplier-tools' => ['Future Plan', 'Supplier Tools', 'Supplier Tools'],
            '/invoice-printing' => ['Future Plan', 'Invoice Printing', 'Invoice Printing'],
        ];

        if (!isset($map[$path])) {
            if (str_starts_with($path, '/dispatch-report')) {
                return [
                    'pageTitle' => 'Daily Dispatch',
                    'breadcrumbs' => self::crumbs('Fulfillment', 'Daily Dispatch'),
                ];
            }

            return null;
        }

        [$section, $crumb, $title] = $map[$path];

        return [
            'pageTitle' => $title,
            'breadcrumbs' => self::crumbs($section, $crumb),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function crumbs(string $section, string $active): array
    {
        if ($section === $active) {
            return [
                ['label' => $active, 'active' => true],
            ];
        }

        return [
            ['label' => $section, 'active' => false],
            ['label' => $active, 'active' => true],
        ];
    }
}
