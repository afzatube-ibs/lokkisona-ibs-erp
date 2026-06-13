<?php

namespace App\Support;

use App\Domain\EntryMappingOptions;
use App\Domain\FinalResultMappingOptions;
use App\Domain\OrderWorkflowStatus;

/**
 * Order sync preview display labels (v1.9.6+).
 * Eligibility is connector queue mapping only — product/cost/stock never affect these labels.
 */
class OrderSyncPreviewPresenter
{
    public static function orderNo(array $row): string
    {
        $ref = trim((string) ($row['source_order_reference'] ?? ''));
        if ($ref !== '') {
            return $ref;
        }

        $id = trim((string) ($row['source_order_id'] ?? ''));

        return $id !== '' ? '#' . $id : '—';
    }

    public static function originOcStatus(array $row): string
    {
        $name = trim((string) ($row['source_status'] ?? ''));

        return $name !== '' ? $name : '—';
    }

    public static function mappedIbsStatusLabel(?string $mappedCode, array $row = []): string
    {
        $entryLabel = trim((string) ($row['entry_action_label'] ?? ''));
        if ($entryLabel !== '') {
            return $entryLabel;
        }

        $resyncLabel = trim((string) ($row['resync_mode_label'] ?? ''));
        if ($resyncLabel !== '') {
            return $resyncLabel;
        }

        $code = trim((string) $mappedCode);
        if ($code === EntryMappingOptions::IBS_STATUS_FOR_IMPORT) {
            return 'Import as NEW';
        }
        if ($code !== '' && FinalResultMappingOptions::isValidTarget($code)) {
            return FinalResultMappingOptions::targetLabel($code);
        }
        if ($code === '') {
            return 'No Mapping';
        }

        return OrderWorkflowStatus::label($code);
    }

    public static function importResultLabel(string $previewStatus): string
    {
        return match ($previewStatus) {
            'eligible' => 'Eligible',
            'snapshot_update' => 'Eligible (Existing Order)',
            'blocked_unmapped' => 'Blocked',
            'blocked_invalid_mapping' => 'Blocked',
            'skipped_missing' => 'Skipped',
            // Legacy preview rows — mapping existed; import allowed under status-mapping-only rule.
            'blocked_not_supplier_handled' => 'Eligible',
            default => 'Blocked',
        };
    }

    public static function importResultDetail(string $previewStatus, array $row = []): string
    {
        if ($previewStatus === 'snapshot_update') {
            return trim((string) ($row['resync_mode'] ?? '')) === 'final_result'
                ? 'Existing order — OpenCart final result will apply (Dispatched+).'
                : 'Already in ERP — snapshot refresh only (supplier workflow protected).';
        }

        return match ($previewStatus) {
            'eligible' => 'Entry mapping matched — imports as NEW in IBS.',
            'blocked_unmapped' => 'Blocked Unmapped Status — map in Sync & Mapping Settings → Entry Status Mapping.',
            'blocked_invalid_mapping' => 'Legacy mapping row — re-save Entry Status Mapping as Import as NEW.',
            'skipped_missing' => 'OpenCart status missing or status id 0 — skipped by sync rules.',
            'blocked_not_supplier_handled' => 'Status mapping matched (legacy preview row).',
            'blocked_demo_order' => 'Demo/test order — skipped.',
            default => 'Blocked by order sync preview rules.',
        };
    }

    public static function isImportablePreviewStatus(string $previewStatus): bool
    {
        return in_array($previewStatus, ['eligible', 'snapshot_update', 'blocked_not_supplier_handled'], true);
    }

    /**
     * @param array<string, int> $counts
     * @return array<string, int>
     */
    public static function labeledPreviewCounts(array $counts): array
    {
        $labels = [
            'fetched' => 'Fetched',
            'eligible' => 'Eligible',
            'updated_snapshot' => 'Existing Order Refresh',
            'blocked_unmapped' => 'Blocked Unmapped Status',
            'blocked_invalid_mapping' => 'Blocked Invalid Mapping',
            'skipped_missing_status' => 'Skipped Missing Status',
            'return_candidates' => 'Return Candidates',
        ];

        $labeled = [];
        foreach ($counts as $key => $value) {
            $label = $labels[$key] ?? ucwords(str_replace('_', ' ', (string) $key));
            $labeled[$label] = (int) $value;
        }

        return $labeled;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function enrichDisplayRow(array $row): array
    {
        $previewStatus = (string) ($row['preview_status'] ?? '');
        $mappedCode = isset($row['mapped_status']) && $row['mapped_status'] !== ''
            ? (string) $row['mapped_status']
            : null;

        if ($previewStatus === 'blocked_unmapped' || $previewStatus === 'skipped_missing') {
            $mappedCode = null;
        }

        $originStatusId = trim((string) ($row['origin_status_id'] ?? ''));
        $originStatusName = trim((string) ($row['origin_status_name'] ?? $row['source_status'] ?? ''));

        return array_merge($row, [
            'order_no' => self::orderNo($row),
            'origin_status_id' => $originStatusId !== '' ? $originStatusId : '—',
            'origin_status_name' => $originStatusName !== '' ? $originStatusName : '—',
            'origin_oc_status' => $originStatusName !== '' ? $originStatusName : self::originOcStatus($row),
            'mapping_matched' => (string) ($row['mapping_matched'] ?? ($mappedCode !== null && $mappedCode !== '' ? 'YES' : 'NO')),
            'mapped_ibs_status' => self::mappedIbsStatusLabel($mappedCode, $row),
            'import_result' => self::importResultLabel($previewStatus),
            'import_result_detail' => self::importResultDetail($previewStatus, $row),
            'importable' => self::isImportablePreviewStatus($previewStatus),
        ]);
    }
}
