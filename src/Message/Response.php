<?php

declare(strict_types=1);

namespace Webclient\Fake\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface
{
    /**
     * @var array<string, string[]>
     */
    private array $headers = [];
    private string $protocolVersion = '1.1';
    private int $statusCode = 200;
    private string $reasonPhrase = 'OK';
    private StreamInterface $body;

    public function __construct(int $statusCode = 200, string $reasonPhrase = 'OK')
    {
        $this->setStatus($statusCode, $reasonPhrase);
        $this->body = new Stream();
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {
        $that = clone $this;
        $that->setProtocolVersion($version);
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name): bool
    {
        $name = $this->normalizeHeaderName($name);
        return array_key_exists($name, $this->headers);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name): array
    {
        $name = $this->normalizeHeaderName($name);
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : [];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name): string
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $that = clone $this;
        $that->setHeader($name, $value);
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        $that = clone $this;
        $that->addHeader($name, $value);
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $that = clone $this;
        $that->removeHeader($name);
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        $that = clone $this;
        $that->body = $body;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $that = clone $this;
        $that->setStatus($code, $reasonPhrase);
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function setProtocolVersion(string $version): void
    {
        $this->protocolVersion = $version;
    }

    private function setStatus(int $statusCode, ?string $reasonPhrase): void
    {
        $this->statusCode = $statusCode;
        if (empty($reasonPhrase)) {
            $reasonPhrase = $this->defineReasonPhrase($statusCode);
        }
        $this->reasonPhrase = $reasonPhrase;
    }

    /**
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     */
    private function setHeader(string $name, $value): void
    {
        $this->headers[$this->normalizeHeaderName($name)] = (array)$value;
    }

    /**
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     */
    private function addHeader(string $name, $value): void
    {
        $name = $this->normalizeHeaderName($name);
        $header = $this->headers[$name] ?? [];
        foreach ((array)$value as $item) {
            if (!in_array($item, $header)) {
                $header[] = $item;
            }
        }
        $this->headers[$name] = $header;
    }

    private function removeHeader(string $name): void
    {
        $name = $this->normalizeHeaderName($name);
        if (!array_key_exists($name, $this->headers)) {
            return;
        }
        unset($this->headers[$name]);
    }

    private function normalizeHeaderName(string $name): string
    {
        return mb_convert_case(trim($name), MB_CASE_TITLE);
    }

    private function defineReasonPhrase(int $statusCode): string
    {
        $map = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-status',
            208 => 'Already Reported',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Large',
            415 => 'Unsupported Media Type',
            416 => 'Requested range not satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            511 => 'Network Authentication Required',
        ];
        return $map[$statusCode] ?? '';
    }
}
