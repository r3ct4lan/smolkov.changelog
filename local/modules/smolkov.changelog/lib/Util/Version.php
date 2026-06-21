<?php

namespace Smolkov\Changelog\Util;

final class Version
{
    public static function normalize(string $version): string
    {
        if (preg_match('/\d+(?:\.\d+)+/', $version, $matches) === 1) {
            return $matches[0];
        }

        return '0.0.0';
    }

    public static function compare(string $left, string $right): int
    {
        return version_compare(self::normalize($left), self::normalize($right));
    }
}
