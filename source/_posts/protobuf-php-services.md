---
title: "Writing Protobuf Services in PHP"
date: 2018-01-27T14:59:42-05:00
extends: _layouts.post
section: content
tags: [php, apis]
---


# Introduction

Lately I've been investigating [Protobuf](https://developers.google.com/protocol-buffers/) as a replacement for JSON RPC services.  If you aren't familiar with Protobuf, it's a language neutral serialization format from Google.  It's most commonly associated with Google's RPC framework [gRPC](https://grpc.io/) but it can be used standalone too.  In this guide we are going to build a simple calculator RPC service using nothing but the Protobuf compiler and PHP.

## Example Code

The example code for this article is available [here](https://github.com/yuloh/protobuf-php-service-tutorial).

# Writing Protos

Protobuf definitions are written in a text file with the `.proto` extension.  We are going to define our calculator service and all of it's messages in a single proto file.  Let's start by defining our service.

I like to put my protos in a separate `proto` directory, so I am going to make a new file at `proto/math.proto`.

```bash
mkdir proto
touch proto/math.proto
```

## Services

```protobuf
syntax = "proto3";

package yuloh.math;

option php_generic_services = true;

service Calculator {
    rpc add (AddRequest) returns (AddReply) {}
    rpc subtract (SubtractRequest) returns (SubtractReply) {}
}
```

The first line tells the protobuf compiler which version we want to use.  PHP only supports proto3 syntax.  The `package` specifier is optional but a good idea to avoid name clashes.  In the generated PHP code it will be converted to the namespace. the `php_generic_services` option tells the compiler we want to generate an interface for the service.  It's optional because you might be using a plugin like gRPC that generates it's own service classes.


## Messages

Now we need to define the request and reply messages.  This should be pretty straightforward but you may be wondering about the value assignment after each field name.  The number is the **tag**, and it's used to uniquely identify the field when the message is serialized to the binary format.

```protobuf
message AddRequest {
    int32 x = 1;
    int32 y = 2;
}

message AddReply {
    int32 sum = 1;
}

message SubtractRequest {
    int32 x = 1;
    int32 y = 2;
}

message SubtractReply {
    int32 diff = 1;
}
```

# Compilation

For the next step you will need to install the protobuf compiler.  Head over to the [installation instructions](https://github.com/google/protobuf#protocol-compiler-installation) and install it.  You will need at least v3.4.0 to follow along.  You can check with `protoc --version`.

The protobuf compiler will read our proto definitions and generate a few classes for us.  We are going to put the generated code in a separate directory from our application code.  Make the directory (otherwise the compiler will complain) and run protoc.

```bash
mkdir gen
protoc  --php_out=./gen ./proto/math.proto
```

You should see a generated class for every message we defined as well as an interface for the service.


# Composer Setup

## Autoloading

The generated code is organized using PSR-4 standards and can be autoloaded with composer.  There are currently 2 namespaces that need to be autoloaded - the `Yuloh\Math` namespace for our service and `GPBMetadata\Proto`, which contains internal files required by the Protobuf runtime.  If you don't want to manually map namespaces each time you can just use the empty namespace.

```json
{
    "autoload": {
      "psr-4": {
        "": "gen"
      }
    }
}
```

## Protobuf Runtime

You will also need the protobuf runtime for PHP.  You can either install the runtime as a C extension or use the PHP package.  The PHP can be installed with composer.  Follow the [instructions](https://github.com/google/protobuf/tree/master/php) if you want to use the extension.

```bash
composer require google/protobuf
```

# Writing The Service

The interface has been generated but we still need to implement it.  Create a `src` directory and inside of it create the calculator service.

```php
<?php
// src/Calculator.php

namespace Yuloh\Math;

class Calculator implements CalculatorInterface
{
    public function add(AddRequest $request): AddReply
    {
      $sum = $request->getX() + $request->getY();

      return (new AddReply())->setSum($sum);
    }

    public function subtract(\Yuloh\Math\SubtractRequest $request): SubtractReply
    {
      $diff = $request->getX() - $request->getY();

      return (new SubtractReply())->setDiff($diff);
    }
}
```

The service implements a method for each `rpc` we defined in the proto.  The contract defined in the proto file is enforced by the generated interface.

# A Simple HTTP Server

Let's test our service by making it available over HTTP.  Our HTTP script will check if the path is `/add` or `/subtract`.  if so it will deserialize the request, call the service, and return the serialized reply.  Save this to `public/index.php`.

A protocol buffer can be serialized to text, JSON, or an efficient binary format.  You should always prefer the binary format for production.  Not just for efficiency but also because it allows you to benefit from protobuf's backwards and forwards compatibility.

Our server is using the binary format so we need to use  `mergeFromString` to deserialize the message and `serializeToString` to encode it.  If we wanted to support JSON we would use `mergeFromJsonString` and `serializeToJsonString` instead.


```php
<?php
// public/index.php

use Yuloh\Math\AddRequest;
use Yuloh\Math\Calculator;
use Yuloh\Math\SubtractRequest;

require '../vendor/autoload.php';

$method = ltrim(rawurldecode($_SERVER['REQUEST_URI']), '/');

switch ($method) {
  case 'add':
    $request = new AddRequest();
    $request->mergeFromString(file_get_contents('php://input'));
    $reply = (new Calculator())->add($request);
    echo $reply->serializeToString();
    break;
  case 'subtract':
    $request = new SubtractRequest();
    $request->mergeFromString(file_get_contents('php://input'));
    $reply = (new Calculator())->subtract($request);
    echo $reply->serializeToString();
    break;
  default:
    http_response_code(404);
}
```

# Implementing the Client

We can use the same service interface to implement our client.  Our client implements a method corresponding to each method on the server and internally makes a cURL request to the server.

```php
<?php
// src/CalculatorClient.php
namespace Yuloh\Math;

use Google\Protobuf\Internal\Message;

class CalculatorClient implements CalculatorInterface
{
    public function add(AddRequest $request): AddReply
    {
      $reply = new AddReply();
      $reply->mergeFromString($this->makeRequest($request, 'add'));

      return $reply;
    }


    public function subtract(SubtractRequest $request): SubtractReply
    {
      $reply = new SubtractReply();
      $reply->mergeFromString($this->makeRequest($request, 'subtract'));

      return $reply;
    }

    private function makeRequest(Message $message, string $method): string
    {
      $body = $message->serializeToString();

      $ch = curl_init("http://localhost:8000/{$method}");

      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
      ]);

      $response = curl_exec($ch);

      curl_close($ch);

      return $response;
    }
}
```

# Making Requests

Once you've written the service, the client, and the server you can make requests.  Lets write a simple test script that will use our client.  You can just put this in the root of the project.

```php
// make_requests.php
<?php

use Yuloh\Math\AddRequest;
use Yuloh\Math\CalculatorClient;
use Yuloh\Math\SubtractRequest;

require __DIR__ . '/vendor/autoload.php';

$calculator = new CalculatorClient();

$req   = (new AddRequest())->setX(2)->setY(4);
$reply = $calculator->add($req);

echo '2 + 4 = ' . $reply->getSum() . PHP_EOL;

$req   = (new SubtractRequest())->setX(5)->setY(4);
$reply = $calculator->subtract($req);

echo '5 - 4 = ' . $reply->getDiff() . PHP_EOL;
```

Start PHP's built in webserver and point it to the public path of our server.


```bash
php -S localhost:8000 -t ./public
```

Now in another terminal run the client.  You should see the result of your operations!

```bash
$ php ./make_requests.php
2 + 4 = 6
5 - 4 = 1
```

# Conclusion

We now have a basic RPC system with the contract (mostly) enforced by our protobuf definitions.  Our services get to use simple typesafe message objects and our client and server use a shared interface.  It would be easy to add support for other languages if we needed to.

We did have to write a lot of boilerplate.  The only real business logic lives in our service; the rest could be generated.  That is exactly what a RPC framework like [gRPC](https://grpc.io/) does.  gRPC provides a [compiler plugin](https://developers.google.com/protocol-buffers/docs/reference/other) that generates all the boilerplate.  All you have to do is write the business logic.  Not only does using a RPC framework save you time, it also makes sure the client and server agree on things that aren't enforced by the proto; things like authentication, error handling, and routing.

Unfortunately your options are limited as a PHP developer.  gRPC doesn't support PHP servers yet, but they are making steady progress.  If you write your own compiler plugin you will have to use a different language because PHP is not supported.

Is it worth switching to protobuf for RPC services?  I think so, if you are using a RPC framework that generates all the boilerplate for you.  Protobuf with a solid RPC framework lets you focus on business logic and work with simple objects.  It's incredibly liberating to write a simple proto file and have most of my code already written after working with JSON + JSON Schema for the past few years.