<?php

declare(strict_types=1);

namespace Webclient\Tests\Fake;

use Nyholm\Psr7\Factory\Psr17Factory;
use Webclient\Fake\FakeHttpClient;
use Webclient\Stuff\Fake\Handler\ErrorHandler;
use Webclient\Stuff\Fake\Handler\UniversalHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;

class FakeHttpClientTest extends TestCase
{
    private Psr17Factory $factory;

    /**
     * @throws ClientExceptionInterface
     */
    public function testSuccessWithRequest()
    {
        $this->init();
        $request = $this->factory->createRequest('GET', 'http://phpunit.de/?return=302&redirect=https://phpunit.de');
        $client = new FakeHttpClient(new UniversalHandler($this->factory));
        $response = $client->sendRequest($request);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://phpunit.de', $response->getHeaderLine('Location'));
        $this->assertTrue(true);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function testSuccessWithServerRequest()
    {
        $this->init();
        $request = $this->factory->createServerRequest(
            'GET',
            'https://phpunit.de',
            []
        );
        $client = new FakeHttpClient(new UniversalHandler($this->factory));
        $response = $client->sendRequest($request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->__toString(), true);
        $this->assertArrayHasKey('server', $data);
        $this->assertNotEmpty($data['server']);
        $this->assertArrayHasKey('HTTP_HOST', $data['server']);
        $this->assertTrue(true);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function testSuccessWithPreparedServerRequest()
    {
        $this->init();
        $request = $this->factory->createServerRequest(
            'GET',
            'https://phpunit.de',
            []
        );
        $client = new FakeHttpClient(new UniversalHandler($this->factory));
        $response = $client->sendRequest($request->withAttribute(FakeHttpClient::NO_REPLACE_ATTRIBUTE, true));
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->__toString(), true);
        $this->assertArrayHasKey('server', $data);
        $this->assertEmpty($data['server']);
        $this->assertTrue(true);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function testFailWithNetworkError()
    {
        $this->init();
        $request = $this->factory->createRequest('GET', '/');
        $client = new FakeHttpClient(new ErrorHandler());
        $this->expectException(NetworkExceptionInterface::class);
        $client->sendRequest($request);
    }

    private function init()
    {
        $this->factory = new Psr17Factory();
    }
}
