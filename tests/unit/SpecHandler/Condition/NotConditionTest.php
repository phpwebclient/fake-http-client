<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Condition;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Webclient\Fake\Tools\Condition\StaticCondition;
use Webclient\Fake\Handler\SpecHandler\Condition\ConditionInterface;
use Webclient\Fake\Handler\SpecHandler\Condition\NotCondition;

final class NotConditionTest extends TestCase
{
    /**
     * @param ConditionInterface $condition
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCheck
     */
    public function testCheck(ConditionInterface $condition, bool $expectedResult)
    {
        $notCondition = new NotCondition($condition);
        $actualResult = $notCondition->check($this->createRequest());
        Assert::assertSame($expectedResult, $actualResult);
    }

    public function provideCheck(): iterable
    {
        return [
            [new StaticCondition(true), false],
            [new StaticCondition(false), true],
        ];
    }

    private function createRequest(): ServerRequestInterface
    {
        return new ServerRequest('GET', 'http://localhost/');
    }
}
