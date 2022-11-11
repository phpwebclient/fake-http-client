<?php

declare(strict_types=1);

namespace Webclient\Fake\Handler\SpecHandler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webclient\Fake\Message\Factory\ResponseFactory;

final class SpecHandler implements RequestHandlerInterface
{
    /**
     * @var Route[]
     */
    private array $routes;
    private ResponseFactoryInterface $responseFactory;
    private ?ResponseInterface $defaultResponse = null;

    public function __construct(Route ...$routes)
    {
        $this->routes = $routes;
        $this->responseFactory = new ResponseFactory();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->routes as $route) {
            if ($route->getCondition()->check($request)) {
                return $route->createResponse(
                    $this->responseFactory->createResponse(200, 'OK')
                );
            }
        }
        return $this->defaultResponse ?? $this->responseFactory->createResponse(404, 'Not Found');
    }

    public function withResponseFactory(ResponseFactoryInterface $responseFactory): self
    {
        $that = clone $this;
        $that->responseFactory = $responseFactory;
        return $that;
    }

    public function withDefaultResponse(ResponseInterface $response): self
    {
        $that = clone $this;
        $that->defaultResponse = $response;
        return $that;
    }
}
