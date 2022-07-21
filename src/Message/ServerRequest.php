<?php

declare(strict_types=1);

namespace Webclient\Fake\Message;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest implements ServerRequestInterface
{
    private StreamInterface $body;
    private UriInterface $uri;
    private string $protocolVersion;
    private string $method;
    private string $target;
    private array $server;
    private array $attributes = [];
    private array $query = [];
    private array $cookies = [];
    private array $files = [];
    /** @var string[][] */
    private array $headers = [];


    /**
     * @var null|array|object
     */
    private $parsedBody;

    public function __construct(
        UriInterface $uri,
        StreamInterface $body,
        string $protocolVersion,
        string $method,
        string $target,
        array $headers,
        array $server = []
    ) {
        $this->body = $body;
        $this->uri = $uri;
        $this->protocolVersion = $protocolVersion;
        $this->method = $method;
        $this->target = $target;
        $this->server = $server;
        parse_str($this->uri->getQuery(), $this->query);
        $this->server['REQUEST_URI'] = $this->uri->__toString();
        $this->server['QUERY_STRING'] = $this->uri->getQuery();
        $this->server['REQUEST_METHOD'] = $this->method;
        $this->server['SERVER_NAME'] = $this->uri->getHost();
        $this->server['SERVER_PROTOCOL'] = $this->protocolVersion;
        if ($uri->getScheme() === 'https') {
            $this->server['HTTPS'] = 1;
        }
        $auth = $uri->getUserInfo();
        $this->headers['Host'] = [$this->uri->getHost()];
        foreach ($headers as $name => $values) {
            $name = $this->normalizeHeaderName($name);
            switch ($name) {
                case 'Cookie':
                    foreach ($values as $value) {
                        list($cookie, $data) = explode('=', $value . '=');
                        $data = trim($data);
                        if (!$data) {
                            continue;
                        }
                        $this->cookies[trim($cookie)] = trim($data);
                    }
                    break;
                case 'Authorization':
                    $line = implode(',', $values);
                    if (preg_match('/^\s?basic\s(?<auth>.*)/ui', $line, $m)) {
                        $auth = base64_decode($m['auth']);
                    }
                    $this->headers[$name] = $values;
                    $this->server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = implode(',', $values);
                    break;
                default:
                    $this->headers[$name] = $values;
                    $this->server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = implode(',', $values);
                    break;
            }
        }
        if ($auth) {
            $loginPassword = explode(':', $auth, 2);
            $this->server['PHP_AUTH_USER'] = $loginPassword[0];
            if (array_key_exists(1, $loginPassword)) {
                $this->server['PHP_AUTH_PW'] = $loginPassword[1];
            }
        }
        $contentType = strtolower($this->getHeaderLine('Content-Type'));
        list($type) = explode(';', $contentType);
        $contents = (string)$body;
        if ($type === 'multipart/form-data') {
            if (preg_match('/boundary="?(?<boundary>.*?)"?$/', $contentType, $matches)) {
                $this->parseMultipartBody('--' . $matches['boundary'], $contents);
            }
        } else {
            $this->parseBody($type, $contents, true);
        }
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
        $valid = ['1.0', '1.1', '2.0', '2'];
        if (!in_array($version, $valid)) {
            throw new InvalidArgumentException('Invalid protocol version. Must be one of: ' . implode(', ', $valid));
        }
        $that = clone $this;
        $that->protocolVersion = $version;
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
        $headers = $this->headers;
        $headers[$this->normalizeHeaderName($name)] = (array)$value;
        $that = clone $this;
        $that->headers = $headers;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        $header = $this->getHeader($name);
        foreach ((array)$value as $item) {
            if (!in_array($item, $header)) {
                $header[] = $item;
            }
        }
        return $this->withHeader($name, $header);
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $headers = $this->headers;
        $name = $this->normalizeHeaderName($name);
        if (array_key_exists($name, $headers)) {
            unset($headers[$this->normalizeHeaderName($name)]);
        }
        $that = clone $this;
        $that->headers = $headers;
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
    public function getRequestTarget(): string
    {
        return $this->target;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        $that = clone $this;
        $that->target = $requestTarget;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {
        $that = clone $this;
        $that->method = $method;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $that = clone $this;
        $that->uri = $uri;

        if (!$preserveHost && $uri->getHost() !== '') {
            $that->headers['Host'] = [$uri->getHost()];
            return $that;
        }

        if (($uri->getHost() !== '' && !$this->hasHeader('Host') || $this->getHeaderLine('Host') === '')) {
            $that->headers['Host'] = [$uri->getHost()];
            return $that;
        }
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        $that = clone $this;
        $that->cookies = $cookies;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        $that = clone $this;
        $that->query = $query;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $that = clone $this;
        $that->files = $uploadedFiles;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) {
            throw new InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }
        $that = clone $this;
        $that->parsedBody = $data;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $that = clone $this;
        $that->attributes[$name] = $value;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        $that = clone $this;
        if (array_key_exists($name, $that->attributes)) {
            unset($that->attributes[$name]);
        }
        return $that;
    }

    private function normalizeHeaderName(string $name): string
    {
        return preg_replace_callback('/(^|-)[a-z]/u', function (array $matches) {
            return strtoupper($matches[0]);
        }, strtolower($name));
    }

    private function parseBody(string $contentType, string $contents, $allowRecursion = false)
    {
        $parsedBody = null;
        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                $parsedBody = [];
                parse_str($contents, $parsedBody);
                break;
            case 'application/json':
                $parsedBody = json_decode($contents, true);
                break;
            default:
                if (!$allowRecursion) {
                    return;
                }
                $parts = explode('+', $contentType);
                if (count($parts) >= 2) {
                    $contentType = 'application/' . $parts[count($parts) - 1];
                }
                $this->parseBody($contentType, $contents);
                break;
        }
        if ($parsedBody) {
            $this->parsedBody = $parsedBody;
        }
    }

