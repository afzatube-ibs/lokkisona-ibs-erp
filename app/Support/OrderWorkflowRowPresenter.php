<?php

namespace App\Support;

use App\Domain\OrderWorkflowStatus;
use App\ReadFoundation\WriteGate;

/**
 * Builds vendor fulfillment table row payloads (cost-only, IBS workflow actions).
 */
class OrderWorkflowRowPresenter
{
    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $productLines
     * @return array<string, mixed>
     */
    public static function buildRow(
        array $order,
        string $displayStatus,
        ?string $dispatchReference,
        bool $batchLocked,
        array $productLines,
        ?string $sourceOrderStatus
    ): array {
        $orderId = (int) ($order['order_id'] ?? 0);
        $rawStatus = (string) ($order['ibs_status'] ?? 'new_order');
        $normalized = OrderWorkflowStatus::normalize($rawStatus);
        $dispatchModuleReady = WriteGate::dispatchReports()['ready'] ?? false;

        $totalQty = 0;
        $totalCost = 0.0;
        $missingCost = false;
        foreach ($productLines as $line) {
            $lineQty = max(1, (int) ($line['quantity'] ?? 0));
            $lineCost = (float) ($line['supplier_cost_snapshot'] ?? 0);
            $totalQty += $lineQty;
            $totalCost += $lineCost * $lineQty;
            if ($lineCost <= 0) {
                $missingCost = true;
            }
        }

        $actions = self::buildActions($displayStatus, $normalized, $batchLocked, $dispatchModuleReady);
        $orderNo = self::formatOrderNo($order);

        return [
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'order_reference' => (string) ($order['order_reference'] ?? ''),
            'source_order_reference' => trim((string) ($order['source_order_reference'] ?? '')) ?: null,
            'customer_name' => (string) ($order['customer_name'] ?? ''),
            'customer_phone' => trim((string) ($order['customer_phone'] ?? '')) ?: null,
            'product_lines' => $productLines,
            'total_quantity' => $totalQty,
            'total_cost_snapshot' => round($totalCost, 2),
            'missing_cost' => $missingCost,
            'fulfillment_status' => $displayStatus,
            'fulfillment_status_label' => OrderWorkflowStatus::groupDisplayLabel($displayStatus),
            'fulfillment_status_class' => OrderWorkflowStatus::stageAccentClass($displayStatus),
            'courier_status' => trim((string) ($order['courier_status'] ?? '')) ?: null,
            'consignment_id' => trim((string) ($order['tracking_number'] ?? '')) ?: null,
            'oc_order_status' => $sourceOrderStatus,
            'dispatch_report_reference' => $dispatchReference,
            'batch_locked' => $batchLocked,
            'primary_action' => $actions['primary'],
            'secondary_actions' => $actions['secondary'],
            'selectable' => $actions['selectable'],
            'bulk_action_key' => $actions['bulk_action_key'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, array<string, mixed>|null> $productsById
     * @param array<int, array<int, array<string, mixed>>> $variantsByProduct
     * @return array<int, array<string, mixed>>
     */
    public static function formatProductLines(array $items, array $productsById, array $variantsByProduct): array
    {
        $lines = [];
        foreach ($items as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $variantId = (int) ($line['product_variant_id'] ?? 0);
            $product = $productId > 0 ? ($productsById[$productId] ?? null) : null;
            $variant = null;
            if ($productId > 0 && $variantId > 0) {
                foreach ($variantsByProduct[$productId] ?? [] as $variantRow) {
                    if ((int) ($variantRow['product_variant_id'] ?? 0) === $variantId) {
                        $variant = $variantRow;
                        break;
                    }
                }
            }

            $model = trim((string) ($variant['supplier_model'] ?? $product['supplier_model'] ?? $product['source_model'] ?? ''));
            if ($model === '') {
                $model = trim((string) ($line['product_name'] ?? 'Product'));
            }

            $rawImage = (string) ($variant['option_image_path'] ?? $product['image_path'] ?? '');
            $qty = max(1, (int) ($line['quantity'] ?? 0));
            $cost = (float) ($line['supplier_cost_snapshot'] ?? 0);
            $variantLabel = trim((string) ($line['variant_label'] ?? ''));

            $chips = [];
            if ($variant !== null) {
                $variantModel = trim((string) ($variant['supplier_model'] ?? ''));
                if ($variantModel !== '' && $variantModel !== $model) {
                    $chips[] = [
                        'label' => $variantModel . '   x' . $qty . ' = ' . number_format($cost, 2),
                        'meta' => self::variantChipMeta($variant, $variantLabel),
                    ];
                }
            }
            if ($variantLabel !== '' && ($chips === [] || !self::chipContainsLabel($chips, $variantLabel))) {
                $chips[] = ['label' => $variantLabel, 'meta' => null];
            }

            $lines[] = [
                'image_url' => $rawImage !== '' ? opencart_media_url($rawImage) : null,
                'model' => $model,
                'quantity' => $qty,
                'cost_snapshot' => $cost,
                'line_cost_total' => round($cost * $qty, 2),
                'option_chips' => $chips,
            ];
        }

        return $lines;
    }

    public static function resolveSourceOrderStatus(array $order, ?array $importHistory = null): ?string
    {
        if (!empty($order['source_order_status'])) {
            $label = trim((string) $order['source_order_status']);

            return $label !== '' ? $label : null;
        }

        if ($importHistory !== null) {
            $note = (string) ($importHistory['action_note'] ?? '');
            if (preg_match('/Source status:\s*([^(\n\r]+)/u', $note, $matches)) {
                $label = trim($matches[1]);
                if ($label !== '' && $label !== '-') {
                    return $label;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $order
     */
    public static function formatOrderNo(array $order): string
    {
        $sourceRef = trim((string) ($order['source_order_reference'] ?? ''));
        if ($sourceRef !== '') {
            return str_starts_with($sourceRef, '#') ? $sourceRef : '#' . $sourceRef;
        }

        $ref = trim((string) ($order['order_reference'] ?? ''));
        if ($ref === '') {
            return '#0';
        }

        return str_starts_with($ref, '#') ? $ref : '#' . $ref;
    }

    /**
     * @return array{primary: ?array<string, mixed>, secondary: array<int, array<string, mixed>>, selectable: bool, bulk_action_key: ?string}
     */
    private static function buildActions(
        string $displayStatus,
        string $normalized,
        bool $batchLocked,
        bool $dispatchModuleReady
    ): array {
        if ($batchLocked || $displayStatus === 'dispatch_report_created') {
            return [
                'primary' => null,
                'secondary' => [],
                'selectable' => false,
                'bulk_action_key' => null,
            ];
        }

        if (in_array($displayStatus, ['delivered', 'cancelled', 'hub_return', 'order_returning'], true)) {
            return [
                'primary' => null,
                'secondary' => [],
                'selectable' => false,
                'bulk_action_key' => null,
            ];
        }

        $allowed = [];
        foreach (OrderWorkflowStatus::allowedActionCodes($normalized) as $toStatus) {
            if ($dispatchModuleReady && $normalized === 'shipped' && $toStatus === 'dispatch_report_created') {
                continue;
            }
            $allowed[] = self::actionMeta($normalized, $toStatus);
        }

        if ($dispatchModuleReady && $normalized === 'shipped') {
            $allowed[] = [
                'code' => 'bulk_dispatch',
                'label' => 'Create Dispatch Batch',
                'requires_note' => false,
                'requires_checkbox' => false,
                'requires_confirm' => true,
                'is_delivery_stop' => false,
                'is_bulk_dispatch' => true,
            ];
        }

        $primaryCode = self::primaryActionCode($normalized, $dispatchModuleReady);
        $primary = null;
        $secondary = [];

        foreach ($allowed as $action) {
            if ($primary === null && ($action['code'] === $primaryCode || ($primaryCode === 'bulk_dispatch' && !empty($action['is_bulk_dispatch'])))) {
                $primary = $action;
            } else {
                $secondary[] = $action;
            }
        }

        if ($primary === null && $allowed !== []) {
            $primary = array_shift($allowed);
            $secondary = $allowed;
        }

        return [
            'primary' => $primary,
            'secondary' => $secondary,
            'selectable' => self::isBulkEligible($normalized, $dispatchModuleReady),
            'bulk_action_key' => self::bulkActionKey($normalized, $dispatchModuleReady),
        ];
    }

    private static function primaryActionCode(string $status, bool $dispatchModuleReady): ?string
    {
        return match ($status) {
            'new_order' => 'order_received',
            'order_received' => 'packaging',
            'packaging' => 'shipped',
            'shipped' => $dispatchModuleReady ? 'bulk_dispatch' : 'dispatch_report_created',
            'delivery_stop' => 'hub_return',
            default => null,
        };
    }

    private static function isBulkEligible(string $status, bool $dispatchModuleReady): bool
    {
        if ($dispatchModuleReady && $status === 'shipped') {
            return true;
        }

        return in_array($status, ['new_order', 'order_received', 'packaging'], true);
    }

    private static function bulkActionKey(string $status, bool $dispatchModuleReady): ?string
    {
        return match ($status) {
            'new_order' => 'bulk_receive',
            'order_received' => 'bulk_packaging',
            'packaging' => 'bulk_shipped',
            'shipped' => $dispatchModuleReady ? 'bulk_dispatch' : null,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function actionMeta(string $fromStatus, string $toStatus): array
    {
        return [
            'code' => $toStatus,
            'label' => OrderWorkflowStatus::actionLabel($fromStatus, $toStatus),
            'requires_note' => OrderWorkflowStatus::requiresNoteForTransition($fromStatus, $toStatus),
            'requires_checkbox' => OrderWorkflowStatus::requiresCheckbox($fromStatus, $toStatus),
            'checkbox_label' => OrderWorkflowStatus::checkboxLabel($fromStatus, $toStatus),
            'requires_confirm' => OrderWorkflowStatus::requiresConfirmDialog($fromStatus, $toStatus),
            'is_delivery_stop' => OrderWorkflowStatus::normalize($toStatus) === 'delivery_stop',
            'is_bulk_dispatch' => false,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $chips
     */
    private static function chipContainsLabel(array $chips, string $label): bool
    {
        foreach ($chips as $chip) {
            if (str_contains((string) ($chip['label'] ?? ''), $label)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $variant
     */
    private static function variantChipMeta(array $variant, string $fallbackLabel): ?string
    {
        $optionName = trim((string) ($variant['option_name'] ?? ''));
        $optionValue = trim((string) ($variant['option_value'] ?? ''));
        if ($optionName !== '' && $optionValue !== '') {
            return $optionName . ': ' . $optionValue;
        }

        return $fallbackLabel !== '' ? $fallbackLabel : null;
    }
}
