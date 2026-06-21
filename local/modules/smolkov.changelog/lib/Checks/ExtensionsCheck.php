<?php

namespace Smolkov\Changelog\Checks;

final class ExtensionsCheck implements CheckInterface
{
    /**
     * @param array<int, string> $required
     * @param array<int, string|array<int, string>> $recommended
     */
    public function __construct(
        private readonly array $required,
        private readonly array $recommended
    ) {
    }

    public function run(array $context): array
    {
        $results = [];
        $extensions = (array)($context['environment']['extensions'] ?? []);

        foreach ($this->required as $name) {
            $isLoaded = (bool)($extensions[$name] ?? false);
            $results[] = [
                'id' => 'ext_required_' . $name,
                'title' => 'Расширение (обязательное): ' . $name,
                'status' => $isLoaded ? 'OK' : 'ERROR',
                'details' => $isLoaded ? 'Установлено' : 'Не установлено',
                'recommendation' => $isLoaded ? '' : 'Установите расширение ' . $name,
            ];
        }

        foreach ($this->recommended as $item) {
            $group = is_array($item) ? $item : [$item];
            $loaded = false;

            foreach ($group as $extName) {
                if ((bool)($extensions[$extName] ?? false)) {
                    $loaded = true;
                    break;
                }
            }

            $label = implode('/', $group);
            $results[] = [
                'id' => 'ext_recommended_' . str_replace('/', '_', $label),
                'title' => 'Расширение (рекомендуемое): ' . $label,
                'status' => $loaded ? 'OK' : 'WARN',
                'details' => $loaded ? 'Установлено' : 'Не установлено',
                'recommendation' => $loaded ? '' : 'Рекомендуется установить: ' . $label,
            ];
        }

        return $results;
    }
}
