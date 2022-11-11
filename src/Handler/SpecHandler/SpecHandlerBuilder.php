<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class SpecHandlerBuilder
{
    /**
     * @var Route[]
     */
    private array $routes = [];
    private ?ResponseFactoryInterface $responseFactory = null;
    private ?ResponseInterface $defaultResponse = null;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param callable(Rule): void $fn
     * @return Route
     */
    public function route(callable $fn): Route
    {
        $rule = new Rule();
        $fn($rule);
        $route = new Route($rule->getCondition());
        $this->routes[] = $route;
        return $route;
    }

    public function setDefaultResponse(ResponseInterface $response): self
    {
        $this->defaultResponse = $response;
        return $this;
    }

    public function setResponseFactory(ResponseFactoryInterface $responseFactory): self
    {
        $this->responseFactory = $responseFactory;
        return $this;
    }

    public function build(): SpecHandler
    {
        $handler = new SpecHandler(...$this->routes);
        if (!is_null($this->defaultResponse)) {
            $handler = $handler->withDefaultResponse($this->defaultResponse);
        }
        if (!is_null($this->responseFactory)) {
            $handler = $handler->withResponseFactory($this->responseFactory);
        }
        return $handler;
    }
}
