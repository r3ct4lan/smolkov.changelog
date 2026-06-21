<?php

use Bitrix\Main\Loader;
use Smolkov\Changelog\Service\Collector;
use Smolkov\Changelog\Service\Exporter;
use Smolkov\Changelog\Service\ReportBuilder;
use Smolkov\Changelog\Service\UpdateChecker;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION;
global $USER;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещён');
}

if (!Loader::includeModule('smolkov.changelog')) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
    echo CAdminMessage::ShowMessage('Модуль smolkov.changelog не установлен.');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

    return;
}

$reportBuilder = new ReportBuilder(new Collector(), new UpdateChecker());
$exporter = new Exporter();

$action = (string)($_REQUEST['action'] ?? '');
$statusFilter = (string)($_GET['status_filter'] ?? 'all');
$onlyUpdates = (string)($_GET['only_updates'] ?? 'N');

if (($action === 'download_html' || $action === 'download_csv') && !check_bitrix_sessid()) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
    echo CAdminMessage::ShowMessage('Неверная сессия.');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

    return;
}

$report = $reportBuilder->build();

if ($action === 'download_html') {
    $relativePath = $exporter->exportHtml($report);
    $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

    if (is_file($absolutePath)) {
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . basename($absolutePath) . '"');
        readfile($absolutePath);
    }
    die();
}

if ($action === 'download_csv') {
    $csv = $exporter->buildModulesCsv($report['modules']);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="modules-' . date('Ymd-His') . '.csv"');
    echo "\xEF\xBB\xBF";
    echo $csv;
    die();
}

$checks = (array)$report['checks'];
if (in_array($statusFilter, ['OK', 'WARN', 'ERROR'], true)) {
    $checks = array_values(array_filter(
        $checks,
        static fn(array $check): bool => (string)$check['status'] === $statusFilter
    ));
}

$modules = (array)$report['modules'];
if ($onlyUpdates === 'Y') {
    $modules = array_values(array_filter(
        $modules,
        static fn(array $module): bool => (string)$module['updateAvailable'] === 'yes'
    ));
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$APPLICATION->SetTitle('Smolkov Changelog: отчёт');

if ((string)$report['updatesAvailable'] === 'unknown' && (string)$report['updatesReason'] !== '') {
    echo CAdminMessage::ShowMessage([
        'TYPE' => 'PROGRESS',
        'MESSAGE' => 'Проверка обновлений: unknown',
        'DETAILS' => htmlspecialcharsbx((string)$report['updatesReason']),
        'HTML' => true,
    ]);
}
?>
<form method="get" action="">
    <input type="hidden" name="lang" value="<?= htmlspecialcharsbx(LANGUAGE_ID) ?>">
    <label for="status_filter"><b>Фильтр Checks:</b></label>
    <select id="status_filter" name="status_filter">
        <option value="all"<?= $statusFilter === 'all' ? ' selected' : '' ?>>Все</option>
        <option value="OK"<?= $statusFilter === 'OK' ? ' selected' : '' ?>>OK</option>
        <option value="WARN"<?= $statusFilter === 'WARN' ? ' selected' : '' ?>>WARN</option>
        <option value="ERROR"<?= $statusFilter === 'ERROR' ? ' selected' : '' ?>>ERROR</option>
    </select>
    <label style="margin-left:20px;">
        <input type="checkbox" name="only_updates" value="Y"<?= $onlyUpdates === 'Y' ? ' checked' : '' ?>>
        Только модули с updateAvailable=yes
    </label>
    <button type="submit" class="adm-btn">Применить</button>
    <a class="adm-btn" href="<?= htmlspecialcharsbx($APPLICATION->GetCurPageParam('action=rescan&' . bitrix_sessid_get(), ['action', 'sessid'])) ?>">Пересканировать</a>
    <a class="adm-btn adm-btn-save" href="<?= htmlspecialcharsbx($APPLICATION->GetCurPageParam('action=download_html&' . bitrix_sessid_get(), ['action', 'sessid'])) ?>">Скачать HTML отчёт</a>
    <a class="adm-btn" href="<?= htmlspecialcharsbx($APPLICATION->GetCurPageParam('action=download_csv&' . bitrix_sessid_get(), ['action', 'sessid'])) ?>">Скачать CSV модулей</a>
</form>

<br>
<h2>Summary</h2>
<table class="internal" width="100%">
    <tr><td width="30%"><b>Версия ядра main</b></td><td><?= htmlspecialcharsbx((string)$report['coreVersion']) ?></td></tr>
    <tr><td><b>PHP</b></td><td><?= htmlspecialcharsbx((string)$report['environment']['phpVersion']) ?></td></tr>
    <tr><td><b>MySQL</b></td><td><?= htmlspecialcharsbx((string)$report['environment']['mysqlVersion']) ?></td></tr>
    <tr><td><b>Updates available</b></td><td><?= htmlspecialcharsbx((string)$report['updatesAvailable']) ?></td></tr>
    <tr><td><b>Checks OK/WARN/ERROR</b></td>
        <td><?= (int)$report['checksSummary']['OK'] ?>/<?= (int)$report['checksSummary']['WARN'] ?>/<?= (int)$report['checksSummary']['ERROR'] ?></td>
    </tr>
</table>

<h2>Checks</h2>
<table class="internal" width="100%">
    <tr class="heading">
        <td>ID</td>
        <td>Title</td>
        <td>Status</td>
        <td>Details</td>
        <td>Recommendation</td>
    </tr>
    <?php foreach ($checks as $check): ?>
        <tr>
            <td><?= htmlspecialcharsbx((string)$check['id']) ?></td>
            <td><?= htmlspecialcharsbx((string)$check['title']) ?></td>
            <td><b><?= htmlspecialcharsbx((string)$check['status']) ?></b></td>
            <td><?= htmlspecialcharsbx((string)$check['details']) ?></td>
            <td><?= htmlspecialcharsbx((string)$check['recommendation']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Modules</h2>
<table class="internal" width="100%">
    <tr class="heading">
        <td>moduleId</td>
        <td>name</td>
        <td>currentVersion</td>
        <td>updateAvailable</td>
    </tr>
    <?php foreach ($modules as $module): ?>
        <tr>
            <td><?= htmlspecialcharsbx((string)$module['moduleId']) ?></td>
            <td><?= htmlspecialcharsbx((string)$module['name']) ?></td>
            <td><?= htmlspecialcharsbx((string)$module['currentVersion']) ?></td>
            <td><?= htmlspecialcharsbx((string)$module['updateAvailable']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
