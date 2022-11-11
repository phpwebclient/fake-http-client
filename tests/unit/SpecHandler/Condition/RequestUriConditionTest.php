<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\EqualComparer;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestUriCondition;

final class RequestUriConditionTest extends TestCase
{
    /**
     * @param string $pattern
     * @param string|null $part
     * @param string $uri
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(string $pattern, ?string $part, string $uri, bool $expectedResult)
    {
        $requestUriCondition = new RequestUriCondition(new EqualComparer(), $pattern, $part);
        $actualResult = $requestUriCondition->check($this->createRequest($uri));
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            ['http', 'scheme', 'https://phpunit.de', false],
            ['https', 'scheme', 'https://phpunit.de', true],
            ['', 'userInfo', 'https://phpunit.de', true],
            ['user:pass', 'userInfo', 'https://user:pass@phpunit.de', true],
            ['user:pass', 'userInfo', 'https://phpunit.de', false],
            ['user:pass@phpunit.de', 'authority', 'https://user:pass@phpunit.de', true],
            ['phpunit.de', 'authority', 'https://phpunit.de', true],
            ['localhost:8080', 'authority', 'http://localhost:8080', true],
            ['user:pass@localhost:8080', 'authority', 'http://user:pass@localhost:8080', true],
            ['localhost:8080', 'authority', 'http://user:pass@localhost:8080', false],
            ['localhost', 'host', 'http://user:pass@localhost:8080', true],
            ['phpunit.de', 'host', 'https://phpunit.de', true],
            ['localhost', 'host', 'https://phpunit.de', false],
            ['443', 'port', 'https://phpunit.de', true],
            ['80', 'port', 'http://phpunit.de', true],
            ['443', 'port', 'https://phpunit.de:443', true],
            ['80', 'port', 'http://phpunit.de:80', true],
            ['8080', 'port', 'http://localhost:8080', true],
            ['8080', 'port', 'http://localhost', false],
            ['', 'path', 'http://localhost', true],
            ['/', 'path', 'http://localhost/', true],
            ['/path/to/resource', 'path', 'http://localhost/path/to/resource', true],
            ['/path/to/resource', 'path', 'http://localhost/path/to/resource/1', false],
            ['', 'query', 'http://localhost/path/to/resource', true],
            ['id=1', 'query', 'http://localhost/path/to/resource?id=1', true],
            ['id=1', 'query', 'http://localhost/path/to/resource?id=2', false],
            ['', 'fragment', 'http://localhost/path/to/resource?id=2', true],
            ['anchor', 'fragment', 'http://localhost/path/to/resource?id=2#anchor', true],
            ['', 'fragment', 'http://localhost/path/to/resource?id=2#anchor', false],
            ['anchor', 'fragment', 'http://localhost/path/to/resource?id=2', false],
        ];
    }

    private function createRequest(string $uri): ServerRequestInterface
    {
        return (new ServerRequest('GET', $uri));
    }
}
