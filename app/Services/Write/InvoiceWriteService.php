<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\InvoiceItemWriteRepository;
use App\Repositories\Write\InvoiceWriteRepository;
use App\Repositories\Write\OrderItemWriteRepository;
use App\Repositories\Write\OrderWriteRepository;

class InvoiceWriteService
{
    private InvoiceWriteRepository $invoices;
    private InvoiceItemWriteRepository $invoiceItems;
    private OrderWriteRepository $orders;
    private OrderItemWriteRepository $orderItems;

    public function __construct(
        ?InvoiceWriteRepository $invoices = null,
        ?InvoiceItemWriteRepository $invoiceItems = null,
        ?OrderWriteRepository $orders = null,
        ?OrderItemWriteRepository $orderItems = null
    ) {
        $this->invoices = $invoices ?? new InvoiceWriteRepository();
        $this->invoiceItems = $invoiceItems ?? new InvoiceItemWriteRepository();
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->orderItems = $orderItems ?? new OrderItemWriteRepository();
    }

    public function generateFromOrder(array $input): WriteResult
    {
        if (!WriteGate::invoicePrinting()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $orderId = (int) ($input['order_id'] ?? 0);
        if ($orderId <= 0) {
            return WriteResult::fail('Order ID is required.');
        }

        $invoiceType = trim((string) ($input['invoice_type'] ?? 'packing_slip'));
        if (!in_array($invoiceType, ['customer_invoice', 'packing_slip'], true)) {
            return WriteResult::fail('Invoice type must be customer_invoice or packing_slip.');
        }

        $order = $this->orders->find($orderId);
        if ($order === null) {
            return WriteResult::fail('Order not found.');
        }

        $existing = $this->invoices->findByOrderAndType($orderId, $invoiceType);
        if ($existing !== null) {
            return WriteResult::ok('Invoice already exists for this order.', (int) $existing['invoice_id']);
        }

        $lines = $this->orderItems->tableExists()
            ? $this->fetchOrderItems($orderId)
            : [];

        $invoiceTotal = round((float) ($order['order_total'] ?? 0), 2);
        $invoiceRef = strtoupper(substr($invoiceType, 0, 1)) . '-INV-' . ($order['order_reference'] ?? $orderId) . '-' . date('YmdHis');
        $invoiceId = $this->invoices->create([
            'invoice_reference' => $invoiceRef,
            'order_id' => $orderId,
            'manual_order_id' => null,
            'business_source_id' => $order['business_source_id'] ?? null,
            'invoice_type' => $invoiceType,
            'customer_name' => $order['customer_name'] ?? null,
            'invoice_total' => $invoiceTotal,
            'invoice_status' => 'issued',
            'issued_by' => null,
        ]);

        foreach ($lines as $line) {
            $this->invoiceItems->create([
                'invoice_id' => $invoiceId,
                'product_id' => $line['product_id'] ?? null,
                'product_variant_id' => $line['product_variant_id'] ?? null,
                'product_name' => (string) ($line['product_name'] ?? 'Item'),
                'variant_label' => $line['variant_label'] ?? null,
                'quantity' => (int) ($line['quantity'] ?? 1),
                'unit_price' => round((float) ($line['selling_price'] ?? 0), 2),
                'line_total' => round((float) ($line['line_total'] ?? 0), 2),
            ]);
        }

        ActivityLog::record('invoice_generated', 'ERP invoice generated from order snapshot', [
            'invoice_id' => $invoiceId,
            'order_id' => $orderId,
            'invoice_type' => $invoiceType,
        ]);

        return WriteResult::ok('Invoice generated from order snapshot.', $invoiceId);
    }

    private function fetchOrderItems(int $orderId): array
    {
        $table = config('database.prefix', 'ibs_') . 'order_items';
        $sql = 'SELECT * FROM `' . str_replace('`', '``', $table) . '` WHERE order_id = :order_id ORDER BY order_item_id ASC';
        $pdo = \App\Database\Connection::pdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(['order_id' => $orderId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
