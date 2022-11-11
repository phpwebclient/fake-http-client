<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\EqualComparer;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestMethodCondition;

final class RequestMethodConditionTest extends TestCase
{
    /**
     * @param string $pattern
     * @param string $method
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(string $pattern, string $method, bool $expectedResult)
    {
        $requestMethodCondition = new RequestMethodCondition(new EqualComparer(), $pattern);
        $actualResult = $requestMethodCondition->check($this->createRequest($method));
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            ['GET', 'GET', true],
            ['HEAD', 'POST', false],
        ];
    }

    private function createRequest(string $method): ServerRequestInterface
    {
        return new ServerRequest($method, 'http://localhost/');
    }
}
