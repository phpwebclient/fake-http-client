<?php

declare(strict_types=1);

namespace Webclient\Fake\Message;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest implements ServerRequestInterface
{
    private StreamInterface $body;
    private UriInterface $uri;
    private string $protocolVersion;
    private string $method;
    private string $target;
    private array $serverParams;
    private array $attributes = [];
    private array $query = [];
    private array $cookies = [];
    private array $files;

    /**
     * @var array<string, string[]>
     */
    private array $headers = [];

    /**
     * @var null|array|object
     */
    private $parsedBody;

    /**
     * @param RequestInterface $request
     * @param array $serverParams
     */
    public function __construct(
        RequestInterface $request,
        array $serverParams = []
    ) {
        $uri = $request->getUri();
        $this->uri = $uri;
        $this->protocolVersion = $request->getProtocolVersion();
        $this->method = $request->getMethod();
        $body = $request->getBody();
        $this->target = $request->getRequestTarget();
        $this->serverParams = $serverParams;
        parse_str($this->uri->getQuery(), $this->query);
        $this->serverParams['REQUEST_URI'] = $this->uri->__toString();
        $this->serverParams['QUERY_STRING'] = $this->uri->getQuery();
        $this->serverParams['REQUEST_METHOD'] = $this->method;
        $this->serverParams['SERVER_NAME'] = $this->uri->getHost();
        $this->serverParams['SERVER_PROTOCOL'] = $this->protocolVersion;
        if ($uri->getScheme() === 'https') {
            $this->serverParams['HTTPS'] = '1';
        }
        $auth = $uri->getUserInfo();
        $this->headers['Host'] = [$this->uri->getHost()];
        foreach ($request->getHeaders() as $name => $values) {
            $name = $this->normalizeHeaderName((string)$name);
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
                    $this->serverParams['HTTP_' . strtoupper(str_replace('-', '_', $name))] = implode(',', $values);
                    break;
                default:
                    $this->headers[$name] = $values;
                    $this->serverParams['HTTP_' . strtoupper(str_replace('-', '_', $name))] = implode(',', $values);
                    break;
            }
        }
        if ($auth) {
            $loginPassword = explode(':', $auth, 2);
            $this->serverParams['PHP_AUTH_USER'] = $loginPassword[0];
            if (array_key_exists(1, $loginPassword)) {
                $this->serverParams['PHP_AUTH_PW'] = $loginPassword[1];
            }
        }
        $contentType = strtolower($this->getHeaderLine('Content-Type'));
        $input = $this->parseBody($contentType, $body);
        $this->parsedBody = empty($input['params']) ? null : $input['params'];
        $this->headers = array_replace($this->headers, $input['headers']);
        $this->serverParams['HTTP_CONTENT_TYPE'] = $this->getHeaderLine('Content-Type') ?: 'text/plain';
        $this->files = $input['files'];
        $this->body = $input['body'];
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
        $that->target = (string)$requestTarget;
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
        return $this->serverParams;
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
        return mb_convert_case($name, MB_CASE_TITLE);
    }

//    private function parseBody(string $contentType, string $contents, bool $allowRecursion = false): void
//    {
//        switch ($contentType) {
//            case 'application/x-www-form-urlencoded':
//                parse_str($contents, $parsedForm);
//                $this->parsedBody = $parsedForm;
//                return;
//            case 'application/json':
//                /** @var array|false $parsedJson */
//                $parsedJson = json_decode($contents, true);
//                if (is_array($parsedJson)) {
//                    $this->parsedBody = $parsedJson;
//                }
//                return;
//            default:
//                if (!$allowRecursion) {
//                    return;
//                }
//                $parts = explode('+', $contentType);
//                if (count($parts) >= 2) {
//                    $contentType = 'application/' . $parts[count($parts) - 1];
//                }
//                $this->parseBody($contentType, $contents);
//        }
//    }

