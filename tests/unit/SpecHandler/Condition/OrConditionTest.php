<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Webclient\Fake\Tools\Condition\StaticCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\ConditionInterface;
use Webclient\Fake\Handler\SpecHandler\Condition\OrCondition;

final class OrConditionTest extends TestCase
{
    /**
     * @param ConditionInterface[] $conditions
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(array $conditions, bool $expectedResult)
    {
        $orCondition = new OrCondition(...$conditions);
        $actualResult = $orCondition->check($this->createRequest());
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            [[], true],
            [[new StaticCondition(true), new StaticCondition(true), new StaticCondition(true)], true],

            [[new StaticCondition(false), new StaticCondition(true), new StaticCondition(true)], true],
            [[new StaticCondition(true), new StaticCondition(false), new StaticCondition(true)], true],
            [[new StaticCondition(true), new StaticCondition(true), new StaticCondition(false)], true],

            [[new StaticCondition(false), new StaticCondition(false), new StaticCondition(true)], true],
            [[new StaticCondition(false), new StaticCondition(true), new StaticCondition(false)], true],
            [[new StaticCondition(true), new StaticCondition(false), new StaticCondition(false)], true],

            [[new StaticCondition(false), new StaticCondition(false), new StaticCondition(false)], false],
        ];
    }

    private function createRequest(): ServerRequestInterface
    {
        return new ServerRequest('GET', 'http://localhost/');
    }
}
