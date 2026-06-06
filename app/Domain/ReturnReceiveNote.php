<?php

namespace App\Domain;

class ReturnReceiveNote
{
    /**
     * @param array{
     *   return_type: string,
     *   return_reason: string,
     *   order_id?: int,
     *   order_reference?: string,
     *   consignment_id?: string,
     *   dispatch_report_reference?: string,
     *   verification_note?: string,
     *   received_confirmation: string,
     *   supplier_condition?: string|null,
     *   supplier_note?: string,
     *   owner_note?: string
     * } $data
     */
    public static function build(array $data): string
    {
        $parts = [
            'Return Received',
            'ERP Order: ' . (int) ($data['order_id'] ?? 0),
            'Order Ref: ' . (trim((string) ($data['order_reference'] ?? '')) !== '' ? trim((string) $data['order_reference']) : '-'),
            'Consignment: ' . (trim((string) ($data['consignment_id'] ?? '')) !== '' ? trim((string) $data['consignment_id']) : '-'),
            'Dispatch Snapshot: ' . (trim((string) ($data['dispatch_report_reference'] ?? '')) !== '' ? trim((string) $data['dispatch_report_reference']) : '-'),
            'Destination: ' . ReturnReceiveType::destinationLabel($data['return_type']),
            'Type: ' . ReturnReceiveType::label($data['return_type']),
            'Reason: ' . ReturnReceiveReason::label($data['return_reason']),
            'Verification Note: ' . (trim((string) ($data['verification_note'] ?? '')) !== '' ? trim((string) $data['verification_note']) : '-'),
            'Received: ' . ReturnReceivePhysicalConfirmation::label($data['received_confirmation']),
        ];

        if (ReturnReceiveType::isSupplierReturn($data['return_type'])) {
            $condition = (string) ($data['supplier_condition'] ?? '');
            $parts[] = 'Condition: ' . ($condition !== '' ? ReturnReceiveCondition::label($condition) : '-');
            $parts[] = 'Supplier Note: ' . (trim((string) ($data['supplier_note'] ?? '')) !== '' ? trim((string) $data['supplier_note']) : '-');
        } else {
            $parts[] = 'Condition: N/A';
            $parts[] = 'Supplier Note: N/A';
        }

        $parts[] = 'Owner Note: ' . (trim((string) ($data['owner_note'] ?? '')) !== '' ? trim((string) $data['owner_note']) : '-');
        $parts[] = 'Stage: receive confirmation only';

        return implode(' | ', $parts);
    }

    /**
     * @return array{
     *   erp_order_id: ?string,
     *   order_reference: ?string,
     *   consignment_id: ?string,
     *   dispatch_snapshot: ?string,
     *   destination: ?string,
     *   type: ?string,
     *   reason: ?string,
     *   verification_note: ?string,
     *   received: ?string,
     *   condition: ?string,
     *   condition_code: ?string,
     *   supplier_note: ?string,
     *   owner_note: ?string
     * }
     */
    public static function parse(string $actionNote): array
    {
        $actionNote = trim($actionNote);
        $result = [
            'erp_order_id' => null,
            'order_reference' => null,
            'consignment_id' => null,
            'dispatch_snapshot' => null,
            'destination' => null,
            'type' => null,
            'reason' => null,
            'verification_note' => null,
            'received' => null,
            'condition' => null,
            'condition_code' => null,
            'supplier_note' => null,
            'owner_note' => null,
        ];

        if ($actionNote === '' || !str_starts_with($actionNote, 'Return Received |')) {
            return self::parseLegacy($actionNote, $result);
        }

        foreach ([
            'erp_order_id' => 'ERP Order',
            'order_reference' => 'Order Ref',
            'consignment_id' => 'Consignment',
            'dispatch_snapshot' => 'Dispatch Snapshot',
            'destination' => 'Destination',
            'type' => 'Type',
            'reason' => 'Reason',
            'verification_note' => 'Verification Note',
            'received' => 'Received',
            'condition' => 'Condition',
            'supplier_note' => 'Supplier Note',
            'owner_note' => 'Owner Note',
        ] as $key => $label) {
            if (preg_match('/' . preg_quote($label, '/') . ':\s*([^|]+)/', $actionNote, $match)) {
                $result[$key] = trim($match[1]);
            }
        }

        if (preg_match('/Verify:\s*([^|]+)/', $actionNote, $legacyVerify)) {
            $result['verification_note'] = trim($legacyVerify[1]);
        }

        if (($result['condition'] ?? '') !== null && $result['condition'] !== '-' && $result['condition'] !== 'N/A') {
            $result['condition_code'] = self::conditionCodeFromLabel((string) $result['condition']);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private static function parseLegacy(string $actionNote, array $result): array
    {
        if (!str_starts_with($actionNote, 'Return Received |')) {
            return $result;
        }

        if (preg_match('/Type:\s*([^|]+)/', $actionNote, $typeMatch)) {
            $result['type'] = trim($typeMatch[1]);
        }

        if (preg_match('/Condition:\s*([^|]+)/', $actionNote, $conditionMatch)) {
            $conditionLabel = trim($conditionMatch[1]);
            $result['condition'] = $conditionLabel;
            $result['condition_code'] = self::conditionCodeFromLabel($conditionLabel);
        }

        if (preg_match('/Note:\s*(.+)$/s', $actionNote, $noteMatch)) {
            $result['supplier_note'] = trim($noteMatch[1]);
        }

        return $result;
    }

    private static function conditionCodeFromLabel(string $label): ?string
    {
        foreach (ReturnReceiveCondition::all() as $code) {
            if (strcasecmp(ReturnReceiveCondition::label($code), $label) === 0) {
                return $code;
            }
        }

        $legacy = [
            'damaged' => ReturnReceiveCondition::BROKEN,
            'missing' => ReturnReceiveCondition::BROKEN,
            'need owner review' => ReturnReceiveCondition::BROKEN,
        ];
        foreach ($legacy as $legacyLabel => $code) {
            if (strcasecmp($legacyLabel, strtolower($label)) === 0) {
                return $code;
            }
        }

        return null;
    }
}
