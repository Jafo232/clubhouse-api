<?php

declare(strict_types=1);

namespace JDecool\Clubhouse\Tests;

use Http\Client\Common\HttpMethodsClient;
use JDecool\Clubhouse\{
    Client,
    ClubhouseException as LegacyClubhouseException,
    Exception\ClubhouseException,
    Exception\ResourceNotExist,
    Exception\SchemaMismatch,
    Exception\TooManyRequest,
    Exception\Unprocessable
};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ClientTest extends TestCase
{
    public function testCreateV1(): void
    {
        $client = Client::createV1(
            $this->createMock(HttpMethodsClient::class),
            'foo'
        );

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testCreateV2(): void
    {
        $client = Client::createV2(
            $this->createMock(HttpMethodsClient::class),
            'foo'
        );

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testCreateBeta(): void
    {
        $client = Client::createBeta(
            $this->createMock(HttpMethodsClient::class),
            'foo'
        );

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testContruct(): void
    {
        $client = new Client(
            $this->createMock(HttpMethodsClient::class),
            'http://domain.tld',
            'foo'
        );

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructWithInvalidUrl(): void
    {
        $this->expectException(RuntimeException::class);

        new Client(
            $this->createMock(HttpMethodsClient::class),
            'foo',
            'bar'
        );
    }

    /**
     * @dataProvider httpMethods
     */
    public function testCallForV1(string $method, int $statusCode, ...$params): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn($statusCode);
        $response->method('getBody')
            ->willReturn(json_encode([]));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method($method)
            ->with('https://api.clubhouse.io/api/v1/resource?token=foo')
            ->willReturn($response);

        $client = Client::createV1($http, 'foo');
        $resource = call_user_func([$client, $method], 'resource', $params);

        $this->assertIsArray($resource);
    }

    /**
     * @dataProvider httpMethods
     */
    public function testCallForV2(string $method, int $statusCode, ...$params): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn($statusCode);
        $response->method('getBody')
            ->willReturn(json_encode([]));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method($method)
            ->with('https://api.clubhouse.io/api/v2/resource?token=foo')
            ->willReturn($response);

        $client = Client::createV2($http, 'foo');
        $resource = call_user_func([$client, $method], 'resource', $params);

        $this->assertIsArray($resource);
    }

    /**
     * @dataProvider httpMethods
     */
    public function testCallForBeta(string $method, int $statusCode, ...$params): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn($statusCode);
        $response->method('getBody')
            ->willReturn(json_encode([]));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method($method)
            ->with('https://api.clubhouse.io/api/beta/resource?token=foo')
            ->willReturn($response);

        $client = Client::createBeta($http, 'foo');
        $resource = call_user_func([$client, $method], 'resource', $params);

        $this->assertIsArray($resource);
    }

    public function testGetCall(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(200);
        $response->method('getBody')
            ->willReturn(json_encode([]));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('get')
            ->with('http://domain.tld/resource/42?token=foo')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');
        $resource = $client->get('resource/42');

        $this->assertIsArray($resource);
    }

    public function testGetCallWithError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(400);
        $response->method('getBody')
            ->willReturn(json_encode(['message' => 'Bad request']));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('get')
            ->with('http://domain.tld/resource/42?token=foo')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException(ClubhouseException::class);
        $this->expectException(LegacyClubhouseException::class);
        $this->expectExceptionMessage('Bad request');

        $client->get('resource/42');
    }

    public function testGetCallWithErrorWithDefaultMessage(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(500);
        $response->method('getBody')
            ->willReturn('');

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('get')
            ->with('http://domain.tld/resource/42?token=foo')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException(ClubhouseException::class);
        $this->expectException(LegacyClubhouseException::class);
        $this->expectExceptionMessage('An error occured.');

        $client->get('resource/42');
    }

    public function testPostCall(): void
    {
        $requestParams = [
            'foo' => 'bar',
            'qux' => 'quux',
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(201);
        $response->method('getBody')
            ->willReturn(json_encode([]));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('post')
            ->with(
                'http://domain.tld/resource?token=foo',
                ['Content-Type' => 'application/json'],
                json_encode($requestParams)
            )
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');
        $resource = $client->post('resource', $requestParams);

        $this->assertIsArray($resource);
    }

    public function testPostCallWithError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(400);
        $response->method('getBody')
            ->willReturn(json_encode(['message' => 'Bad request']));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException(ClubhouseException::class);
        $this->expectException(LegacyClubhouseException::class);
        $this->expectExceptionMessage('Bad request');

        $client->post('resource', []);
    }

    public function testPostCallWithErrorWithDefaultMessage(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(500);
        $response->method('getBody')
            ->willReturn('');

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException(ClubhouseException::class);
        $this->expectException(LegacyClubhouseException::class);
        $this->expectExceptionMessage('An error occured.');

        $client->post('resource', []);
    }

    public function testPutCall(): void
    {
        $requestParams = [
            'foo' => 'bar',
            'qux' => 'quux',
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(200);
        $response->method('getBody')
            ->willReturn(json_encode([]));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('put')
            ->with(
                'http://domain.tld/resource/42?token=foo',
                ['Content-Type' => 'application/json'],
                json_encode($requestParams)
            )
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');
        $resource = $client->put('resource/42', $requestParams);

        $this->assertIsArray($resource);
    }

    public function testPutCallWithError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(400);
        $response->method('getBody')
            ->willReturn(json_encode(['message' => 'Bad request']));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('put')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException(ClubhouseException::class);
        $this->expectException(LegacyClubhouseException::class);
        $this->expectExceptionMessage('Bad request');

        $client->put('resource/42', []);
    }

    public function testPutCallWithErrorWithDefaultMessage(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(500);
        $response->method('getBody')
            ->willReturn('');

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('put')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException(ClubhouseException::class);
        $this->expectException(LegacyClubhouseException::class);
        $this->expectExceptionMessage('An error occured.');

        $client->put('resource/42', []);
    }

    public function testDeleteCall(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(204);
        $response->method('getBody')
            ->willReturn(json_encode(null));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('delete')
            ->with('http://domain.tld/resource?token=foo')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');
        $this->assertNull($client->delete('resource'));
    }

    public function testDeleteCallWithError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(400);
        $response->method('getBody')
            ->willReturn(json_encode(['message' => 'Bad request']));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('delete')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException(ClubhouseException::class);
        $this->expectException(LegacyClubhouseException::class);
        $this->expectExceptionMessage('Bad request');

        $client->delete('resource/42', []);
    }

    public function testDeleteCallWithErrorWithDefaultMessage(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn(500);
        $response->method('getBody')
            ->willReturn('');

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method('delete')
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException(ClubhouseException::class);
        $this->expectException(LegacyClubhouseException::class);
        $this->expectExceptionMessage('An error occured.');

        $client->delete('resource/42', []);
    }

    /**
     * @dataProvider exceptionCalls
     */
    public function testCallsThrowAnException(string $method, int $statusCode, string $exceptionClass, ...$params): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn($statusCode);
        $response->method('getBody')
            ->willReturn(json_encode([]));

        $http = $this->createMock(HttpMethodsClient::class);
        $http->expects($this->once())
            ->method($method)
            ->willReturn($response);

        $client = new Client($http, 'http://domain.tld', 'foo');

        $this->expectException($exceptionClass);

        call_user_func([$client, $method], 'resource', $params);
    }

    public function exceptionCalls(): array
    {
        return [
            ['get', 404, ResourceNotExist::class],
            ['post', 404, ResourceNotExist::class, []],
            ['put', 404, ResourceNotExist::class, []],
            ['delete', 404, ResourceNotExist::class],

            ['get', 400, SchemaMismatch::class],
            ['post', 400, SchemaMismatch::class, []],
            ['put', 400, SchemaMismatch::class, []],
            ['delete', 400, SchemaMismatch::class],

            ['get', 429, TooManyRequest::class],
            ['post', 429, TooManyRequest::class, []],
            ['put', 429, TooManyRequest::class, []],
            ['delete', 429, TooManyRequest::class],

            ['get', 422, Unprocessable::class],
            ['post', 422, Unprocessable::class, []],
            ['put', 422, Unprocessable::class, []],
            ['delete', 422, Unprocessable::class],
        ];
    }

    public function httpMethods(): array
    {
        return [
            ['get', 200],
            ['post', 201, []],
            ['put', 200, []],
            ['delete', 204],
        ];
    }
}
