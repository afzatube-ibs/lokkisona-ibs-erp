<?php

namespace App\Repositories\Write;

use App\Models\OrderItem;
use App\Support\SchemaColumnProbe;

class OrderItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return OrderItem::class;
    }

    public function create(array $data): int
    {
        $table = $this->table();
        $columns = [
            'order_id' => $data['order_id'],
            'product_id' => $data['product_id'] ?? null,
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'source_product_id' => $data['source_product_id'] ?? null,
            'product_name' => $data['product_name'],
            'variant_label' => $data['variant_label'] ?? null,
            'quantity' => $data['quantity'],
            'selling_price' => $data['selling_price'],
            'supplier_cost_snapshot' => $data['supplier_cost_snapshot'],
            'line_total' => $data['line_total'],
        ];
        if (!empty($data['source_line_key']) && SchemaColumnProbe::tableHasColumn($table, 'source_line_key', $this->pdo)) {
            $columns['source_line_key'] = $data['source_line_key'];
        }

        $fieldNames = array_keys($columns);
        $placeholders = array_map(static fn (string $name): string => ':' . $name, $fieldNames);
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($table) . '` ('
            . implode(', ', array_map(fn (string $c): string => '`' . $this->escapeIdentifier($c) . '`', $fieldNames))
            . ', created_at) VALUES (' . implode(', ', $placeholders) . ', NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($columns);

        return (int) $this->pdo->lastInsertId();
    }
}
