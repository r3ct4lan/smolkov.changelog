<?php

namespace Smolkov\Changelog\Util;

final class Ini
{
    public static function sizeToBytes(?string $value): int
    {
        $raw = trim((string)$value);
        if ($raw == '' || $raw === '-1') {
            return PHP_INT_MAX;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*([KMGTP]?)(B)?$/i', $raw, $matches) !== 1) {
            return 0;
        }

        $number = (float)$matches[1];
        $unit = strtoupper($matches[2] ?? '');

        $multiplier = match ($unit) {
            'K' => 1024,
            'M' => 1024 ** 2,
            'G' => 1024 ** 3,
            'T' => 1024 ** 4,
            'P' => 1024 ** 5,
            default => 1,
        };

        return (int)round($number * $multiplier);
    }

    public static function bytesToHuman(int $bytes): string
    {
        if ($bytes === PHP_INT_MAX) {
            return 'unlimited';
        }

        $units = ['B', 'K', 'M', 'G', 'T'];
        $index = 0;
        $size = (float)$bytes;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return sprintf('%.0f%s', $size, $units[$index]);
    }
}
