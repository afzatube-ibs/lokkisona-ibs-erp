<?php

namespace App\Domain;

class OrderWorkflowStatus
{
    public const RESUME_ACTION = '_resume';

    public const DISPATCH_DEV_NOTE = 'v0.4.4.0 temporary status-only gate when dispatch tables are missing. When migration 0006 is applied, use Dispatch Reports (v0.4.5.0) to create the daily batch and cost snapshot.';

    public const COURIER_FLOW_NOTE = 'Courier flow continues by status mapping. Supplier has no manual Out For Delivery / Delivered action.';

    public const DISPATCH_REPORT_REDIRECT_NOTE = 'Create Dispatch Report from Dispatch Reports page.';

    public const OUT_FOR_DELIVERY_NOTE = 'Courier/PIT status reflection only. Supplier does not manage courier stages.';

    public const SYNC_IMPORT_RULE_NOTE = 'ERP must sync only supplier-handled orders based on PIT/OpenCart order status mapping. Do not import all product orders just because the product is mapped.';

    private const LABELS = [
        'new_order' => 'New Order',
        'order_received' => 'Order Received',
        'packaging' => 'Packaging',
        'shipped' => 'Shipped',
        'dispatch_report_created' => 'Created Report',
        'out_for_delivery' => 'Out For Delivery',
        'delivered' => 'Delivered',
        'hold' => 'Hold',
        'cancelled' => 'Cancelled',
        'delivery_stop' => 'Delivery Stop',
        'hub_return' => 'Hub Return',
        'order_returning' => 'Order Returning',
    ];

    /** Supplier manual actions only. Courier/PIT stages have no supplier buttons in v0.4.4.0. */
    private const TRANSITIONS = [
        'new_order' => ['order_received', 'hold', 'cancelled'],
        'order_received' => ['packaging', 'hold', 'cancelled'],
        'packaging' => ['shipped', 'hold', 'cancelled'],
        'hold' => ['cancelled'],
        'shipped' => ['dispatch_report_created', 'delivery_stop'],
        'dispatch_report_created' => [],
        'out_for_delivery' => ['delivery_stop'],
        'delivery_stop' => ['hub_return'],
        'hub_return' => [],
    ];

    private const ACTION_LABELS = [
        'new_order|order_received' => 'Receive Order',
        'order_received|packaging' => 'Print & Start Packaging',
        'packaging|shipped' => 'Mark as Shipped',
        'shipped|dispatch_report_created' => 'Create Dispatch Batch',
        'shipped|delivery_stop' => 'Delivery Stop',
        'delivery_stop|hub_return' => 'Confirm Hub Return',
        'hold|cancelled' => 'Cancelled',
        'new_order|hold' => 'Hold',
        'order_received|hold' => 'Hold',
        'packaging|hold' => 'Hold',
        'new_order|cancelled' => 'Cancelled',
        'order_received|cancelled' => 'Cancelled',
        'packaging|cancelled' => 'Cancelled',
    ];

    private const CHECKBOX_LABELS = [
        'order_received|packaging' => 'I confirm product checked before packaging.',
        'packaging|shipped' => 'I confirm parcel packed and ready to ship.',
    ];

    private const LEGACY_ALIASES = [
        'confirmed' => 'order_received',
        'processing' => 'packaging',
        'ready_for_dispatch' => 'shipped',
        'courier_return' => 'hub_return',
    ];

    private const NOTES_REQUIRED_TO = [
        'hold',
        'cancelled',
        'delivery_stop',
        'dispatch_report_created',
        'hub_return',
    ];

    private const TERMINAL = [
        'cancelled',
        'delivered',
    ];

    private const RESUME_TARGETS = [
        'order_received',
        'packaging',
    ];

    private const GROUP_ORDER = [
        'new_order',
        'order_received',
        'packaging',
        'hold',
        'shipped',
        'dispatch_report_created',
        'out_for_delivery',
        'delivered',
        'delivery_stop',
        'hub_return',
        'order_returning',
        'cancelled',
    ];

    public static function normalize(string $code): string
    {
        $code = trim($code);

        return self::LEGACY_ALIASES[$code] ?? $code;
    }

    public static function label(string $code): string
    {
        if (self::isResumeAction($code)) {
            return 'Resume Order';
        }

        $normalized = self::normalize($code);

        return self::LABELS[$normalized] ?? $normalized;
    }

