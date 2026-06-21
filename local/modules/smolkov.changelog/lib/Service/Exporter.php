<?php

namespace Smolkov\Changelog\Service;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

final class Exporter
{
    /**
     * @param array<string, mixed> $report
     */
    public function exportHtml(array $report): string
    {
        $dir = Application::getDocumentRoot() . '/upload/changelog/reports';
        if (!Directory::isDirectoryExists($dir)) {
            Directory::createDirectory($dir);
        }

        $fileName = sprintf('report-%s.html', date('Ymd-His'));
        $absolutePath = $dir . '/' . $fileName;

        $content = $this->buildHtml($report);
        file_put_contents($absolutePath, $content);

        return '/upload/changelog/reports/' . $fileName;
    }

    /**
     * @param array<int, array<string, string>> $modules
     */
    public function buildModulesCsv(array $modules): string
    {
        $rows = ['moduleId;name;version;updateAvailable'];

        foreach ($modules as $module) {
            $rows[] = implode(';', [
                $this->escapeCsv((string)($module['moduleId'] ?? '')),
                $this->escapeCsv((string)($module['name'] ?? '')),
                $this->escapeCsv((string)($module['currentVersion'] ?? '')),
                $this->escapeCsv((string)($module['updateAvailable'] ?? 'unknown')),
            ]);
        }

        return implode("\n", $rows);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function buildHtml(array $report): string
    {
        $checksRows = '';
        foreach ((array)$report['checks'] as $check) {
            $checksRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string)$check['title']),
                htmlspecialchars((string)$check['status']),
                htmlspecialchars((string)$check['details']),
                htmlspecialchars((string)$check['recommendation'])
            );
        }

        $modulesRows = '';
        foreach ((array)$report['modules'] as $module) {
            $modulesRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string)$module['moduleId']),
                htmlspecialchars((string)$module['name']),
                htmlspecialchars((string)$module['currentVersion']),
                htmlspecialchars((string)$module['updateAvailable'])
            );
        }

        $env = (array)$report['environment'];
        $ini = (array)($env['ini'] ?? []);

        return '<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Changelog report</title>'
            . '<style>body{font-family:Arial,sans-serif;margin:20px}table{border-collapse:collapse;width:100%;margin:10px 0}'
            . 'th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f2f2f2}</style></head><body>'
            . '<h1>Отчёт smolkov.changelog</h1>'
            . '<p><b>Дата:</b> ' . htmlspecialchars((string)$report['generatedAt']) . '</p>'
            . '<p><b>Версия ядра main:</b> ' . htmlspecialchars((string)$report['coreVersion']) . '</p>'
            . '<p><b>Обновления:</b> ' . htmlspecialchars((string)$report['updatesAvailable']) . ' '
            . htmlspecialchars((string)($report['updatesReason'] ?? '')) . '</p>'
            . '<h2>Окружение</h2>'
            . '<p>PHP: ' . htmlspecialchars((string)($env['phpVersion'] ?? 'unknown'))
            . ', MySQL: ' . htmlspecialchars((string)($env['mysqlVersion'] ?? 'unknown')) . '</p>'
            . '<p>memory_limit: ' . htmlspecialchars((string)($ini['memory_limit'] ?? ''))
            . ', upload_max_filesize: ' . htmlspecialchars((string)($ini['upload_max_filesize'] ?? '')) . '</p>'
            . '<h2>Проверки</h2><table><tr><th>Проверка</th><th>Статус</th><th>Детали</th><th>Рекомендация</th></tr>'
            . $checksRows . '</table>'
            . '<h2>Модули</h2><table><tr><th>Module ID</th><th>Name</th><th>Version</th><th>Update</th></tr>'
            . $modulesRows . '</table>'
            . '</body></html>';
    }

    private function escapeCsv(string $value): string
    {
        $safe = str_replace('"', '""', $value);

        return '"' . $safe . '"';
    }
}