//    private function parseMultipartBody(string $boundary, string $contents): void
//    {
//        $parts = array_slice(explode("\r\n$boundary\r\n", explode("\r\n$boundary--\r\n", $contents)[0]), 1);
//        foreach ($parts as $part) {
//            $this->parsePart($part);
//        }
//        $this->headers['Content-Type'] = ['application/x-www-form-urlencoded'];
//        $this->server['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
//        $this->body = new Stream(http_build_query((array)$this->parsedBody));
//    }
//
//    private function parsePart(string $contents): void
//    {
//        $data = explode("\r\n\r\n", $contents, 2);
//        $header = trim($data[0]);
//        if (!$header || !array_key_exists(1, $data)) {
//            return;
//        }
//        $body = trim($data[1]);
//        if (!$body) {
//            return;
//        }
//        $headers = [];
//        foreach (explode("\r\n", $header) as $line) {
//            $parts = explode(':', $line, 2);
//            $key = strtolower(trim($parts[0]));
//            if (!array_key_exists(1, $parts) || !$key) {
//                continue;
//            }
//            $values = array_map('trim', explode(';', $parts[1]));
//            foreach ($values as $value) {
//                $parts = explode('=', $value, 2);
//                $n = trim($parts[0]);
//                if (!$n) {
//                    continue;
//                }
//                if (array_key_exists(1, $parts)) {
//                    $headers[$key]['attr'][$n] = ltrim(rtrim(trim($parts[1]), '"'), '"');
//                } else {
//                    $headers[$key]['values'][] = $n;
//                }
//                if (!array_key_exists('attr', $headers[$key])) {
//                    $headers[$key]['attr'] = [];
//                }
//                if (!array_key_exists('values', $headers[$key])) {
//                    $headers[$key]['values'] = [];
//                }
//            }
//        }
//        if (
//            !array_key_exists('content-disposition', $headers)
//            || !array_key_exists('attr', $headers['content-disposition'])
//            || !array_key_exists('name', $headers['content-disposition']['attr'])
//            || !$headers['content-disposition']['attr']['name']
//        ) {
//            return;
//        }
//        $disposition = $headers['content-disposition'];
//        $name = $disposition['attr']['name'];
//        $filename = array_key_exists('filename', $disposition['attr']) ? $disposition['attr']['filename'] : null;
//        if (is_null($filename)) {
//            if (is_null($this->parsedBody)) {
//                $this->parsedBody = [];
//            }
//            if (is_array($this->parsedBody)) {
//                $this->setField($this->parsedBody, $name, $body);
//            }
//            return;
//        }
//        $mime = 'application/octet-stream';
//        if (
//            array_key_exists('content-type', $headers)
//            && array_key_exists('values', $headers['content-type'])
//            && array_key_exists(0, $headers['content-type']['values'])
//        ) {
//            $mime = $headers['content-type']['values'][0];
//        }
//        $file = $this->parseFile($filename, $mime, $body);
//        $this->setField($this->files, $name, $file);
//    }
//
//    private function parseFile(string $filename, string $contentType, string $contents): UploadedFile
//    {
//        $error = UPLOAD_ERR_OK;
//        if (!$contents && $filename === '') {
//            $error = UPLOAD_ERR_NO_FILE;
//        }
//        return new UploadedFile($filename, $contents, $contentType, $error);
//    }

    /**
     * @param array $bag
     * @param string $field
     * @param mixed $value
     * @return void
     */
