<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Comparer;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Webclient\Fake\Handler\SpecHandler\Comparer\ComparerInterface;

abstract class AbstractComparerTest extends TestCase
{
    /**
     * @param string|null $pattern
     * @param string|null $actual
     * @param bool $expectedResult
     * @return void
     * @dataProvider provideCompare
     */
    final public function testCompare(?string $pattern, ?string $actual, bool $expectedResult)
    {
        $comparer = $this->getComparer();
        $actualResult = $comparer->compare($pattern, $actual);
        Assert::assertSame($expectedResult, $actualResult);
    }

    abstract public function provideCompare(): iterable;

    abstract protected function getComparer(): ComparerInterface;
}
