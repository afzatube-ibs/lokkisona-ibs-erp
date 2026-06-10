<?php

namespace App\Domain;

/**
 * ERP-local IBS product category (supplier_product_category) — not OpenCart category.
 */
class ProductControlIbsCategory
{
    public const MAX_LENGTH = 120;

    /** @var list<string> */
    public const SUGGESTED = [
        'Feeding',
        'Travel Gear',
        'Toys',
        'Bathing',
        'Furniture',
        'Safety',
        'Learning',
        'Accessories',
    ];

    /**
     * @param array<int, string> $fromDatabase
     *
     * @return list<string>
     */
    public static function mergeOptions(array $fromDatabase): array
    {
        $merged = array_merge(self::SUGGESTED, $fromDatabase);
        $seen = [];
        $out = [];

        foreach ($merged as $name) {
            $normalized = self::normalize($name);
            if ($normalized === null) {
                continue;
            }
            $key = strtolower($normalized);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $normalized;
        }

        usort($out, static fn (string $a, string $b): int => strcasecmp($a, $b));

        return $out;
    }

    public static function normalize(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/\s+/u', ' ', $raw) ?? $raw;
        if (strlen($raw) > self::MAX_LENGTH) {
            $raw = substr($raw, 0, self::MAX_LENGTH);
        }

        return $raw;
    }

    /**
     * Soft duplicate prevention: reuse canonical spelling when case-insensitive match exists.
     *
     * @param array<int, string> $existingNames
     *
     * @return array{value: ?string, warning: ?string}
     */
    public static function resolve(?string $raw, array $existingNames): array
    {
        $normalized = self::normalize($raw);
        if ($normalized === null) {
            return ['value' => null, 'warning' => null];
        }

        $canonical = self::findCanonical($normalized, $existingNames);
        if ($canonical !== null && strcasecmp($canonical, $normalized) !== 0) {
            return [
                'value' => $canonical,
                'warning' => 'IBS Category saved as "' . $canonical . '" to match an existing category name.',
            ];
        }

        return ['value' => $normalized, 'warning' => null];
    }

    /**
     * @param array<int, string> $existingNames
     */
    public static function findCanonical(string $name, array $existingNames): ?string
    {
        foreach (self::mergeOptions($existingNames) as $existing) {
            if (strcasecmp($existing, $name) === 0) {
                return $existing;
            }
        }

        return null;
    }
}
