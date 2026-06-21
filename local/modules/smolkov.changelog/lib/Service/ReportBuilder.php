<?php

namespace Smolkov\Changelog\Service;

use Smolkov\Changelog\Checks\CheckInterface;
use Smolkov\Changelog\Checks\ExtensionsCheck;
use Smolkov\Changelog\Checks\IniSettingsCheck;
use Smolkov\Changelog\Checks\MysqlVersionCheck;
use Smolkov\Changelog\Checks\PhpVersionCheck;

final class ReportBuilder
{
    public const RULES = [
        'php' => ['min' => '8.0.0', 'recommended' => '8.2.0'],
        'mysql' => ['min' => '5.7.0', 'recommended' => '8.0.0'],
        'ini' => [
            'memory_limit' => ['min' => '256M', 'recommended' => '512M'],
            'upload_max_filesize' => ['min' => '20M', 'recommended' => '50M'],
        ],
        'extensions' => [
            'required' => ['curl', 'mbstring', 'openssl', 'json', 'zip'],
            'recommended' => ['intl', 'opcache', ['gd', 'imagick']],
        ],
    ];

    public function __construct(
        private readonly Collector $collector,
        private readonly UpdateChecker $updateChecker
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $coreModules = $this->collector->collectCoreAndModules();
        $environment = $this->collector->collectEnvironment();
        $updateData = $this->updateChecker->check($coreModules['modules']);

        $modules = [];
        foreach ($coreModules['modules'] as $module) {
            $moduleId = (string)$module['moduleId'];
            $module['updateAvailable'] = $updateData['moduleUpdates'][$moduleId] ?? 'unknown';
            $modules[] = $module;
        }

        $context = ['environment' => $environment];
        $checks = [];

        foreach ($this->getChecks() as $check) {
            foreach ($check->run($context) as $row) {
                $checks[] = $row;
            }
        }

        $summaryCounts = ['OK' => 0, 'WARN' => 0, 'ERROR' => 0];
        foreach ($checks as $check) {
            $status = (string)$check['status'];
            if (isset($summaryCounts[$status])) {
                $summaryCounts[$status]++;
            }
        }

        return [
            'generatedAt' => date('c'),
            'coreVersion' => $coreModules['coreVersion'],
            'updatesAvailable' => $updateData['updatesAvailable'],
            'updatesReason' => $updateData['reason'],
            'environment' => $environment,
            'checks' => $checks,
            'checksSummary' => $summaryCounts,
            'modules' => $modules,
        ];
    }

    /**
     * @return array<int, CheckInterface>
     */
    private function getChecks(): array
    {
        return [
            new PhpVersionCheck(self::RULES['php']['min'], self::RULES['php']['recommended']),
            new MysqlVersionCheck(self::RULES['mysql']['min'], self::RULES['mysql']['recommended']),
            new IniSettingsCheck(self::RULES['ini']),
            new ExtensionsCheck(self::RULES['extensions']['required'], self::RULES['extensions']['recommended']),
        ];
    }
}
