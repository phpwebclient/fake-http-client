<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Tools\Condition;

use Psr\Http\Message\ServerRequestInterface;
use Webclient\Fake\Handler\SpecHandler\Condition\ConditionInterface;

final class StaticCondition implements ConditionInterface
{
    private bool $result;

    public function __construct(bool $result)
    {
        $this->result = $result;
    }

    public function check(ServerRequestInterface $request): bool
    {
        return $this->result;
    }
}
