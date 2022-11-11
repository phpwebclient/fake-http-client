<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Condition;

use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\ComparerInterface;

final class RequestMethodCondition implements ConditionInterface
{
    private ComparerInterface $comparer;
    private ?string $pattern;

    public function __construct(ComparerInterface $comparer, ?string $pattern)
    {
        $this->comparer = $comparer;
        $this->pattern = $pattern;
    }

    public function check(ServerRequestInterface $request): bool
    {
        return $this->comparer->compare($this->pattern, $request->getMethod());
    }
}
