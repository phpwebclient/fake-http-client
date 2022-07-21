<?php

declare(strict_types=1);

namespace Webclient\Fake\Exception;

use Exception;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

class NetworkError extends Exception implements NetworkExceptionInterface
{
    private RequestInterface $request;

    public function __construct(RequestInterface $request, Throwable $previous = null)
    {
        $this->request = $request;
        parent::__construct('network error', 127, $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
