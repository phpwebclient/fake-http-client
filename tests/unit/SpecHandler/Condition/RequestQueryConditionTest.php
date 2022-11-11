<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\EqualComparer;
use Webclient\Fake\Handler\SpecHandler\Condition\RequestQueryCondition;

final class RequestQueryConditionTest extends TestCase
{
    /**
     * @param string $pattern
     * @param string $param
     * @param string $query
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(string $pattern, string $param, string $query, bool $expectedResult)
    {
        $requestQueryCondition = new RequestQueryCondition(new EqualComparer(), $pattern, $param);
        $actualResult = $requestQueryCondition->check($this->createRequest($query));
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            ['ru', 'lang[]', 'lang[]=ru&lang[]=en', true],
            ['kz', 'lang[]', 'lang[]=ru&lang[]=en', false],
            ['ru', 'lang', 'lang=ru', true],
            ['ru', 'lang', 'lang=en', false],
        ];
    }

    private function createRequest(string $query): ServerRequestInterface
    {
        parse_str($query, $parsed);
        return (new ServerRequest('GET', 'http://localhost/?' . $query))
            ->withQueryParams($parsed)
            ;
    }
}
