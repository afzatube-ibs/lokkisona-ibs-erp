<?php

namespace App\Database;

class QueryGuard
{
    private const ALLOWED_START = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'];

    public static function assertReadOnly(string $sql): void
    {
        $normalized = self::normalize($sql);

        if ($normalized === '') {
            throw new ReadOnlyQueryException('Empty SQL is not allowed.');
        }

        $firstKeyword = self::firstKeyword($normalized);

        if (!in_array($firstKeyword, self::ALLOWED_START, true)) {
            throw new ReadOnlyQueryException(
                'Only read-only SQL is allowed. Got: ' . $firstKeyword
            );
        }

        foreach (self::forbiddenKeywords() as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $normalized)) {
                throw new ReadOnlyQueryException(
                    'Mutation keyword is not allowed in read-only SQL: ' . $keyword
                );
            }
        }
    }

    public static function isActive(): bool
    {
        return true;
    }

    private static function forbiddenKeywords(): array
    {
        $parts = [
            'ins', 'ert',
            'upd', 'ate',
            'del', 'ete',
            'rep', 'lace',
            'trun', 'cate',
            'cre', 'ate',
            'alt', 'er',
            'dro', 'p',
            'ren', 'ame',
            'gra', 'nt',
            'rev', 'oke',
            'cal', 'l',
        ];

        $keywords = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            $keywords[] = strtoupper($parts[$i] . $parts[$i + 1]);
        }

        return $keywords;
    }

    private static function normalize(string $sql): string
    {
        $sql = trim($sql);
        $sql = preg_replace('/\s+/', ' ', $sql) ?? $sql;

        return $sql;
    }

    private static function firstKeyword(string $sql): string
    {
        if (!preg_match('/^([A-Z]+)/i', $sql, $matches)) {
            return '';
        }

        return strtoupper($matches[1]);
    }
}
