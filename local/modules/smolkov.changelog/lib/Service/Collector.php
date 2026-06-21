<?php

namespace Smolkov\Changelog\Service;

use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;

final class Collector
{
    /**
     * @return array<string, mixed>
     */
    public function collectCoreAndModules(): array
    {
        $coreVersion = defined('SM_VERSION') ? (string)SM_VERSION : 'unknown';

        $modules = [];
        foreach ((array)ModuleManager::getInstalledModules() as $moduleId => $_) {
            $moduleName = $moduleId;
            $moduleVersion = 'unknown';

            if (isset($GLOBALS['MESS']) && is_array($GLOBALS['MESS'])) {
                // no-op, keeps analyzers calm for global MESS side effects
            }

            $moduleObject = CModule::CreateModuleObject($moduleId);
            if (is_object($moduleObject)) {
                $moduleVersion = (string)($moduleObject->MODULE_VERSION ?? 'unknown');
                $moduleName = (string)($moduleObject->MODULE_NAME ?? $moduleId);
            }

            $modules[] = [
                'moduleId' => (string)$moduleId,
                'name' => $moduleName,
                'currentVersion' => $moduleVersion,
            ];
        }

        usort(
            $modules,
            static fn(array $a, array $b): int => strcmp($a['moduleId'], $b['moduleId'])
        );

        return [
            'coreVersion' => $coreVersion,
            'modules' => $modules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function collectEnvironment(): array
    {
        $connection = Application::getConnection();
        $mysqlVersion = 'unknown';
        $charset = 'unknown';
        $collation = 'unknown';

        try {
            $versionRow = $connection->query('SELECT VERSION() AS VERSION')->fetch();
            if (is_array($versionRow) && isset($versionRow['VERSION'])) {
                $mysqlVersion = (string)$versionRow['VERSION'];
            }

            $vars = $connection->query("SHOW VARIABLES WHERE Variable_name IN ('character_set_database','collation_database')");
            while ($row = $vars->fetch()) {
                if (($row['Variable_name'] ?? '') === 'character_set_database') {
                    $charset = (string)($row['Value'] ?? 'unknown');
                }
                if (($row['Variable_name'] ?? '') === 'collation_database') {
                    $collation = (string)($row['Value'] ?? 'unknown');
                }
            }
        } catch (\Throwable) {
            // graceful degradation
        }

        $iniKeys = [
            'memory_limit',
            'max_execution_time',
            'post_max_size',
            'upload_max_filesize',
            'max_input_vars',
        ];

        $iniValues = [];
        foreach ($iniKeys as $key) {
            $iniValues[$key] = (string)ini_get($key);
        }

        $extensions = [
            'curl' => extension_loaded('curl'),
            'mbstring' => extension_loaded('mbstring'),
            'json' => extension_loaded('json'),
            'openssl' => extension_loaded('openssl'),
            'zip' => extension_loaded('zip'),
            'gd' => extension_loaded('gd'),
            'imagick' => extension_loaded('imagick'),
            'intl' => extension_loaded('intl'),
            'opcache' => extension_loaded('Zend OPcache') || extension_loaded('opcache'),
        ];

        return [
            'phpVersion' => PHP_VERSION,
            'mysqlVersion' => $mysqlVersion,
            'ini' => $iniValues,
            'extensions' => $extensions,
            'db' => [
                'charset' => $charset,
                'collation' => $collation,
            ],
        ];
    }
}
