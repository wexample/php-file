<?php

namespace Wexample\PhpFile\Helper;

/**
 * Turns a size in bytes into something readable, and reads it back.
 *
 * The two directions live together because they answer to one another: a limit
 * written `10MB` in a configuration file is shown back as `10 MB`, and a
 * multiplier changed on one side without the other is a discrepancy nothing
 * catches.
 */
class FileSizeHelper
{
    /**
     * What each written unit is worth, read in binary — a `KB` is 1024 — and in
     * French as well as English, since both spellings are written in the
     * configurations this reads.
     */
    final public const UNIT_MULTIPLIERS = [
        'B' => 1,
        'K' => 1024, 'KB' => 1024, 'KO' => 1024,
        'M' => 1024 ** 2, 'MB' => 1024 ** 2, 'MO' => 1024 ** 2,
        'G' => 1024 ** 3, 'GB' => 1024 ** 3, 'GO' => 1024 ** 3,
        'T' => 1024 ** 4, 'TB' => 1024 ** 4, 'TO' => 1024 ** 4,
        'P' => 1024 ** 5, 'PB' => 1024 ** 5, 'PO' => 1024 ** 5,
    ];

    /** The steps of the ladder, in the order `format()` climbs them. */
    final public const UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    /**
     * How many bytes one written size stands for, or null when the text says
     * nothing usable. A bare number is already a count of bytes.
     */
    public static function parse(string|int $size): ?int
    {
        if (is_int($size)) {
            return $size;
        }

        $size = trim(strtoupper($size));

        if (! preg_match('/^(\d+(?:\.\d+)?)\s*([KMGTP]?B?|[KMGTP]O)?$/i', $size, $matches)) {
            return null;
        }

        $number = (float) $matches[1];
        $unit = $matches[2] ?? '';

        if ('' === $unit) {
            return (int) $number;
        }

        $multiplier = self::UNIT_MULTIPLIERS[strtoupper($unit)] ?? null;

        return null !== $multiplier ? (int) ($number * $multiplier) : null;
    }

    /**
     * The other way round, for display: 1536 becomes 1.5 KB. Bytes stay whole,
     * since half a byte says nothing.
     */
    public static function format(int $bytes): string
    {
        $power = 0;

        while ($bytes >= 1024 && $power < count(self::UNITS) - 1) {
            $bytes /= 1024;
            ++$power;
        }

        return 0 === $power
            ? $bytes.' B'
            : round($bytes, 1).' '.self::UNITS[$power];
    }
}
