<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler;

use Psr\Http\Message\ResponseInterface;
use Webclient\Fake\Handler\SpecHandler\Condition\ConditionInterface;
use Webclient\Fake\Handler\SpecHandler\Exception\BadResponseFiller;

final class Route
{
    private ConditionInterface $condition;

    /**
     * @var null|callable(ResponseInterface): ResponseInterface
     */
    private $responseFiller = null;

    public function __construct(ConditionInterface $condition)
    {
        $this->condition = $condition;
    }

    /**
     * @param  callable(ResponseInterface): ResponseInterface $responseFiller
     */
    public function response(callable $responseFiller): void
    {
        $this->responseFiller = $responseFiller;
    }

    public function getCondition(): ConditionInterface
    {
        return $this->condition;
    }

    public function createResponse(ResponseInterface $response): ResponseInterface
    {
        if (is_null($this->responseFiller)) {
            return $response;
        }
        $result = ($this->responseFiller)($response);
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        throw new BadResponseFiller('can not create response');
    }
}
