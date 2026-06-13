<?php

namespace App\Support;

use App\Domain\OrderWorkflowStatus;
use App\Domain\SfmWorkflowStatus;
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
        ?string $dispatchReference,
        ?int $dispatchReportId,
        bool $batchLocked,
        array $productLines,
        ?string $sourceOrderStatus,
        bool $supplierView = false
    ): array {
        $orderId = (int) ($order['order_id'] ?? 0);
        $rawStatus = (string) ($order['ibs_status'] ?? 'new_order');
        $displayStatus = OrderWorkflowStatus::filterBucket($rawStatus, $batchLocked);
        $normalized = OrderWorkflowStatus::normalize($rawStatus);
        $dispatchModuleReady = WriteGate::dispatchReports()['ready'] ?? false;

        $totalQty = 0;
        $totalCost = 0.0;
        $missingCost = false;
        foreach ($productLines as $line) {
            $lineQty = max(1, (int) ($line['quantity'] ?? 0));
            $lineCost = (float) ($line['cost_snapshot'] ?? 0);
            $totalQty += $lineQty;
            $totalCost += $lineCost * $lineQty;
            if ($lineCost <= 0) {
                $missingCost = true;
            }
        }

        $orderNo = self::formatOrderNo($order);
        $batchRef = trim((string) ($dispatchReference ?? ''));
        $viewReportUrl = $batchRef !== ''
            ? url('/dispatch-report/' . rawurlencode($batchRef))
            : (($dispatchReportId ?? 0) > 0
                ? url('/dispatch-reports?report_id=' . (int) $dispatchReportId)
                : null);
        $actions = self::buildActions($displayStatus, $normalized, $batchLocked, $dispatchModuleReady, $viewReportUrl);
        $sfmBucket = SfmWorkflowStatus::bucketForInternal($normalized, $batchLocked);
        $ocOrderId = trim((string) ($order['source_order_id'] ?? ''));
        if ($ocOrderId === '') {
            $ocOrderId = trim((string) ($order['source_order_reference'] ?? ''));
        }

        return [
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'oc_order_id' => $ocOrderId !== '' ? $ocOrderId : $orderNo,
            'order_reference' => (string) ($order['order_reference'] ?? ''),
            'source_order_reference' => trim((string) ($order['source_order_reference'] ?? '')) ?: null,
            'customer_name' => (string) ($order['customer_name'] ?? ''),
            'customer_phone' => trim((string) ($order['customer_phone'] ?? '')) ?: null,
            'customer_address' => trim((string) ($order['customer_address'] ?? '')) ?: null,
            'invoice_cod_amount' => round((float) ($order['order_total'] ?? 0), 2),
            'product_lines' => $productLines,
            'total_quantity' => $totalQty,
            'total_cost_snapshot' => round($totalCost, 2),
            'missing_cost' => $missingCost,
            'hide_cost_column' => $supplierView,
            'fulfillment_status' => $displayStatus,
            'fulfillment_status_label' => SfmWorkflowStatus::label($sfmBucket),
            'sfm_status_label' => SfmWorkflowStatus::label($sfmBucket),
            'fulfillment_status_class' => OrderWorkflowStatus::stageAccentClass($displayStatus),
            'ibs_status_raw' => $normalized,
            'courier_status' => trim((string) ($order['courier_status'] ?? '')) ?: null,
            'consignment_id' => trim((string) ($order['tracking_number'] ?? '')) ?: 'Not Assigned',
            'oc_order_status' => $sourceOrderStatus,
            'dispatch_report_reference' => $dispatchReference,
            'dispatch_report_id' => $dispatchReportId,
            'view_report_url' => $viewReportUrl,
            'created_report_note' => ($batchLocked || $displayStatus === 'dispatch_report_created') && $batchRef !== ''
                ? 'Included in Daily Dispatch Statement ' . $batchRef . '. Sale/cost snapshot locked — workflow actions are locked.'
                : null,
            'batch_locked' => $batchLocked,
            'primary_action' => $actions['primary'],
            'menu_items' => $actions['menu_items'],
            'selectable' => $actions['selectable'],
            'bulk_action_key' => $actions['bulk_action_key'],
            'can_hold_cancel' => $actions['can_hold_cancel'],
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
            if ($chips === [] && $variantId <= 0 && $variantLabel === '') {
                $chips[] = ['label' => 'No option selected', 'meta' => null, 'empty_option' => true];
            }

            $productName = trim((string) ($line['product_name'] ?? $product['product_name'] ?? $product['source_name'] ?? ''));
            if ($productName === '') {
                $productName = $model !== '' ? $model : 'Product';
            }

            $lines[] = [
                'product_name' => $productName,
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
        if (!empty($order['origin_order_status_name'])) {
            $label = trim((string) $order['origin_order_status_name']);

            return $label !== '' ? $label : null;
        }

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
     * @return array{primary: ?array<string, mixed>, menu_items: array<int, array<string, mixed>>, selectable: bool, bulk_action_key: ?string, can_hold_cancel: bool}
     */
    private static function buildActions(
        string $displayStatus,
        string $normalized,
        bool $batchLocked,
        bool $dispatchModuleReady,
        ?string $viewReportUrl = null
    ): array {
        if ($batchLocked || $displayStatus === 'dispatch_report_created') {
            $primary = null;
            if ($viewReportUrl !== null && $viewReportUrl !== '') {
                $primary = [
                    'code' => 'view_report',
                    'label' => 'View Report',
                    'url' => $viewReportUrl,
                    'is_link' => true,
                    'requires_note' => false,
                    'requires_checkbox' => false,
                    'checkbox_label' => null,
                    'requires_confirm' => false,
                    'is_delivery_stop' => false,
                    'is_hub_return' => false,
                    'is_dispatch_create' => false,
                    'menu_only' => false,
                ];
            }

            return [
                'primary' => $primary,
                'menu_items' => self::createdReportMenuItems($normalized, true),
                'selectable' => false,
                'bulk_action_key' => null,
                'can_hold_cancel' => false,
            ];
        }

        if (in_array($displayStatus, ['delivered', 'cancelled', 'hub_return', 'order_returning'], true)) {
            return [
                'primary' => null,
                'menu_items' => self::baseMenuItems($normalized, true, false, false),
                'selectable' => false,
                'bulk_action_key' => null,
                'can_hold_cancel' => false,
            ];
        }

        $primary = null;
        $canHoldCancel = OrderWorkflowStatus::canHoldOrCancel($normalized);
        $canCancelFromHold = $normalized === 'hold';
        $canDeliveryStop = in_array($normalized, ['shipped', 'out_for_delivery'], true);

        if ($normalized === 'hold') {
            $primary = self::actionMeta('hold', OrderWorkflowStatus::RESUME_ACTION, true);
        } else {
            $primaryCode = self::primaryActionCode($normalized, $dispatchModuleReady);
            if ($primaryCode === 'create_dispatch_report') {
                $primary = self::dispatchCreateActionMeta();
            } elseif ($primaryCode !== null && $primaryCode !== 'bulk_dispatch') {
                $primary = self::actionMeta($normalized, $primaryCode, true);
            }
        }

        return [
            'primary' => $primary,
            'menu_items' => self::baseMenuItems($normalized, true, $canHoldCancel, $canDeliveryStop, $canCancelFromHold),
            'selectable' => self::isBulkEligible($normalized, $dispatchModuleReady),
            'bulk_action_key' => self::bulkActionKey($normalized, $dispatchModuleReady),
            'can_hold_cancel' => $canHoldCancel,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function createdReportMenuItems(string $normalized, bool $dispatched = false): array
    {
        $fromStatus = match ($normalized) {
            'delivery_stop' => 'delivery_stop',
            'out_for_delivery' => 'out_for_delivery',
            'shipped' => 'shipped',
            default => 'dispatch_report_created',
        };

        $items = [
            [
                'code' => 'view_timeline',
                'label' => 'View timeline',
                'menu_only' => true,
            ],
            [
                'code' => 'add_note',
                'label' => 'Add note',
                'requires_note' => true,
                'menu_only' => true,
            ],
        ];

        if ($normalized !== 'delivery_stop') {
            $items[] = self::actionMeta($fromStatus, 'delivery_stop', false);
        }

        if (!$dispatched && $normalized === 'delivery_stop') {
            $items[] = self::actionMeta('delivery_stop', 'hub_returning', false);
        }

        if (!$dispatched && $normalized === 'hub_returning') {
            $items[] = self::actionMeta('hub_returning', 'hub_return', false);
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function baseMenuItems(string $fromStatus, bool $includeTimeline, bool $includeHoldCancel, bool $includeDeliveryStop, bool $includeCancelOnly = false): array
    {
        $items = [];
        if ($includeTimeline) {
            $items[] = [
                'code' => 'view_timeline',
                'label' => 'View timeline',
                'menu_only' => true,
            ];
            $items[] = [
                'code' => 'add_note',
                'label' => 'Add note',
                'requires_note' => true,
                'menu_only' => true,
            ];
        }
        if ($includeHoldCancel) {
            $items[] = self::actionMeta($fromStatus, 'hold', false);
            $items[] = self::actionMeta($fromStatus, 'cancelled', false);
        } elseif ($includeCancelOnly) {
            $items[] = self::actionMeta('hold', 'cancelled', false);
        }
        if ($includeDeliveryStop) {
            $items[] = self::actionMeta($fromStatus === 'out_for_delivery' ? 'out_for_delivery' : 'shipped', 'delivery_stop', false);
        }

        return $items;
    }

    private static function primaryActionCode(string $status, bool $dispatchModuleReady): ?string
    {
        return match ($status) {
            'new_order' => 'order_received',
            'order_received' => 'packaging',
            'packaging' => 'shipped',
            'shipped' => $dispatchModuleReady ? 'create_dispatch_report' : 'dispatch_report_created',
            'delivery_stop' => 'hub_returning',
            'hub_returning' => 'hub_return',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function dispatchCreateActionMeta(): array
    {
        return [
            'code' => 'create_dispatch_report',
            'label' => 'Create Daily Dispatch',
            'requires_note' => false,
            'requires_checkbox' => false,
            'checkbox_label' => null,
            'requires_confirm' => true,
            'is_delivery_stop' => false,
            'is_hub_return' => false,
            'is_dispatch_create' => true,
            'is_menu_action' => false,
            'menu_only' => false,
        ];
    }

    private static function isBulkEligible(string $status, bool $dispatchModuleReady): bool
    {
        if ($dispatchModuleReady && $status === 'shipped') {
            return true;
        }

        return in_array($status, ['new_order', 'order_received', 'packaging', 'hold'], true);
    }

    private static function bulkActionKey(string $status, bool $dispatchModuleReady): ?string
    {
        return match ($status) {
            'new_order' => 'bulk_receive',
            'order_received' => 'bulk_packaging',
            'packaging' => 'bulk_shipped',
            'shipped' => $dispatchModuleReady ? 'bulk_dispatch' : null,
            'hold' => 'bulk_resume',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function actionMeta(string $fromStatus, string $toStatus, bool $useRowLabel): array
    {
        $normalizedTo = OrderWorkflowStatus::normalize($toStatus);

        return [
            'code' => $toStatus,
            'label' => $useRowLabel
                ? OrderWorkflowStatus::rowActionLabel($fromStatus, $toStatus)
                : OrderWorkflowStatus::actionLabel($fromStatus, $toStatus),
            'requires_note' => $normalizedTo === 'delivery_stop'
                ? false
                : OrderWorkflowStatus::requiresNoteForTransition($fromStatus, $toStatus),
            'requires_checkbox' => OrderWorkflowStatus::requiresCheckbox($fromStatus, $toStatus),
            'checkbox_label' => OrderWorkflowStatus::checkboxLabel($fromStatus, $toStatus),
            'requires_confirm' => OrderWorkflowStatus::requiresConfirmDialog($fromStatus, $toStatus),
            'is_delivery_stop' => $normalizedTo === 'delivery_stop',
            'is_hub_return' => $normalizedTo === 'hub_return',
            'is_menu_action' => in_array($toStatus, ['view_timeline', 'add_note'], true),
            'menu_only' => false,
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
