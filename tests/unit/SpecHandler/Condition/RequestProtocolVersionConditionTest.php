<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\EqualComparer;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestProtocolVersionCondition;

final class RequestProtocolVersionConditionTest extends TestCase
{
    /**
     * @param string $pattern
     * @param string $protocolVersion
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(string $pattern, string $protocolVersion, bool $expectedResult)
    {
        $requestProtocolVersionCondition = new RequestProtocolVersionCondition(new EqualComparer(), $pattern);
        $actualResult = $requestProtocolVersionCondition->check($this->createRequest($protocolVersion));
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            ['1.1', '1.1', true],
            ['1.0', '1.1', false],
        ];
    }

    private function createRequest(string $protocolVersion): ServerRequestInterface
    {
        return (new ServerRequest('GET', 'http://localhost/'))->withProtocolVersion($protocolVersion);
    }
}
