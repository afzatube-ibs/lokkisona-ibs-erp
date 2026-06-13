<?php

namespace App\Domain;

class OrderWorkflowStatus
{
    public const RESUME_ACTION = '_resume';

    public const DISPATCH_DEV_NOTE = 'v0.4.4.0 temporary status-only gate when dispatch tables are missing. When migration 0006 is applied, use Dispatch Reports (v0.4.5.0) to create the daily batch and cost snapshot.';

    public const COURIER_FLOW_NOTE = 'Courier flow continues by status mapping. Supplier has no manual Out For Delivery / Delivered action.';

    public const DISPATCH_REPORT_REDIRECT_NOTE = 'Create Daily Dispatch Statement from Daily Dispatch page.';

    public const OUT_FOR_DELIVERY_NOTE = 'Courier/PIT status reflection only. Supplier does not manage courier stages.';

    public const SYNC_IMPORT_RULE_NOTE = 'OpenCart sync maps to New / Received / Packaging / Shipped at import only. After Shipped, IBS workflow owns the order. Product/cost/stock never block order import.';

    private const LABELS = [
        'new_order' => 'New Order',
        'order_received' => 'Order Received',
        'packaging' => 'Packaging',
        'shipped' => 'Shipped',
        'dispatch_report_created' => 'Dispatched',
        'in_review' => 'In Review',
        'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out For Delivery',
        'delivered' => 'Delivered',
        'hold' => 'Hold',
        'cancelled' => 'Cancelled',
        'delivery_stop' => 'Delivery Stop',
        'hub_returning' => 'Hub Returning',
        'hub_return' => 'Hub Return Confirmed',
        'order_returning' => 'Customer Returning',
    ];

    /** Supplier manual actions only. Courier/PIT stages have no supplier buttons in v0.4.4.0. */
    private const TRANSITIONS = [
        'new_order' => ['order_received', 'hold', 'cancelled'],
        'order_received' => ['packaging', 'hold', 'cancelled'],
        'packaging' => ['shipped', 'hold', 'cancelled'],
        'hold' => ['cancelled'],
        'shipped' => ['dispatch_report_created', 'delivery_stop'],
        'dispatch_report_created' => ['delivery_stop'],
        'in_review' => ['delivery_stop'],
        'in_transit' => ['delivery_stop'],
        'out_for_delivery' => ['delivery_stop'],
        'delivery_stop' => ['hub_returning'],
        'hub_returning' => ['hub_return'],
        'hub_return' => [],
    ];

    private const ACTION_LABELS = [
        'new_order|order_received' => 'Receive Order',
        'order_received|packaging' => 'Print & Start Packaging',
        'packaging|shipped' => 'Mark as Shipped',
        'shipped|dispatch_report_created' => 'Create Daily Dispatch',
        'dispatch_report_created|delivery_stop' => 'Delivery Stop',
        'shipped|delivery_stop' => 'Delivery Stop',
        'delivery_stop|hub_returning' => 'Mark Hub Returning',
        'hub_returning|hub_return' => 'Confirm Hub Return',
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
        'ready' => 'order_received',
        'processing' => 'packaging',
        'ready_for_dispatch' => 'shipped',
        'dispatch_ready' => 'dispatch_report_created',
        'created_report' => 'dispatch_report_created',
        'courier_return' => 'hub_return',
        'hub_return_confirmed' => 'hub_return',
        'customer_returning' => 'order_returning',
        'customer_return' => 'order_returning',
        'dispatched' => 'dispatch_report_created',
    ];

    private const NOTES_REQUIRED_TO = [
        'hold',
        'cancelled',
        'delivery_stop',
        'dispatch_report_created',
        'hub_returning',
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
        'in_review',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'delivery_stop',
        'hub_returning',
        'hub_return',
        'order_returning',
        'cancelled',
    ];

    public static function normalize(string $code): string
    {
        $code = trim($code);

        return self::LEGACY_ALIASES[$code] ?? $code;
    }

    /**
     * Canonical status plus any legacy DB aliases that map to it (for SQL filters).
     *
     * @return array<int, string>
     */
    public static function statusCodesIncludingLegacy(string $canonical): array
    {
        return self::statusCodesForBucket($canonical);
    }

    /**
     * All raw ibs_status values that belong to a filter/display bucket.
     *
     * @return array<int, string>
     */
    public static function statusCodesForBucket(string $bucket): array
    {
        if (SfmWorkflowStatus::isKnownBucket($bucket)) {
            return SfmWorkflowStatus::internalCodesForBucket($bucket);
        }

        $bucket = self::normalize($bucket);
        $codes = [$bucket];
        foreach (self::LEGACY_ALIASES as $legacy => $mapped) {
            if ($mapped === $bucket) {
                $codes[] = $legacy;
            }
        }

        return array_values(array_unique($codes));
    }

    /** Resolve list/card filter bucket for an order row. */
    public static function filterBucket(string $rawStatus, bool $inIncludedDispatch): string
    {
        $normalized = self::normalize($rawStatus);
        if ($inIncludedDispatch || $normalized === 'dispatch_report_created') {
            return 'dispatch_report_created';
        }

        return $normalized;
    }

