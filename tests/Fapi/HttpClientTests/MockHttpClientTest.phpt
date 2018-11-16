<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Fapi\HttpClient\InvalidArgumentException;
use Fapi\HttpClient\MockHttpClient;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

final class MockHttpClientTest extends \Tester\TestCase
{

	public function testNoRequests()
	{
		$mockClient = new MockHttpClient();

		Assert::exception(static function () use ($mockClient) {
			$mockClient->sendHttpRequest(new HttpRequest('not.match.com/1'));
		}, InvalidArgumentException::class,
			'Invalid HTTP request. No more requests found.');
	}

	public function testUrlNotMatch()
	{
		$mockClient = new MockHttpClient();
		$mockClient->add(new HttpRequest('not.match.com'), new HttpResponse(200, [], ''));

		Assert::exception(static function () use ($mockClient) {
			$mockClient->sendHttpRequest(new HttpRequest('not.match.com/1'));
		}, InvalidArgumentException::class,
			'Invalid HTTP request. Url not matched. Expected "not.match.com" got "not.match.com/1".');
	}

	public function testMethodNotMatch()
	{
		$mockClient = new MockHttpClient();
		$mockClient->add(new HttpRequest('not.match.com'), new HttpResponse(200, [], ''));

		Assert::exception(static function () use ($mockClient) {
			$mockClient->sendHttpRequest(new HttpRequest('not.match.com', 'POST'));
		}, InvalidArgumentException::class,
			'Invalid HTTP request. Method not matched. Expected "GET" got "POST".');
	}

	public function testOptionsNotMatch()
	{
		$mockClient = new MockHttpClient();
		$mockClient->add(new HttpRequest('not.match.com'), new HttpResponse(200, [], ''));

		Assert::exception(static function () use ($mockClient) {
			$mockClient->sendHttpRequest(new HttpRequest('not.match.com', 'GET', [
				'headers' => [],
			]));
		}, InvalidArgumentException::class,
			'Invalid HTTP request. Options not matched.');
	}

}

(new MockHttpClientTest())->run();
