[![Latest Stable Version](https://img.shields.io/packagist/v/webclient/fake-http-client.svg?style=flat-square)](https://packagist.org/packages/webclient/fake-http-client)
[![Total Downloads](https://img.shields.io/packagist/dt/webclient/fake-http-client.svg?style=flat-square)](https://packagist.org/packages/webclient/fake-http-client/stats)
[![License](https://img.shields.io/packagist/l/webclient/fake-http-client.svg?style=flat-square)](https://github.com/phpwebclient/fake-http-client/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/webclient/fake-http-client.svg?style=flat-square)](https://php.net)

# webclient/fake-http-client

Mock for PSR-18 HTTP client

# Install

Add package to project

```bash
composer require --dev webclient/fake-http-client:^3.0
```

Set autoload

```php
<?php

include 'vendor/autoload.php';
```

# Using

```php
<?php

use Webclient\Fake\FakeHttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** 
 * @var RequestHandlerInterface $handler your mock handler 
 * @var RequestInterface $request your tested request
 */
$client = new FakeHttpClient($handler);

$response = $client->sendRequest($request);
```
# Handlers

## SpecHandler

This package provides universal handler `\Webclient\Fake\Handler\SpecHandler\SpecHandler` 
and builder `\Webclient\Fake\Handler\SpecHandler\SpecHandlerBuilder`. 
With it, you can customize the client for almost any need.

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Webclient\Fake\FakeHttpClient;
use Webclient\Fake\Handler\SpecHandler\SpecHandlerBuilder;
use Webclient\Fake\Handler\SpecHandler\Rule;

$builder = SpecHandlerBuilder::create();

$builder
    ->route(function (Rule $rule) {
        $rule->notEqual('header.authorization', 'bearer xxx');
        $rule->oneOf(function (Rule $rule) {
            $rule->allOf(function (Rule $rule) {
                $rule->equal('uri.path', '/api/v1/posts');
                $rule->equal('method', 'POST');
            });
            $rule->allOf(function (Rule $rule) {
                $rule->match('uri.path', '^/api/v1/posts/([a-zA-Z0-9\-]+)$');
                $rule->match('method', '^(PUT|DELETE)$');
            });
        });
    })
    ->response(function (ResponseInterface $response): ResponseInterface {
        return $response->withStatus(403);
    });

$handler = $builder->build();

$client = new FakeHttpClient($handler);
```

## SimpleRoutingHandler

This package provides simple routing.

```php
<?php

use Webclient\Fake\FakeHttpClient;
use Webclient\Fake\Handler\SimpleRoutingHandler\SimpleRoutingHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** 
 * @var RequestHandlerInterface $notFoundHandler default handler, error 404
 * @var RequestHandlerInterface $entityCreatedHandler handler for 201 request (POST /entities) 
 * @var RequestHandlerInterface $entityHandler handler for 200 request (GET /entities/1)
 * @var RequestHandlerInterface $entityDeletedHandler handler for 204 request (DELETE /entities/2)
 * @var RequestInterface $errorRequest request for unused uri (GET /users)
 * @var RequestInterface $entityCreatingRequest request for creating entity (POST /entities)
 * @var RequestInterface $entityRequest request for getting entity (GET /entities/1)
 * @var RequestInterface $entityDeletingRequest request for deleting entity (DELETE /entities/2)
 */

$handler = new SimpleRoutingHandler($notFoundHandler);
$handler
    ->route(['GET', 'HEAD'], '/entities/1', $entityHandler)
    ->route(['POST'], '/entities', $entityCreatedHandler)
    ->route(['DELETED'], '/entities/2', $entityDeletedHandler)
;
$client = new FakeHttpClient($handler);

$response1 = $client->sendRequest($errorRequest); // returns error 404
$response2 = $client->sendRequest($entityCreatingRequest); // returns success response 201
$response3 = $client->sendRequest($entityRequest); // returns success response 200
$response4 = $client->sendRequest($entityDeletingRequest); // returns success response 204
```

# Nuance

If you pass the `\Psr\Http\Message\ServerRequestInterface` object to client and want the handler to receive it as is,
 add the attribute `\Webclient\Fake\Client::NO_REPLACE_ATTRIBUTE`:

```php
<?php

use Webclient\Fake\FakeHttpClient;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** 
 * @var RequestHandlerInterface $handler your mock handler 
 * @var ServerRequestInterface $request your tested request
 */
$client = new FakeHttpClient($handler);

$response = $client->sendRequest($request->withAttribute(FakeHttpClient::NO_REPLACE_ATTRIBUTE, true));
```