    public static function groupDisplayLabel(string $code): string
    {
        if (self::normalize($code) === 'dispatch_report_created') {
            return 'Created Report';
        }

        return self::label($code);
    }

    public static function isKnown(string $code): bool
    {
        if (self::isResumeAction($code)) {
            return true;
        }

        $normalized = self::normalize($code);

        return isset(self::LABELS[$normalized]);
    }

    public static function isResumeAction(string $code): bool
    {
        return trim($code) === self::RESUME_ACTION;
    }

    public static function isTerminal(string $code): bool
    {
        return in_array(self::normalize($code), self::TERMINAL, true);
    }

    public static function requiresNote(string $toStatus): bool
    {
        if (self::isResumeAction($toStatus)) {
            return false;
        }

        return in_array(self::normalize($toStatus), self::NOTES_REQUIRED_TO, true);
    }

    public static function requiresNoteForTransition(string $fromStatus, string $toStatus): bool
    {
        return self::requiresNote($toStatus);
    }

    public static function requiresCheckbox(string $fromStatus, string $toStatus): bool
    {
        $key = self::normalize($fromStatus) . '|' . self::normalize($toStatus);

        return isset(self::CHECKBOX_LABELS[$key]);
    }

    public static function checkboxLabel(string $fromStatus, string $toStatus): ?string
    {
        $key = self::normalize($fromStatus) . '|' . self::normalize($toStatus);

        return self::CHECKBOX_LABELS[$key] ?? null;
    }

    public static function requiresConfirmDialog(string $fromStatus, string $toStatus): bool
    {
        if (self::isResumeAction($toStatus)) {
            return true;
        }

        $from = self::normalize($fromStatus);
        $to = self::normalize($toStatus);

        return in_array($to, self::allowedTransitions($from), true);
    }

    public static function actionLabel(string $fromStatus, string $toStatus): string
    {
        if (self::isResumeAction($toStatus)) {
            return 'Resume Order';
        }

        $from = self::normalize($fromStatus);
        $to = self::normalize($toStatus);
        $key = $from . '|' . $to;

        if (isset(self::ACTION_LABELS[$key])) {
            return self::ACTION_LABELS[$key];
        }

        return self::label($to);
    }

    /** Single-row action button label (may differ from bulk label). */
    public static function rowActionLabel(string $fromStatus, string $toStatus): string
    {
        if (self::isResumeAction($toStatus)) {
            return 'Resume Order Received';
        }

        $from = self::normalize($fromStatus);
        $to = self::normalize($toStatus);

        if ($from === 'new_order' && $to === 'order_received') {
            return 'Receive Order';
        }

        if ($from === 'order_received' && $to === 'packaging') {
            return 'Start Packaging';
        }

        if ($from === 'packaging' && $to === 'shipped') {
            return 'Mark Shipped';
        }

        if ($from === 'delivery_stop' && $to === 'hub_return') {
            return 'Confirm Hub Return';
        }

        return self::actionLabel($fromStatus, $toStatus);
    }

    public static function bulkActionLabel(string $bulkKey): string
    {
        return match ($bulkKey) {
            'bulk_receive' => 'Bulk Receive Order',
            'bulk_packaging' => 'Print & Start Packaging',
            'bulk_shipped' => 'Mark as Shipped',
            'bulk_dispatch' => 'Create Dispatch Report',
            default => 'Bulk action',
        };
    }

    /** @return array<int, string> */
    public static function holdCancelEligibleStatuses(): array
    {
        return ['new_order', 'order_received', 'packaging'];
    }

    public static function canHoldOrCancel(string $status): bool
    {
        return in_array(self::normalize($status), self::holdCancelEligibleStatuses(), true);
    }

    public static function allowedTransitions(string $fromStatus): array
    {
        $from = self::normalize($fromStatus);

        if (self::isTerminal($from)) {
            return [];
        }

        return self::TRANSITIONS[$from] ?? [];
    }

    public static function allowedActionCodes(string $fromStatus): array
    {
        $from = self::normalize($fromStatus);

        if ($from === 'hold') {
            return [self::RESUME_ACTION, 'cancelled'];
        }

        return self::allowedTransitions($from);
    }

