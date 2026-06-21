<?php

namespace Smolkov\Changelog\Checks;

use Smolkov\Changelog\Util\Version;

final class PhpVersionCheck implements CheckInterface
{
    public function __construct(
        private readonly string $minVersion,
        private readonly string $recommendedVersion
    ) {
    }

    public function run(array $context): array
    {
        $currentVersion = (string)($context['environment']['phpVersion'] ?? PHP_VERSION);

        $status = 'OK';
        $recommendation = '';

        if (Version::compare($currentVersion, $this->minVersion) < 0) {
            $status = 'ERROR';
            $recommendation = 'Обновите PHP минимум до ' . $this->minVersion;
        } elseif (Version::compare($currentVersion, $this->recommendedVersion) < 0) {
            $status = 'WARN';
            $recommendation = 'Рекомендуется обновить PHP до ' . $this->recommendedVersion;
        }

        return [[
            'id' => 'php_version',
            'title' => 'Версия PHP',
            'status' => $status,
            'details' => sprintf(
                'Текущая: %s, min: %s, recommended: %s',
                $currentVersion,
                $this->minVersion,
                $this->recommendedVersion
            ),
            'recommendation' => $recommendation,
        ]];
    }
}
