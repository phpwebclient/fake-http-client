<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Condition;

use Psr\Http\Message\ServerRequestInterface;

final class NotCondition implements ConditionInterface
{
    private ConditionInterface $condition;

    public function __construct(ConditionInterface $condition)
    {
        $this->condition = $condition;
    }

    public function check(ServerRequestInterface $request): bool
    {
        return !$this->condition->check($request);
    }
}
