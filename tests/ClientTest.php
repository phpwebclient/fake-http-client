<?php

declare(strict_types=1);

namespace Webclient\Tests\Fake;

use Webclient\Fake\Client;
use Webclient\Stuff\Fake\Handler\ErrorHandler;
use Webclient\Stuff\Fake\Handler\UniversalHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;

class ClientTest extends TestCase
{

    /**
     * @var Psr17Factory
     */
    private $factory;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function testSuccessWithRequest()
    {
        $request = $this->factory->createRequest('GET', 'http://phpunit.de/?return=302&redirect=https://phpunit.de');
        $client = new Client(new UniversalHandler($this->factory));
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
        $request = $this->factory->createServerRequest(
            'GET',
            'https://phpunit.de',
            []
        );
        $client = new Client(new UniversalHandler($this->factory));
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
        $request = $this->factory->createServerRequest(
            'GET',
            'https://phpunit.de',
            []
        );
        $client = new Client(new UniversalHandler($this->factory));
        $response = $client->sendRequest($request->withAttribute(Client::NO_REPLACE_ATTRIBUTE, true));
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
        $request = $this->factory->createRequest('GET', '/');
        $client = new Client(new ErrorHandler());
        $this->expectException(NetworkExceptionInterface::class);
        $client->sendRequest($request);
    }
}
