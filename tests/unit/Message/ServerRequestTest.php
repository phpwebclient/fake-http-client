<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\Message;

use Nyholm\Psr7\Request;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Webclient\Fake\Message\ServerRequest;

final class ServerRequestTest extends TestCase
{
    public function testConstruct()
    {
        $request = new Request('GET', 'http://localhost/path?query=yes#fragment', ['Accept' => 'application/json']);
        $serverRequest = new ServerRequest($request);

        Assert::assertSame((string)$request->getUri(), (string)$serverRequest->getUri());
        Assert::assertSame($request->hasHeader('accept'), $serverRequest->hasHeader('accept'));
        Assert::assertSame($request->getHeaderLine('accept'), $serverRequest->getHeaderLine('accept'));
        Assert::assertSame($request->getProtocolVersion(), $serverRequest->getProtocolVersion());
        Assert::assertSame($request->getMethod(), $serverRequest->getMethod());
        Assert::assertSame($request->getRequestTarget(), $serverRequest->getRequestTarget());
    }

    public function testServerParams()
    {
        $request = new Request(
            'POST',
            'https://user:pass@localhost/path?query=yes#fragment',
            ['Content-Type' => 'application/json'],
            '{"ok": true}'
        );
        $serverRequest = new ServerRequest($request);

        $serverParams = $serverRequest->getServerParams();
        Assert::assertSame((string)$request->getUri(), $serverParams['REQUEST_URI'] ?? null);
        Assert::assertSame($request->getUri()->getQuery(), $serverParams['QUERY_STRING'] ?? null);
        Assert::assertSame($request->getMethod(), $serverParams['REQUEST_METHOD'] ?? null);
        Assert::assertSame($request->getUri()->getHost(), $serverParams['SERVER_NAME'] ?? null);
        Assert::assertSame($request->getProtocolVersion(), $serverParams['SERVER_PROTOCOL'] ?? null);
        Assert::assertSame('1', $serverParams['HTTPS'] ?? null);
        Assert::assertSame('user', $serverParams['PHP_AUTH_USER'] ?? null);
        Assert::assertSame('pass', $serverParams['PHP_AUTH_PW'] ?? null);
        Assert::assertSame('yes', $serverRequest->getQueryParams()['query'] ?? null);
    }

    public function testCookies()
    {
        $request = new Request(
            'GET',
            'https://user:pass@localhost/path?query=yes#fragment',
            ['Cookie' => 'sess_id=abc'],
        );
        $serverRequest = new ServerRequest($request);

        $cookies = $serverRequest->getCookieParams();
        Assert::assertCount(1, $cookies);
        Assert::assertSame('abc', $cookies['sess_id'] ?? null);
    }

    public function testBodyParsingForm()
    {
        $request = new Request(
            'POST',
            'https://user:pass@localhost/path?query=yes#fragment',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            'a=1&b=2'
        );
        $serverRequest = new ServerRequest($request);

        $parsedBody = $serverRequest->getParsedBody();
        Assert::assertSame('application/x-www-form-urlencoded', $serverRequest->getHeaderLine('content-type'));
        Assert::assertSame('1', $parsedBody['a'] ?? null);
        Assert::assertSame('2', $parsedBody['b'] ?? null);
    }

    public function testBodyParsingJson()
    {
        $request = new Request(
            'POST',
            'https://user:pass@localhost/path?query=yes#fragment',
            ['Content-Type' => 'application/json'],
            '{"a": 1, "b": "2"}'
        );
        $serverRequest = new ServerRequest($request);

        $parsedBody = $serverRequest->getParsedBody();
        Assert::assertSame('application/json', $serverRequest->getHeaderLine('content-type'));
        Assert::assertSame(1, $parsedBody['a'] ?? null);
        Assert::assertSame('2', $parsedBody['b'] ?? null);
    }

    public function testBodyParsingMultipart()
    {
        $body = implode("\r\n", [
            '--aaaabbbbcccc',
            'Content-Disposition: form-data; name="a"',
            '',
            '1',
            '--aaaabbbbcccc',
            'Content-Disposition: form-data; name="b"',
            '',
            '2',
            '--aaaabbbbcccc--',
            '',
        ]);

        $request = new Request(
            'POST',
            'https://user:pass@localhost/path?query=yes#fragment',
            ['Content-Type' => 'multipart/form-data; boundary=aaaabbbbcccc'],
            $body
        );
        $serverRequest = new ServerRequest($request);

        $parsedBody = $serverRequest->getParsedBody();
        Assert::assertSame('application/x-www-form-urlencoded', $serverRequest->getHeaderLine('content-type'));
        Assert::assertSame('1', $parsedBody['a'] ?? null);
        Assert::assertSame('2', $parsedBody['b'] ?? null);
    }

    public function testBodyParsingMultipartDeep()
    {
        $body = implode("\r\n", [
            '--aaaabbbbcccc',
            'Content-Disposition: form-data; name="array[]"',
            '',
            'item 1',
            '--aaaabbbbcccc',
            'Content-Disposition: form-data; name="array[]"',
            '',
            'item 2',
            '--aaaabbbbcccc',
            'Content-Disposition: form-data; name="object[deep][property]"',
            '',
            'value',
            '--aaaabbbbcccc--',
            '',
        ]);

        $request = new Request(
            'POST',
            'https://user:pass@localhost/path?query=yes#fragment',
            ['Content-Type' => 'multipart/form-data; boundary=aaaabbbbcccc'],
            $body
        );
        $serverRequest = new ServerRequest($request);

        $parsedBody = $serverRequest->getParsedBody();
        Assert::assertSame('application/x-www-form-urlencoded', $serverRequest->getHeaderLine('content-type'));
        Assert::assertSame('item 1', $parsedBody['array'][0] ?? null);
        Assert::assertSame('item 2', $parsedBody['array'][1] ?? null);
        Assert::assertSame('value', $parsedBody['object']['deep']['property'] ?? null);
    }

    public function testBodyParsingMultipartFiles()
    {
        $body = implode("\r\n", [
            '--aaaabbbbcccc',
            'Content-Disposition: form-data; name="file[]"; filename="file1.txt"',
            'Content-Type: text/plain; charset=UTF-8',
            '',
            'file number 1',
            '--aaaabbbbcccc',
            'Content-Disposition: form-data; name="file[]"; filename="file2.txt"',
            'Content-Type: text/plain; charset=UTF-8',
            '',
            'file number 2',
            '--aaaabbbbcccc--',
            '',
        ]);

        $request = new Request(
            'POST',
            'https://user:pass@localhost/path?query=yes#fragment',
            ['Content-Type' => 'multipart/form-data; boundary=aaaabbbbcccc'],
            $body
        );
        $serverRequest = new ServerRequest($request);

        /** @var UploadedFileInterface[][] $uploadedFiles */
        $uploadedFiles = $serverRequest->getUploadedFiles();
        Assert::assertSame('application/x-www-form-urlencoded', $serverRequest->getHeaderLine('content-type'));
        foreach ($uploadedFiles['file'] as $num => $uploadedFile) {
            Assert::assertInstanceOf(UploadedFileInterface::class, $uploadedFile);
            Assert::assertSame('text/plain', $uploadedFile->getClientMediaType());
            Assert::assertSame(sprintf('file%d.txt', $num + 1), $uploadedFile->getClientFilename());
            Assert::assertSame(sprintf('file number %d', $num + 1), (string)$uploadedFile->getStream());
        }
    }
}
