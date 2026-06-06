<?php

namespace App\Domain;

class ReturnReceiveCondition
{
    public const REUSABLE = 'reusable';

    public const BROKEN = 'broken';

    private const LABELS = [
        self::REUSABLE => 'Reusable',
        self::BROKEN => 'Broken',
    ];

    private const BADGE_CLASSES = [
        self::REUSABLE => 'badge-ok',
        self::BROKEN => 'badge-warn',
    ];

    private const MEANINGS = [
        self::REUSABLE => 'Product can be used or sold again.',
        self::BROKEN => 'Product received broken.',
    ];

    public static function all(): array
    {
        return [
            self::REUSABLE,
            self::BROKEN,
        ];
    }

    public static function isKnown(string $code): bool
    {
        return isset(self::LABELS[self::normalize($code)]);
    }

    public static function normalize(string $code): string
    {
        return strtolower(trim($code));
    }

    public static function label(string $code): string
    {
        $normalized = self::normalize($code);

        return self::LABELS[$normalized] ?? $normalized;
    }

    public static function badgeClass(string $code): string
    {
        $normalized = self::normalize($code);

        return self::BADGE_CLASSES[$normalized] ?? 'badge-warn';
    }

    public static function meaning(string $code): string
    {
        $normalized = self::normalize($code);

        return self::MEANINGS[$normalized] ?? '';
    }

    /**
     * @return array<int, array{code: string, label: string, meaning: string, badge: string}>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $code) {
            $options[] = [
                'code' => $code,
                'label' => self::label($code),
                'meaning' => self::meaning($code),
                'description' => self::meaning($code),
                'badge' => self::badgeClass($code),
            ];
        }

        return $options;
    }
}
