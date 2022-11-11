<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler\Condition;

use Psr\Http\Message\ServerRequestInterface;

final class AndCondition implements ConditionInterface
{
    /**
     * @var ConditionInterface[]
     */
    private array $conditions;

    public function __construct(ConditionInterface ...$conditions)
    {
        $this->conditions = $conditions;
    }

    public function check(ServerRequestInterface $request): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->check($request)) {
                return false;
            }
        }
        return true;
    }
}
