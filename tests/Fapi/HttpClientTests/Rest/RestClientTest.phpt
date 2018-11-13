<?php
declare(strict_types = 1);

/**
 * Test: Fapi\HttpClient\Rest\RestClient
 *
 * @testCase Fapi\HttpClientTests\Rest\RestClientTest
 */

namespace Fapi\HttpClientTests\Rest;

use Fapi\HttpClient\HttpMethod;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Fapi\HttpClient\HttpStatusCode;
use Fapi\HttpClient\MockHttpClient;
use Fapi\HttpClient\Rest\RestClient;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

class RestClientTest extends TestCase
{

	public function testGetAllResources()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/resources',
			HttpMethod::GET,
			[],
			HttpStatusCode::S200_OK,
			'[{"id":1},{"id":2},{"id":3}]'
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$resources = $restClient->getResources('/resources');
		Assert::same([['id' => 1], ['id' => 2], ['id' => 3]], $resources);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	public function testGetFilteredResources()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/resources?foo=bar&bar=1',
			HttpMethod::GET,
			[],
			HttpStatusCode::S200_OK,
			'[{"id":1},{"id":2}]'
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$resources = $restClient->getResources('/resources', ['foo' => 'bar', 'bar' => true, 'baz' => null]);
		Assert::same([['id' => 1], ['id' => 2]], $resources);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	public function testGetResource()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/resources/2?foo=bar',
			HttpMethod::GET,
			[],
			HttpStatusCode::S200_OK,
			'{"id":2}'
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$resource = $restClient->getResource('/resources', 2, ['foo' => 'bar']);
		Assert::same(['id' => 2], $resource);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	public function testGetNonexistingResource()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/resources/3',
			HttpMethod::GET,
			[],
			HttpStatusCode::S404_NOT_FOUND,
			'{"status":"error","error":{"message":"Not Found"}}'
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$resource = $restClient->getResource('/resources', 3);
		Assert::null($resource);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	public function testGetSingularResource()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/singular-resource?foo=bar',
			HttpMethod::GET,
			[],
			HttpStatusCode::S200_OK,
			'{"foo":true}'
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$resource = $restClient->getSingularResource('/singular-resource', ['foo' => 'bar']);
		Assert::same(['foo' => true], $resource);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	public function testCreateResource()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/resources',
			HttpMethod::POST,
			['json' => ['foo' => 'bar']],
			HttpStatusCode::S201_CREATED,
			'{"id":7,"foo":"bar"}'
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$resource = $restClient->createResource('/resources', ['foo' => 'bar']);
		Assert::same(['id' => 7, 'foo' => 'bar'], $resource);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	public function testUpdateResource()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/resources/7',
			HttpMethod::PUT,
			['json' => ['bar' => 'baz']],
			HttpStatusCode::S200_OK,
			'{"id":7,"foo":"bar","bar":"baz"}'
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$resource = $restClient->updateResource('/resources', 7, ['bar' => 'baz']);
		Assert::same(['id' => 7, 'foo' => 'bar', 'bar' => 'baz'], $resource);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	public function testDeleteResource()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/resources/7',
			HttpMethod::DELETE,
			[],
			HttpStatusCode::S200_OK,
			'null'
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$restClient->deleteResource('/resources', 7);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	public function testDeleteResourceWithNoContentStatusCode()
	{
		$httpClient = $this->createMockHttpClient(
			'https://example.com/resources/7',
			HttpMethod::DELETE,
			[],
			HttpStatusCode::S204_NO_CONTENT,
			''
		);

		$restClient = new RestClient('admin', 'xxx', 'https://example.com/', $httpClient);
		$restClient->deleteResource('/resources', 7);
		Assert::true($httpClient->wereAllHttpRequestsSent());
	}

	/**
	 * @param string $url
	 * @param string $method
	 * @param mixed[] $options
	 * @param int $statusCode
	 * @param string $responseBody
	 * @return MockHttpClient
	 */
	private function createMockHttpClient(string $url, string $method, array $options, int $statusCode, string $responseBody): MockHttpClient
	{
		$commonOptions = [
			'auth' => ['admin', 'xxx'],
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			],
		];

		$httpRequest = new HttpRequest($url, $method, $commonOptions + $options);

		$httpResponse = new HttpResponse(
			$statusCode,
			['Content-Type' => ['application/json']],
			$responseBody
		);

		$httpClient = new MockHttpClient();
		$httpClient->add($httpRequest, $httpResponse);

		return $httpClient;
	}

}

(new RestClientTest())->run();
