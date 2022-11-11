<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\MatchComparer;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestHeaderCondition;

final class RequestHeaderConditionTest extends TestCase
{
    /**
     * @param string $header
     * @param string $pattern
     * @param string|null $value
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(string $header, string $pattern, ?string $value, bool $expectedResult)
    {
        $requestHeaderCondition = new RequestHeaderCondition(new MatchComparer(), $pattern, $header);
        $actualResult = $requestHeaderCondition->check($this->createRequest($header, $value));
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            ['accept', '', null, true],
            ['accept', '^application/json$', 'application/json', true],
            ['authorization', '^bearer\s(.+)$', 'bearer token', true],
            ['authorization', '^bearer\s(.+)$', 'basic userinfo', false],
        ];
    }

    private function createRequest(string $header, ?string $value): ServerRequestInterface
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        if (!is_null($value)) {
            $request = $request->withHeader($header, [$value]);
        }
        return $request;
    }
}