//    private function setField(array &$bag, string $field, $value): void
//    {
//        $parts = explode('[', str_replace(']', '', $field));
//        if (count($parts) == 1) {
//            $bag[$field] = $value;
//            return;
//        }
//        $key = $parts[0];
//        $target = &$bag;
//        for ($i = 1; array_key_exists($i, $parts); $i++) {
//            $prev = $key;
//
//            if ($prev === '') {
//                $target[] = [];
//                end($target);
//                $target = &$target[key($target)];
//            } else {
//                if (!isset($target[$prev]) || !is_array($target[$prev])) {
//                    $target[$prev] = [];
//                }
//                $target = &$target[$prev];
//            }
//            $key = $parts[$i];
//        }
//        if ($key === '') {
//            $target[] = $value;
//        } else {
//            $target[$key] = $value;
//        }
//    }

    /**
     * @param string $contentType
     * @param StreamInterface $body
     * @return array{params: array, files: array, headers: array<string, string[]>, body: StreamInterface}
     */
    private function parseBody(string $contentType, StreamInterface $body): array
    {
        $result = [
            'params' => [],
            'files' => [],
            'headers' => [],
            'body' => $body,
        ];

        $type = explode(';', $contentType)[0];
        preg_match('/boundary="?(?<boundary>.*?)"?$/', $contentType, $matches);
        $contents = (string)$body;
        if ($type === 'multipart/form-data' && array_key_exists('boundary', $matches)) {
            $result = array_replace($result, $this->parseMixedBody('--' . $matches['boundary'], $contents));
            $result['headers']['Content-Type'] = ['application/x-www-form-urlencoded'];
            $result['body'] = new Stream(http_build_query($result['params']));
        } else {
            $result['params'] = $this->parseSimpleBody($contentType, $contents);
        }
        return $result;
    }

    private function parseSimpleBody(string $contentType, string $contents): array
    {
        if (!in_array($contentType, ['application/x-www-form-urlencoded', 'application/json'])) {
            $parts = explode('+', $contentType);
            if (count($parts) >= 2) {
                $contentType = 'application/' . $parts[count($parts) - 1];
            }
        }
        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                parse_str($contents, $parsedForm);
                return $parsedForm;
            case 'application/json':
                /** @var array|false $parsedJson */
                $parsedJson = json_decode($contents, true);
                if (is_array($parsedJson)) {
                    return $parsedJson;
                }
                return [];
        }
        return [];
    }

    /**
     * @param string $boundary
     * @param string $contents
     * @return array{params: array, files: array}
     */
    private function parseMixedBody(string $boundary, string $contents): array
    {
        $parts = array_slice(explode("\r\n$boundary\r\n", explode("\r\n$boundary--\r\n", "\r\n$contents")[0]), 1);
        $result = [
            'params' => [],
            'files' => [],
        ];
        foreach ($parts as $part) {
            $parsedPart = $this->parseMixedPart($part);
            if (is_null($parsedPart)) {
                continue;
            }
            if (is_string($parsedPart['data'])) {
                $result['params'] = $this->appendData($result['params'], $parsedPart['field'], $parsedPart['data']);
                continue;
            }
            if ($parsedPart['data'] instanceof UploadedFileInterface) {
                $result['files'] = $this->appendData($result['files'], $parsedPart['field'], $parsedPart['data']);
            }
        }
        return $result;
    }

    /**
     * @param string $contents
     * @return null|array{field: string, data: string|UploadedFileInterface}
     */
    private function parseMixedPart(string $contents): ?array
    {
        $data = explode("\r\n\r\n", $contents, 2);
        $headerString = trim($data[0]);
        if ($headerString === '' || !array_key_exists(1, $data)) {
            return null;
        }

        $body = trim($data[1]);

        /** @var array<string, array{value: string, options: array<string, string>}> $headers */
        $headers = [];
        foreach (explode("\r\n", $headerString) as $line) {
            $headerParts = explode(':', $line, 2);
            $headerName = strtolower(trim($headerParts[0]));
            if (!array_key_exists(1, $headerParts) || empty($headerName)) {
                continue;
            }
            $options = array_map('trim', explode(';', $headerParts[1]));
            $headers[$headerName]['value'] = array_shift($options);
            $headers[$headerName]['options'] = [];
            foreach ($options as $option) {
                $optionParts = explode('=', $option, 2);
                $optionName = trim($optionParts[0]);
                if ($optionName === '') {
                    continue;
                }
                $optionValue = trim($optionParts[1] ?? '');
                if (substr($optionValue, 0, 1) === '"' && substr($optionValue, -1) === '"') {
                    $optionValue = substr($optionValue, 1, -1);
                }
                $headers[$headerName]['options'][$optionName] = trim($optionValue);
            }
        }

        $field = $headers['content-disposition']['options']['name'] ?? null;
        if (is_null($field)) {
            return null;
        }

        $filename = $headers['content-disposition']['options']['filename'] ?? null;
        if (is_null($filename)) {
            return [
                'field' => $field,
                'data' => $body,
            ];
        }
        $mime = $headers['content-type']['value'] ?? 'application/octet-stream';
        $error = UPLOAD_ERR_OK;
        if ($body === '' && $filename === '') {
            $error = UPLOAD_ERR_NO_FILE;
        }

        return [
            'field' => $field,
            'data' => new UploadedFile($filename, $body, $mime, $error),
        ];
    }

    /**
     * @param array $array
     * @param string $field
     * @param string|UploadedFileInterface $data
     * @return array
     */
    private function appendData(array $array, string $field, $data): array
    {
        $path = explode('[', $field, 2);
        $current = $path[0];
        $left = $path[1] ?? null;
        if (is_string($left) && substr($left, -1) !== ']') {
            $left = null;
        }

        if (is_null($left)) {
            if ($current === '') {
                $array[] = $data;
            } else {
                $array[$current] = $data;
            }
            return $array;
        }

        $parts = explode('][', substr($left, 0, -1));
        $next = array_shift($parts);
        if (!empty($parts)) {
            $next .= '[' . implode('][', $parts) . ']';
        }

        if ($current === '') {
            $array[] = $this->appendData([], $next, $data);
        } else {
            $array[$current] = $this->appendData(
                (array_key_exists($current, $array) && is_array($array[$current])) ? $array[$current] : [],
                $next,
                $data
            );
        }
        return $array;
    }
}