    public static function isKnownBucket(string $bucket): bool
    {
        if (SfmWorkflowStatus::isKnownBucket(trim($bucket))) {
            return true;
        }

        $bucket = self::normalize($bucket);
        if ($bucket === '') {
            return false;
        }

        $known = array_merge(
            self::groupOrder(),
            array_column(self::exceptionStages(), 'code')
        );

        return in_array($bucket, $known, true);
    }

    /** Target list filter after a successful bulk action. */
    public static function bulkActionTargetStatus(string $bulkAction): ?string
    {
        return match (trim($bulkAction)) {
            'bulk_receive' => 'order_received',
            'bulk_packaging' => 'packaging',
            'bulk_shipped' => 'shipped',
            'bulk_dispatch' => 'dispatch_report_created',
            'bulk_resume' => 'order_received',
            'bulk_hold' => 'hold',
            'bulk_cancel' => 'cancelled',
            default => null,
        };
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
        return SfmWorkflowStatus::label($code);
    }

    /** Status cards shown on /order-workflow release UI (SFM v2.4.8). */
    public static function releaseStatusCards(): array
    {
        return SfmWorkflowStatus::releaseStatusCards();
    }

    /**
     * Status card groups on /order-workflow release UI (supplier vs courier-sync phases).
     *
     * @return array<int, array{key: string, label: string, codes: array<int, string>}>
     */
    public static function releaseStatusCardGroups(): array
    {
        return SfmWorkflowStatus::releaseStatusCardGroups();
    }

    /** Exception chips on /order-workflow release UI. */
    public static function releaseExceptionChips(): array
    {
        return [
            ['code' => 'hold', 'label' => 'Hold'],
            ['code' => 'cancelled', 'label' => 'Cancelled'],
            ['code' => 'delivery_stop', 'label' => 'Delivery Stop'],
            ['code' => 'hub_returning', 'label' => 'Hub Returning'],
            ['code' => 'hub_return', 'label' => 'Hub Return Confirmed'],
            ['code' => 'order_returning', 'label' => 'Customer Returning'],
        ];
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

        if ($from === 'shipped' && $to === 'dispatch_report_created') {
            return 'Create Daily Dispatch';
        }

        if ($from === 'delivery_stop' && $to === 'hub_returning') {
            return 'Mark Hub Returning';
        }

        if ($from === 'hub_returning' && $to === 'hub_return') {
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
            'bulk_dispatch' => 'Create Daily Dispatch',
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

        if ($status === 'hub_returning') {
            return 'Pre-dispatch hub return in progress. Next: Confirm Hub Return, then receive on Return List.';
        }

        if ($status === 'hub_return') {
            return 'Hub return confirmed — receive on Return List. No payable or return report (pre-dispatch only).';
        }

        if ($status === 'in_review' || $status === 'in_transit') {
            return self::OUT_FOR_DELIVERY_NOTE;
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
            ['code' => 'dispatch_report_created', 'label' => 'Dispatched', 'description' => 'Financial lock after Daily Dispatch Statement. Courier stages continue forward via sync mapping only.'],
            ['code' => 'in_review', 'label' => 'In Review', 'description' => 'Courier/PIT forward stage after dispatch. No supplier manual action.'],
            ['code' => 'in_transit', 'label' => 'In Transit', 'description' => 'Courier/PIT forward stage after dispatch. No supplier manual action.'],
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
            ['code' => 'hub_returning', 'label' => 'Hub Returning', 'description' => 'Pre-dispatch only: Shipped → Delivery Stop → Hub Returning. No payable impact.'],
            ['code' => 'hub_return', 'label' => 'Hub Return Confirmed', 'description' => 'Pre-dispatch only: confirm hub return then receive on Return List. No return report.'],
            ['code' => 'order_returning', 'label' => 'Customer Returning', 'description' => 'Post-dispatch customer return path. Supplier confirms on Return List → Return Report.'],
        ];
    }

    public static function stageAccentClass(string $code): string
    {
        return match ($code) {
            SfmWorkflowStatus::NEW, 'new_order' => 'workflow-accent-warn',
            SfmWorkflowStatus::ACCEPTED, 'order_received' => 'workflow-accent-success',
            SfmWorkflowStatus::PACKED, 'packaging', 'shipped' => 'workflow-accent-purple',
            SfmWorkflowStatus::DISPATCHED, 'dispatch_report_created', 'in_review', 'in_transit', 'out_for_delivery' => 'workflow-accent-cyan',
            SfmWorkflowStatus::DELIVERED, 'delivered' => 'workflow-accent-success',
            SfmWorkflowStatus::RETURNED, 'hub_returning', 'hub_return', 'order_returning' => 'workflow-accent-error',
            SfmWorkflowStatus::CANCELLED, 'hold', 'cancelled', 'delivery_stop' => 'workflow-accent-muted',
            default => 'workflow-accent-muted',
        };
    }

    /** @return array<int, array{code: string, label: string}> */
    public static function filterStatusOptions(): array
    {
        $options = [['code' => '', 'label' => 'All statuses']];
        foreach (self::releaseStatusCards() as $stage) {
            $options[] = $stage;
        }
        foreach (self::releaseExceptionChips() as $stage) {
            $options[] = $stage;
        }

        return $options;
    }
}
