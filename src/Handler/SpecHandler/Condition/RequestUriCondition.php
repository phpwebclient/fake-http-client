<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Condition;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Webclient\Fake\Handler\SpecHandler\Comparer\ComparerInterface;

final class RequestUriCondition implements ConditionInterface
{
    private ComparerInterface $comparer;
    private ?string $pattern;
    private string $part;

    public function __construct(ComparerInterface $comparer, ?string $pattern, ?string $part)
    {
        $this->comparer = $comparer;
        $this->pattern = $pattern;
        $this->part = strtolower(trim((string)$part));
    }

    public function check(ServerRequestInterface $request): bool
    {
        $value = $this->getValue($request->getUri());
        return $this->comparer->compare($this->pattern, $value);
    }

    private function getValue(UriInterface $uri): string
    {
        switch ($this->part) {
            case '':
                return (string)$uri;
            case 'scheme':
                return $uri->getScheme();
            case 'userinfo':
                return $uri->getUserInfo();
            case 'authority':
                return $uri->getAuthority();
            case 'host':
                return $uri->getHost();
            case 'port':
                return $this->getPostString($uri);
            case 'path':
                return $uri->getPath();
            case 'query':
                return $uri->getQuery();
            case 'fragment':
                return $uri->getFragment();
        }
        throw new RuntimeException(sprintf('uri has not %s part', $this->part));
    }

    private function getPostString(UriInterface $uri): string
    {
        $port = $uri->getPort();
        if (!is_null($port)) {
            return (string)$port;
        }
        if ($uri->getScheme() === 'http') {
            return '80';
        }
        if ($uri->getScheme() === 'https') {
            return '443';
        }
        return '';
    }
}
