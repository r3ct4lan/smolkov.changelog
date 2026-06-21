<?php

namespace Smolkov\Changelog\Checks;

use Smolkov\Changelog\Util\Version;

final class MysqlVersionCheck implements CheckInterface
{
    public function __construct(
        private readonly string $minVersion,
        private readonly string $recommendedVersion
    ) {
    }

    public function run(array $context): array
    {
        $mysqlVersion = (string)($context['environment']['mysqlVersion'] ?? '0.0.0');

        $status = 'OK';
        $recommendation = '';

        if (Version::compare($mysqlVersion, $this->minVersion) < 0) {
            $status = 'ERROR';
            $recommendation = 'Обновите MySQL минимум до ' . $this->minVersion;
        } elseif (Version::compare($mysqlVersion, $this->recommendedVersion) < 0) {
            $status = 'WARN';
            $recommendation = 'Рекомендуется обновить MySQL до ' . $this->recommendedVersion;
        }

        return [[
            'id' => 'mysql_version',
            'title' => 'Версия MySQL',
            'status' => $status,
            'details' => sprintf(
                'Текущая: %s, min: %s, recommended: %s',
                $mysqlVersion,
                $this->minVersion,
                $this->recommendedVersion
            ),
            'recommendation' => $recommendation,
        ]];
    }
}
