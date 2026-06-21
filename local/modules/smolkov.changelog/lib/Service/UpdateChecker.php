<?php

namespace Smolkov\Changelog\Service;

use Bitrix\Main\Loader;

final class UpdateChecker
{
    /**
     * @param array<int, array<string, string>> $modules
     * @return array<string, mixed>
     */
    public function check(array $modules): array
    {
        $updatesAvailable = 'unknown';
        $reason = '';

        $moduleUpdates = [];
        foreach ($modules as $module) {
            $moduleUpdates[(string)$module['moduleId']] = 'unknown';
        }

        if (!Loader::includeModule('main')) {
            return [
                'updatesAvailable' => 'unknown',
                'reason' => 'Не удалось загрузить модуль main',
                'moduleUpdates' => $moduleUpdates,
            ];
        }

        if (!class_exists('CUpdateClient') || !method_exists('CUpdateClient', 'GetUpdatesList')) {
            return [
                'updatesAvailable' => 'unknown',
                'reason' => 'Класс CUpdateClient недоступен',
                'moduleUpdates' => $moduleUpdates,
            ];
        }

        try {
            $errorMessage = '';
            $result = \CUpdateClient::GetUpdatesList($errorMessage, LANGUAGE_ID, 'Y');

            $available = $this->extractAvailableUpdates($result);
            if ($available === null) {
                $updatesAvailable = 'unknown';
                $reason = $errorMessage !== '' ? $errorMessage : 'Обновления недоступны (лицензия/настройки)';
            } else {
                $updatesAvailable = count($available) > 0 ? 'yes' : 'no';
                $reason = $errorMessage;
                foreach ($modules as $module) {
                    $moduleId = (string)$module['moduleId'];
                    $moduleUpdates[$moduleId] = in_array($moduleId, $available, true) ? 'yes' : 'no';
                }
            }
        } catch (\Throwable $exception) {
            $updatesAvailable = 'unknown';
            $reason = $exception->getMessage();
        }

        return [
            'updatesAvailable' => $updatesAvailable,
            'reason' => $reason,
            'moduleUpdates' => $moduleUpdates,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function extractAvailableUpdates(mixed $result): ?array
    {
        if (!is_array($result)) {
            return null;
        }

        $available = [];

        foreach ($result as $key => $item) {
            if (is_array($item)) {
                $moduleId = (string)($item['@']['ID'] ?? $item['ID'] ?? $key);
                if ($moduleId !== '') {
                    $available[] = $moduleId;
                }
            } elseif (is_string($key)) {
                $available[] = $key;
            }
        }

        return array_values(array_unique($available));
    }
}
