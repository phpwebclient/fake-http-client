<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Condition;

use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\ComparerInterface;

final class RequestQueryCondition implements ConditionInterface
{
    private ComparerInterface $comparer;
    private ?string $pattern;
    private string $queryParam;

    public function __construct(ComparerInterface $comparer, ?string $pattern, string $queryParam)
    {
        $this->comparer = $comparer;
        $this->pattern = $pattern;
        $this->queryParam = $queryParam;
    }

    public function check(ServerRequestInterface $request): bool
    {
        $values = $this->getParameterValues($request);
        foreach ($values as $value) {
            if ($this->comparer->compare($this->pattern, $value)) {
                return true;
            }
        }
        return $this->comparer->compare($this->pattern, null);
    }

    /**
     * @param ServerRequestInterface $request
     * @return string[]
     */
    private function getParameterValues(ServerRequestInterface $request): array
    {
        $query = $request->getUri()->getQuery();
        $lines = explode('&', $query);
        $result = [];
        foreach ($lines as $line) {
            if (strpos($line, '=') === false) {
                $line .= '=';
            }
            [$param, $value] = explode('=', $line);
            if (urldecode($param) === $this->queryParam) {
                $result[] = urldecode($value);
            }
        }
        return $result;
    }
}
