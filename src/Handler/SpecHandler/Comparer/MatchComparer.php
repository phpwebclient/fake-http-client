<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Comparer;

final class MatchComparer implements ComparerInterface
{
    public function compare(?string $pattern, ?string $actual): bool
    {
        if (is_null($pattern)) {
            return true;
        }
        $pattern = trim($pattern);
        if ($pattern === '') {
            return true;
        }
        preg_match('#' . $pattern . '#u', (string)$actual, $matches);
        return !empty($matches);
    }
}
