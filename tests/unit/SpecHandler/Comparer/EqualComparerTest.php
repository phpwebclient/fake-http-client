<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Comparer;

use Webclient\Fake\Handler\SpecHandler\Comparer\ComparerInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\EqualComparer;

final class EqualComparerTest extends AbstractComparerTest
{
    public function provideCompare(): iterable
    {
        return [
            [null, null, true],
            ['', '', true],
            ['some string', 'some string', true],
            ['', null, false],
            [null, '', false],
        ];
    }

    protected function getComparer(): ComparerInterface
    {
        return new EqualComparer();
    }
}
