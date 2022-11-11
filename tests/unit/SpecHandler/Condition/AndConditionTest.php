<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Webclient\Fake\Tools\Condition\StaticCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\AndCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\ConditionInterface;

final class AndConditionTest extends TestCase
{
    /**
     * @param ConditionInterface[] $conditions
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(array $conditions, bool $expectedResult)
    {
        $andCondition = new AndCondition(...$conditions);
        $actualResult = $andCondition->check($this->createRequest());
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            [[], true],
            [[new StaticCondition(true), new StaticCondition(true), new StaticCondition(true)], true],

            [[new StaticCondition(false), new StaticCondition(true), new StaticCondition(true)], false],
            [[new StaticCondition(true), new StaticCondition(false), new StaticCondition(true)], false],
            [[new StaticCondition(true), new StaticCondition(true), new StaticCondition(false)], false],

            [[new StaticCondition(false), new StaticCondition(false), new StaticCondition(true)], false],
            [[new StaticCondition(false), new StaticCondition(true), new StaticCondition(false)], false],
            [[new StaticCondition(true), new StaticCondition(false), new StaticCondition(false)], false],

            [[new StaticCondition(false), new StaticCondition(false), new StaticCondition(false)], false],
        ];
    }

    private function createRequest(): ServerRequestInterface
    {
        return new ServerRequest('GET', 'http://localhost/');
    }
}
