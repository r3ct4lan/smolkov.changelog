<?php

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class smolkov_changelog extends CModule
{
    public $MODULE_ID = 'smolkov.changelog';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $versionData = [];
        include __DIR__ . '/../version.php';

        if (is_array($arModuleVersion ?? null)) {
            $versionData = $arModuleVersion;
        }

        $this->MODULE_VERSION = $versionData['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $versionData['VERSION_DATE'] ?? '2026-02-23 00:00:00';
        $this->MODULE_NAME = Loc::getMessage('SMOLKOV_CHANGELOG_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('SMOLKOV_CHANGELOG_MODULE_DESC');
        $this->PARTNER_NAME = Loc::getMessage('SMOLKOV_CHANGELOG_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('SMOLKOV_CHANGELOG_PARTNER_URI');
    }

    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();

        global $APPLICATION;
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('SMOLKOV_CHANGELOG_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );
    }

    public function DoUninstall(): void
    {
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);

        global $APPLICATION;
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('SMOLKOV_CHANGELOG_UNINSTALL_TITLE'),
            __DIR__ . '/unstep.php'
        );
    }

    public function InstallFiles(): bool
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            Application::getDocumentRoot() . '/bitrix/admin',
            true,
            true
        );

        $reportDir = Application::getDocumentRoot() . '/upload/changelog/reports';
        if (!Directory::isDirectoryExists($reportDir)) {
            Directory::createDirectory($reportDir);
        }

        return true;
    }

    public function UnInstallFiles(): bool
    {
        $adminFile = Application::getDocumentRoot() . '/bitrix/admin/smolkov_changelog_report.php';
        if (File::isFileExists($adminFile)) {
            DeleteDirFilesEx('/bitrix/admin/smolkov_changelog_report.php');
        }

        return true;
    }
}
