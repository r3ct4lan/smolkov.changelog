<?php

namespace Smolkov\Changelog\Checks;

interface CheckInterface
{
    /**
     * @return array<int, array<string, string>>
     */
    public function run(array $context): array;
}
