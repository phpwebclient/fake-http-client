<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Comparer;

final class ContainsComparer implements ComparerInterface
{
    public function compare(?string $pattern, ?string $actual): bool
    {
        if (is_null($pattern) && is_null($actual)) {
            return true;
        }
        if (is_null($pattern) || is_null($actual)) {
            return false;
        }
        return strpos($actual, $pattern) !== false;
    }
}
