<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'smolkov.changelog',
    [
        'Smolkov\\Changelog\\Service\\Collector' => 'lib/Service/Collector.php',
        'Smolkov\\Changelog\\Service\\UpdateChecker' => 'lib/Service/UpdateChecker.php',
        'Smolkov\\Changelog\\Service\\ReportBuilder' => 'lib/Service/ReportBuilder.php',
        'Smolkov\\Changelog\\Service\\Exporter' => 'lib/Service/Exporter.php',
        'Smolkov\\Changelog\\Checks\\CheckInterface' => 'lib/Checks/CheckInterface.php',
        'Smolkov\\Changelog\\Checks\\PhpVersionCheck' => 'lib/Checks/PhpVersionCheck.php',
        'Smolkov\\Changelog\\Checks\\MysqlVersionCheck' => 'lib/Checks/MysqlVersionCheck.php',
        'Smolkov\\Changelog\\Checks\\IniSettingsCheck' => 'lib/Checks/IniSettingsCheck.php',
        'Smolkov\\Changelog\\Checks\\ExtensionsCheck' => 'lib/Checks/ExtensionsCheck.php',
        'Smolkov\\Changelog\\Util\\Version' => 'lib/Util/Version.php',
        'Smolkov\\Changelog\\Util\\Ini' => 'lib/Util/Ini.php',
    ]
);
