<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\EqualComparer;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestBodyCondition;

final class RequestBodyConditionTest extends TestCase
{
    /**
     * @param string $pattern
     * @param string|null $body
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(string $pattern, ?string $body, bool $expectedResult)
    {
        $requestBodyCondition = new RequestBodyCondition(new EqualComparer(), $pattern);
        $actualResult = $requestBodyCondition->check($this->createRequest($body));
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            ['OK', 'OK', true],
            ['', null, true],
            ['OK', 'ok', false],
            ['', 'ok', false],
            ['ok', '', false],
        ];
    }

    private function createRequest(?string $body): ServerRequestInterface
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        if (!is_null($body)) {
            $request->getBody()->write($body);
        }
        return $request;
    }
}
