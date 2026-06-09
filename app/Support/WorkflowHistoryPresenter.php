<?php

namespace App\Support;

use App\Domain\OrderWorkflowStatus;
use App\Repositories\UserRepository;

/**
 * Formats order_workflow_histories rows for timeline UI.
 */
class WorkflowHistoryPresenter
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function formatRow(array $row, ?UserRepository $users = null): array
    {
        $fromStatus = (string) ($row['from_status'] ?? '');
        $toStatus = (string) ($row['to_status'] ?? '');
        $rawNote = trim((string) ($row['action_note'] ?? ''));
        $parsed = self::parseNote($rawNote);

        return [
            'from_label' => OrderWorkflowStatus::label($fromStatus),
            'to_label' => OrderWorkflowStatus::label($toStatus),
            'action_label' => self::actionLabel($fromStatus, $toStatus, $parsed['action_key'], $rawNote),
            'action_key' => $parsed['action_key'],
            'action_note' => $parsed['display_note'],
            'batch_reference' => $parsed['batch_reference'],
            'changed_by' => self::resolveChangedByLabel($row, $users),
            'changed_at' => (string) ($row['changed_at'] ?? ''),
        ];
    }

    /**
     * @return array{action_key: ?string, display_note: string, batch_reference: ?string}
     */
    public static function parseNote(string $note): array
    {
        $actionKey = null;
        $displayNote = $note;

        if (preg_match('/\[action:([^\]]+)\]/', $note, $matches)) {
            $actionKey = trim($matches[1]);
            $displayNote = trim(preg_replace('/\[action:[^\]]+\]\s*/', '', $note));
        }

        $batchReference = null;
        if (str_starts_with($displayNote, 'Dispatch Report ')) {
            $batchReference = trim(substr($displayNote, strlen('Dispatch Report ')));
        }

        if (str_starts_with($displayNote, '[note] ')) {
            $displayNote = trim(substr($displayNote, strlen('[note] ')));
        }

        return [
            'action_key' => $actionKey !== '' ? $actionKey : null,
            'display_note' => $displayNote !== '' ? $displayNote : '—',
            'batch_reference' => $batchReference !== '' ? $batchReference : null,
        ];
    }

    private static function actionLabel(string $fromStatus, string $toStatus, ?string $actionKey, string $rawNote = ''): string
    {
        if ($actionKey === 'create_dispatch_report') {
            return 'Create Dispatch Report';
        }

        if ($actionKey === 'resume') {
            return 'Resume Order Received';
        }

        if ($actionKey === 'add_note' || str_starts_with($actionKey ?? '', 'note')) {
            return 'Add note';
        }

        $from = OrderWorkflowStatus::normalize($fromStatus);
        $to = OrderWorkflowStatus::normalize($toStatus);

        if ($from === $to && $to !== '') {
            return str_starts_with($rawNote, '[note]') ? 'Add note' : 'Note';
        }

        if ($actionKey !== null && $actionKey !== '') {
            $parts = explode('|', $actionKey, 2);
            if (count($parts) === 2) {
                return OrderWorkflowStatus::rowActionLabel($parts[0], $parts[1]);
            }
        }

        return OrderWorkflowStatus::rowActionLabel($from, $to);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function resolveChangedByLabel(array $row, ?UserRepository $users): string
    {
        $changedBy = $row['changed_by'] ?? null;
        if ($changedBy === null || $changedBy === '') {
            return '—';
        }

        if (is_string($changedBy) && !ctype_digit($changedBy)) {
            return $changedBy;
        }

        $userId = (int) $changedBy;
        if ($userId <= 0) {
            return '—';
        }

        $users = $users ?? new UserRepository();
        if (!$users->tableExists()) {
            return '#' . $userId;
        }

        $user = $users->findById($userId);
        if ($user === null) {
            return '#' . $userId;
        }

        $username = trim((string) ($user['username'] ?? ''));

        return $username !== '' ? $username : ('#' . $userId);
    }
}
