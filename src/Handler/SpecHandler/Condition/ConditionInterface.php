<?php

namespace Webclient\Fake\Handler\SpecHandler\Condition;

use Psr\Http\Message\ServerRequestInterface;

interface ConditionInterface
{
    public function check(ServerRequestInterface $request): bool;
}
