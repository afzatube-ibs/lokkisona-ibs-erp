<?php

namespace App\Domain;

/**
 * v2.5.0 — OpenCart entry mapping: Import as NEW or Ignore only.
 */
class EntryMappingOptions
{
    public const IMPORT_NEW = 'import_new';
    public const IGNORE = 'ignore';

    public const IBS_STATUS_FOR_IMPORT = 'new_order';

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public static function dropdownOptions(): array
    {
        return [
            ['code' => self::IMPORT_NEW, 'label' => 'Import as NEW'],
            ['code' => self::IGNORE, 'label' => 'Ignore'],
        ];
    }

    public static function isValid(string $action): bool
    {
        return in_array(trim($action), [self::IMPORT_NEW, self::IGNORE], true);
    }

    public static function actionFromSavedIbsStatus(?string $ibsStatus, bool $isActive): string
    {
        if (!$isActive) {
            return self::IGNORE;
        }

        $normalized = OrderSyncMappingRules::normalizeIbsStatus((string) $ibsStatus);

        return $normalized === self::IBS_STATUS_FOR_IMPORT ? self::IMPORT_NEW : self::IMPORT_NEW;
    }
}