    public static function validResumeTargets(): array
    {
        return self::RESUME_TARGETS;
    }

    public static function statusInfoNote(string $statusCode): ?string
    {
        $status = self::normalize($statusCode);

        if ($status === 'dispatch_report_created') {
            return self::COURIER_FLOW_NOTE;
        }

        if ($status === 'out_for_delivery') {
            return self::OUT_FOR_DELIVERY_NOTE;
        }

        if ($status === 'hub_return') {
            return 'Confirm physical return receive on the Return Receive page (v0.4.6.0). Courier flow: Shipped → Delivery Stop → Hub Return.';
        }

        if ($status === 'order_returning') {
            return 'Customer Return to Supplier: confirm receive on Return Receive page when return arrives at supplier (v0.4.6.0). PIT/OpenCart mapping sets this status — not a supplier manual workflow action.';
        }

        return null;
    }

    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        $from = self::normalize($fromStatus);

        if (self::isResumeAction($toStatus)) {
            return $from === 'hold';
        }

        $to = self::normalize($toStatus);

        if ($to === 'new_order') {
            return false;
        }

        return in_array($to, self::allowedTransitions($from), true);
    }

    public static function groupOrder(): array
    {
        return self::GROUP_ORDER;
    }

    public static function mainStages(): array
    {
        return [
            ['code' => 'new_order', 'label' => 'New Order', 'description' => 'Order entered or imported into IBS. Entry point only — supplier must never move an order back to this stage.'],
            ['code' => 'order_received', 'label' => 'Order Received', 'description' => 'Order accepted into Iqbal & Brothers fulfillment. Receive Order action with confirmation.'],
            ['code' => 'packaging', 'label' => 'Packaging', 'description' => 'Start Packaging / Print & Move to Packaging after staff confirms product checked. No invoice generation in v0.4.4.0.'],
            ['code' => 'shipped', 'label' => 'Shipped', 'description' => 'Supplier confirms parcel packed and handed/shipped. Order becomes eligible for daily dispatch report.'],
            ['code' => 'dispatch_report_created', 'label' => 'Dispatch Report Created', 'description' => 'v0.4.4.0 workflow gate with required note/reference. Courier stages continue by status mapping.'],
            ['code' => 'out_for_delivery', 'label' => 'Out For Delivery', 'description' => 'Courier/PIT status reflection only. No supplier manual action.'],
            ['code' => 'delivered', 'label' => 'Delivered', 'description' => 'Terminal successful fulfillment state.'],
        ];
    }

    public static function exceptionStages(): array
    {
        return [
            ['code' => 'hold', 'label' => 'Hold', 'description' => 'Allowed only from New Order, Order Received, or Packaging. Requires reason. Resume Order or Cancelled only.'],
            ['code' => 'cancelled', 'label' => 'Cancelled', 'description' => 'Allowed before completion from New Order, Order Received, Packaging, or Hold. Terminal.'],
            ['code' => 'delivery_stop', 'label' => 'Delivery Stop', 'description' => 'Manual ERP action after Shipped when customer asks to stop delivery. Requires note and confirmation.'],
            ['code' => 'hub_return', 'label' => 'Hub Return', 'description' => 'Shipped → Delivery Stop → Hub Return. Supplier marks Return Received. Vendor Returns module later.'],
            ['code' => 'order_returning', 'label' => 'Order Returning', 'description' => 'From PIT/OpenCart return status mapping. Not a normal supplier manual action in v0.4.4.0.'],
        ];
    }

    public static function stageAccentClass(string $code): string
    {
        return match ($code) {
            'new_order' => 'workflow-accent-primary',
            'order_received' => 'workflow-accent-info',
            'packaging' => 'workflow-accent-purple',
            'shipped' => 'workflow-accent-success',
            'dispatch_report_created' => 'workflow-accent-warn',
            'out_for_delivery' => 'workflow-accent-cyan',
            'delivered' => 'workflow-accent-success',
            'hold' => 'workflow-accent-warn',
            'cancelled' => 'workflow-accent-muted',
            'delivery_stop' => 'workflow-accent-error',
            'hub_return' => 'workflow-accent-warn',
            'order_returning' => 'workflow-accent-error',
            default => 'workflow-accent-muted',
        };
    }
}
