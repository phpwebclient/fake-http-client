<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SimpleRoutingHandler implements RequestHandlerInterface
{

    /**
     * @var RequestHandlerInterface[]
     */
    private $handlers = [];

    public function __construct(RequestHandlerInterface $defaultHandler)
    {
        $this->handlers['default'] = $defaultHandler;
    }

    public function route(array $methods, string $uri, RequestHandlerInterface $handler): self
    {
        foreach ($methods as $method) {
            if (!is_string($method)) {
                continue;
            }
            $this->handlers[$method . ' ' . $uri] = $handler;
        }
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $request->getMethod() . ' ' . $request->getUri()->__toString();
        $key = array_key_exists($route, $this->handlers) ? $route : 'default';
        return $this->handlers[$key]->handle($request);
    }
}
