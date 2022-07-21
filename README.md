[![Latest Stable Version](https://img.shields.io/packagist/v/webclient/fake-http-client.svg?style=flat-square)](https://packagist.org/packages/webclient/fake-http-client)
[![Total Downloads](https://img.shields.io/packagist/dt/webclient/fake-http-client.svg?style=flat-square)](https://packagist.org/packages/webclient/fake-http-client/stats)
[![License](https://img.shields.io/packagist/l/webclient/fake-http-client.svg?style=flat-square)](https://github.com/phpwebclient/fake-http-client/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/webclient/fake-http-client/v1.0.0.svg?style=flat-square)](https://php.net)

# webclient/fake-http-client

Mock for PSR-18 HTTP client

# Install

Add package to project

```bash
composer require --dev webclient/fake-http-client:^2.0
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

# Routing

This package provides simple routing.

```php
<?php

use Webclient\Fake\FakeHttpClient;
use Webclient\Fake\Handler\SimpleRoutingHandler;
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
