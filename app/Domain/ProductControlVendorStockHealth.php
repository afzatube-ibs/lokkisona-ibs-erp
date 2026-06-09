<?php

namespace App\Domain;

class ProductControlVendorStockHealth
{
    /**
     * @return array{label: string, warning: string, class: string, status: string}
     */
    public static function evaluate(int $vendorStock, int $lowWarning): array
    {
        if ($vendorStock <= 0) {
            return [
                'label' => 'Out of Stock',
                'warning' => 'Out',
                'class' => 'danger',
                'status' => 'out_of_stock',
            ];
        }

        if ($lowWarning > 0 && $vendorStock <= $lowWarning) {
            return [
                'label' => 'Low Stock',
                'warning' => 'Low',
                'class' => 'warn',
                'status' => 'low_stock',
            ];
        }

        return [
            'label' => 'Healthy',
            'warning' => '',
            'class' => 'ok',
            'status' => 'healthy',
        ];
    }
}
