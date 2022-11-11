<?php

declare(strict_types=1);

namespace Webclient\Fake;

use Webclient\Fake\Exception\NetworkError;
use Webclient\Fake\Message\ServerRequest;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class FakeHttpClient implements ClientInterface
{
    public const NO_REPLACE_ATTRIBUTE = 'webclient-fake-http-client-request-no-replace';

    private RequestHandlerInterface $handler;
    private array $serverParams;

    public function __construct(RequestHandlerInterface $handler, array $serverParams = [])
    {
        $this->handler = $handler;
        $this->serverParams = $serverParams;
    }

    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($request instanceof ServerRequestInterface && $request->getAttribute(self::NO_REPLACE_ATTRIBUTE, false)) {
            $serverRequest = $request;
        } else {
            $serverRequest = new ServerRequest(
                $request,
                $this->serverParams
            );
        }
        $serverRequest->getBody()->rewind();
        $handler = $this->handler;
        try {
            return $handler->handle($serverRequest);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ClientExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new NetworkError($request, $exception);
        }
    }
}
