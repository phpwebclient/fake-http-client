<?php

declare(strict_types=1);

namespace Tests\Webclient\Fake\Unit\SpecHandler;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Tests\Webclient\Fake\Tools\Condition\StaticCondition;
use Webclient\Fake\Handler\SpecHandler\Exception\BadResponseFiller;
use Webclient\Fake\Handler\SpecHandler\Route;

final class RouteTest extends TestCase
{
    public function testCondition()
    {
        $condition = new StaticCondition(true);
        $route = new Route($condition);
        Assert::assertSame($condition, $route->getCondition());
    }

    public function testResponse()
    {
        $condition = new StaticCondition(true);
        $route = new Route($condition);
        $route->response(function (ResponseInterface $response): ResponseInterface {
            return $response->withStatus(404, 'Page not found');
        });
        $response = $route->createResponse(new Response(200));
        Assert::assertSame(404, $response->getStatusCode());
        Assert::assertSame('Page not found', $response->getReasonPhrase());
    }

    public function testResponseWithBadCallable()
    {
        $condition = new StaticCondition(true);
        $route = new Route($condition);
        $route->response(function () {
            return 'ok';
        });
        $this->expectException(BadResponseFiller::class);
        $route->createResponse(new Response(200));
    }
}
