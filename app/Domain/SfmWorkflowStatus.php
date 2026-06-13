<?php

namespace App\Domain;

/**
 * SFM v2.4.8 presentation layer — maps internal ibs_status codes to seven SFM buckets.
 * DB values unchanged; labels and filters use this mapper only.
 */
class SfmWorkflowStatus
{
    public const NEW = 'sfm_new';
    public const ACCEPTED = 'sfm_accepted';
    public const PACKED = 'sfm_packed';
    public const DISPATCHED = 'sfm_dispatched';
    public const DELIVERED = 'sfm_delivered';
    public const RETURNED = 'sfm_returned';
    public const CANCELLED = 'sfm_cancelled';

    private const LABELS = [
        self::NEW => 'New',
        self::ACCEPTED => 'Accepted',
        self::PACKED => 'Packed',
        self::DISPATCHED => 'Dispatched',
        self::DELIVERED => 'Delivered',
        self::RETURNED => 'Returned',
        self::CANCELLED => 'Cancelled',
    ];

    /** @var array<string, string> internal ibs_status => SFM bucket */
    private const INTERNAL_TO_BUCKET = [
        'new_order' => self::NEW,
        'order_received' => self::ACCEPTED,
        'packaging' => self::PACKED,
        'shipped' => self::PACKED,
        'dispatch_report_created' => self::DISPATCHED,
        'in_review' => self::DISPATCHED,
        'in_transit' => self::DISPATCHED,
        'out_for_delivery' => self::DISPATCHED,
        'delivered' => self::DELIVERED,
        'hub_returning' => self::RETURNED,
        'hub_return' => self::RETURNED,
        'order_returning' => self::RETURNED,
        'cancelled' => self::CANCELLED,
        'hold' => self::CANCELLED,
        'delivery_stop' => self::CANCELLED,
    ];

    /** @var array<int, string> */
    private const CARD_ORDER = [
        self::NEW,
        self::ACCEPTED,
        self::PACKED,
        self::DISPATCHED,
        self::DELIVERED,
        self::RETURNED,
        self::CANCELLED,
    ];

    public static function bucketForInternal(string $internalCode, bool $inIncludedDispatch = false): string
    {
        $normalized = OrderWorkflowStatus::normalize(trim($internalCode));
        if ($inIncludedDispatch || $normalized === 'dispatch_report_created') {
            return self::DISPATCHED;
        }

        return self::INTERNAL_TO_BUCKET[$normalized] ?? self::NEW;
    }

    public static function label(string $bucketOrInternal): string
    {
        $code = trim($bucketOrInternal);
        if (isset(self::LABELS[$code])) {
            return self::LABELS[$code];
        }

        $bucket = self::bucketForInternal($code);

        return self::LABELS[$bucket] ?? $code;
    }

    public static function isKnownBucket(string $code): bool
    {
        return isset(self::LABELS[trim($code)]);
    }

    /**
     * @return array<int, string>
     */
    public static function internalCodesForBucket(string $sfmBucket): array
    {
        $sfmBucket = trim($sfmBucket);
        if (!isset(self::LABELS[$sfmBucket])) {
            return OrderWorkflowStatus::statusCodesIncludingLegacy($sfmBucket);
        }

        $codes = [];
        foreach (self::INTERNAL_TO_BUCKET as $internal => $bucket) {
            if ($bucket === $sfmBucket) {
                $codes[] = $internal;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public static function releaseStatusCards(): array
    {
        $cards = [];
        foreach (self::CARD_ORDER as $code) {
            $cards[] = ['code' => $code, 'label' => self::LABELS[$code]];
        }

        return $cards;
    }

    /**
     * Aggregate internal stage counts into SFM bucket counts.
     *
     * @param array<string, int> $internalCounts
     * @return array<string, int>
     */
    public static function aggregateCounts(array $internalCounts, array $dispatchIncludedOrderIds = []): array
    {
        $aggregated = [];
        foreach (self::CARD_ORDER as $bucket) {
            $aggregated[$bucket] = 0;
        }

        foreach ($internalCounts as $internalCode => $count) {
            $bucket = self::bucketForInternal((string) $internalCode);
            $aggregated[$bucket] = ($aggregated[$bucket] ?? 0) + (int) $count;
        }

        return $aggregated;
    }

    /**
     * @return array<int, array{key: string, label: string, codes: array<int, string>}>
     */
    public static function releaseStatusCardGroups(): array
    {
        return [
            [
                'key' => 'sfm',
                'label' => 'SFM Workflow',
                'codes' => self::CARD_ORDER,
            ],
        ];
    }
}
