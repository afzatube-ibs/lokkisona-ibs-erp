<?php

namespace App\Domain;

class ProductControlHistoryNote
{
    /**
     * @return array{field: string, type: string, delta: string, note: string}
     */
    public static function parse(string $rawNote): array
    {
        $rawNote = trim($rawNote);
        $defaults = [
            'field' => '',
            'type' => '',
            'delta' => '',
            'note' => $rawNote,
        ];

        if (!preg_match('/^\[field:([^|]+)\|type:([^|]+)\|delta:([^\]]+)\]\s*(.*)$/s', $rawNote, $matches)) {
            return $defaults;
        }

        return [
            'field' => trim($matches[1]),
            'type' => trim($matches[2]),
            'delta' => trim($matches[3]),
            'note' => trim($matches[4]),
        ];
    }

    public static function format(string $field, string $type, string $delta, string $userNote = ''): string
    {
        $prefix = '[field:' . $field . '|type:' . $type . '|delta:' . $delta . ']';
        $userNote = trim($userNote);

        return $userNote !== '' ? $prefix . ' ' . $userNote : $prefix;
    }

    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            'supplier_cost', 'product_cost' => 'Rate',
            'vendor_stock' => 'IBS Stock',
            'supplier_model' => 'IBS Model',
            default => $field !== '' ? $field : '—',
        };
    }

    public static function typeLabel(string $type): string
    {
        return match (strtolower(trim($type))) {
            'direct' => 'Direct',
            'fixed_plus' => '+ Fixed Amount',
            'fixed_minus' => '− Fixed Amount',
            'percent_plus' => '+ Percentage',
            'percent_minus' => '− Percentage',
            'increase' => 'Increase',
            'decrease' => 'Decrease',
            'workspace_save' => 'Workspace save',
            default => $type !== '' ? $type : '—',
        };
    }
}
