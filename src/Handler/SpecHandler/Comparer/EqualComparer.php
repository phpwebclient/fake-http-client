<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Comparer;

final class EqualComparer implements ComparerInterface
{
    public function compare(?string $pattern, ?string $actual): bool
    {
        return $pattern === $actual;
    }
}
