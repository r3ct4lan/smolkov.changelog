<?php

namespace Smolkov\Changelog\Checks;

use Smolkov\Changelog\Util\Ini;

final class IniSettingsCheck implements CheckInterface
{
    /**
     * @param array<string, array<string, string>> $rules
     */
    public function __construct(private readonly array $rules)
    {
    }

    public function run(array $context): array
    {
        $results = [];
        $ini = (array)($context['environment']['ini'] ?? []);

        foreach ($this->rules as $setting => $limits) {
            $rawValue = (string)($ini[$setting] ?? '');
            $currentBytes = Ini::sizeToBytes($rawValue);
            $minBytes = Ini::sizeToBytes((string)$limits['min']);
            $recommendedBytes = Ini::sizeToBytes((string)$limits['recommended']);

            $status = 'OK';
            $recommendation = '';

            if ($currentBytes < $minBytes) {
                $status = 'ERROR';
                $recommendation = sprintf('Установите %s минимум %s', $setting, $limits['min']);
            } elseif ($currentBytes < $recommendedBytes) {
                $status = 'WARN';
                $recommendation = sprintf('Рекомендуется установить %s %s', $setting, $limits['recommended']);
            }

            $results[] = [
                'id' => 'ini_' . $setting,
                'title' => 'INI: ' . $setting,
                'status' => $status,
                'details' => sprintf(
                    'Текущее: %s, min: %s, recommended: %s',
                    $rawValue ?: 'n/a',
                    $limits['min'],
                    $limits['recommended']
                ),
                'recommendation' => $recommendation,
            ];
        }

        return $results;
    }
}