    private function parseMultipartBody($boundary, $contents)
    {
        $parts = array_slice(explode("\r\n$boundary\r\n", explode("\r\n$boundary--\r\n", $contents)[0]), 1);
        foreach ($parts as $part) {
            $this->parsePart($part);
        }
        $this->headers['Content-Type'] = ['application/x-www-form-urlencoded'];
        $this->server['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $this->body = new Stream(http_build_query((array)$this->parsedBody));
    }

    private function parsePart(string $contents): void
    {
        $data = explode("\r\n\r\n", $contents, 2);
        $header = trim($data[0]);
        if (!$header || !array_key_exists(1, $data)) {
            return;
        }
        $body = trim($data[1]);
        if (!$body) {
            return;
        }
        $headers = [];
        foreach (explode("\r\n", $header) as $line) {
            $parts = explode(':', $line, 2);
            $key = strtolower(trim($parts[0]));
            if (!array_key_exists(1, $parts) || !$key) {
                continue;
            }
            $values = array_map('trim', explode(';', $parts[1]));
            foreach ($values as $value) {
                $parts = explode('=', $value, 2);
                $n = trim($parts[0]);
                if (!$n) {
                    continue;
                }
                if (array_key_exists(1, $parts)) {
                    $headers[$key]['attr'][$n] = ltrim(rtrim(trim($parts[1]), '"'), '"');
                } else {
                    $headers[$key]['values'][] = $n;
                }
                if (!array_key_exists('attr', $headers[$key])) {
                    $headers[$key]['attr'] = [];
                }
                if (!array_key_exists('values', $headers[$key])) {
                    $headers[$key]['values'] = [];
                }
            }
        }
        if (
            !array_key_exists('content-disposition', $headers)
            || !array_key_exists('name', $headers['content-disposition']['attr'])
            || !$headers['content-disposition']['attr']['name']
        ) {
            return;
        }
        $disposition = $headers['content-disposition'];
        $name = $disposition['attr']['name'];
        $filename = array_key_exists('filename', $disposition['attr']) ? $disposition['attr']['filename'] : null;
        if (is_null($filename)) {
            if (is_null($this->parsedBody)) {
                $this->parsedBody = [];
            }
            if (is_array($this->parsedBody)) {
                $this->setField($this->parsedBody, $name, $body);
            }
            return;
        }
        $mime = 'application/octet-stream';
        if (array_key_exists('content-type', $headers) && array_key_exists(0, $headers['content-type']['values'])) {
            $mime = $headers['content-type']['values'][0];
        }
        $file = $this->parseFile($filename, $mime, $body);
        $this->setField($this->files, $name, $file);
    }

    private function parseFile($filename, $contentType, $contents): UploadedFile
    {
        $error = UPLOAD_ERR_OK;
        if (!$contents && $filename === '') {
            $error = UPLOAD_ERR_NO_FILE;
        }
        return new UploadedFile($filename, $contents, $contentType, $error);
    }

    private function setField(array &$bag, string $field, $value)
    {
        $parts = explode('[', str_replace(']', '', $field));
        if (count($parts) == 1) {
            $bag[$field] = $value;
            return;
        }
        $key = $parts[0];
        $target = &$bag;
        for ($i = 1; array_key_exists($i, $parts); $i++) {
            $prev = $key;

            if ($prev === '') {
                $target[] = [];
                end($target);
                $target = &$target[key($target)];
            } else {
                if (!isset($target[$prev]) || !is_array($target[$prev])) {
                    $target[$prev] = [];
                }
                $target = &$target[$prev];
            }
            $key = $parts[$i];
        }
        if ($key === '') {
            $target[] = $value;
        } else {
            $target[$key] = $value;
        }
    }
}
