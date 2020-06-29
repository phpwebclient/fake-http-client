<?php

declare(strict_types=1);

namespace Webclient\Stuff\Fake\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UniversalHandler implements RequestHandlerInterface
{

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = [
            'protocol' => $request->getProtocolVersion(),
            'method' => $request->getMethod(),
            'uri' => $request->getUri()->__toString(),
            'headers' => [],
            'body' => $request->getParsedBody(),
            'server' => $request->getServerParams(),
            'cookies' => $request->getCookieParams(),
            'files' => [],
        ];
        foreach (array_keys($request->getHeaders()) as $header) {
            $data['headers'][$header] = $request->getHeaderLine($header);
        }
        $status = array_key_exists('return', $query) ? (int)$query['return'] : 200;
        if ($status < 100 || $status > 599) {
            $status = 200;
        }
        $location = null;
        if (array_key_exists('redirect', $query) && is_string($query['redirect'])) {
            $status = ($status < 300 || $status > 399) ? 301 : $status;
            $location = $query['redirect'];
        }
        $this->parseFiles($request->getUploadedFiles(), $data['files']);

        $response = $this->responseFactory->createResponse($status);
        if (array_key_exists('cookie', $query)) {
            foreach ((array)$query['cookie'] as $cookie) {
                $response = $response->withAddedHeader('Set-Cookie', $cookie . '=ok');
            }
        }
        if ($location) {
            $response = $response->withHeader('Location', $location);
        }
        $response->getBody()->write(json_encode($data));
        $response->getBody()->rewind();
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function parseFiles(array $files, &$result, $field = '')
    {
        $isAssoc = false;
        $incr = 0;
        foreach (array_keys($files) as $k) {
            if (!is_int($k) || $k !== $incr) {
                $isAssoc = true;
            }
            $incr++;
        }
        foreach ($files as $k => $file) {
            $subfield = $isAssoc ? $k : '';
            $key = $field ? $field . '[' . $subfield . ']' : $k;
            if ($file instanceof UploadedFileInterface) {
                $contents = (string)$file->getStream();
                $result[] = [
                    'field' => $key,
                    'name' => $file->getClientFilename(),
                    'mime' => $file->getClientMediaType(),
                    'md5' => md5($contents),
                    'sha1-1' => sha1($contents),
                    'sha1-2' => hash_hmac('sha1', $contents, ''),
                    'sha256' => hash_hmac('sha256', $contents, ''),
                    'error' => $file->getError(),
                ];
                continue;
            }
            if (is_array($file)) {
                $this->parseFiles($file, $result, $key);
            }
        }
    }
}
