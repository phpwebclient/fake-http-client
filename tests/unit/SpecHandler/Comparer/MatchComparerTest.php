<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler\Comparer;

use Webclient\Fake\Handler\SpecHandler\Comparer\ComparerInterface;
use Webclient\Fake\Handler\SpecHandler\Comparer\MatchComparer;

final class MatchComparerTest extends AbstractComparerTest
{
    public function provideCompare(): iterable
    {
        return [
            [null, null, true],
            ['', '', true],
            ['', null, true],
            [null, '', true],
            [' ', 'some string', true],
            ['some\sstring', 'some string', true],
            ['^some$', 'some', true],
            ['^some$', 'some string', false],
            ['\d', 'user1', true],
            ['^\d+$', 'user1', false],
            ['^\d+$', '012', true],
        ];
    }

    protected function getComparer(): ComparerInterface
    {
        return new MatchComparer();
    }
}
