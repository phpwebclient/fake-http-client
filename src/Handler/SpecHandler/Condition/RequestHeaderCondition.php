<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Condition;

use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\ComparerInterface;

final class RequestHeaderCondition implements ConditionInterface
{
    private ComparerInterface $comparer;
    private ?string $pattern;
    private string $header;

    public function __construct(ComparerInterface $comparer, ?string $pattern, string $header)
    {
        $this->comparer = $comparer;
        $this->pattern = $pattern;
        $this->header = $header;
    }

    public function check(ServerRequestInterface $request): bool
    {
        return $this->comparer->compare($this->pattern, $this->getHeaderValue($request));
    }

    private function getHeaderValue(ServerRequestInterface $request): ?string
    {
        return $request->hasHeader($this->header) ? $request->getHeaderLine($this->header) : null;
    }
}
