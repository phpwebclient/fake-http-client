<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Comparer;

interface ComparerInterface
{
    public function compare(?string $pattern, ?string $actual): bool;
}
