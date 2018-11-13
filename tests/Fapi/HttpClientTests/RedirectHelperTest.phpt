<?php
declare(strict_types = 1);

/**
 * Test: Fapi\HttpClient\RedirectHelper
 *
 * @testCase Fapi\HttpClientTests\RedirectHelper
 */

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Fapi\HttpClient\HttpStatusCode;
use Fapi\HttpClient\MockHttpClient;
use Fapi\HttpClient\RedirectHelper;
use Fapi\HttpClient\TooManyRedirectsException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class RedirectHelperTest extends TestCase
{

	public function testFollowRedirects()
	{
		$client = $this->getMockHttpClient();

		$response = $client->sendHttpRequest(new HttpRequest('http://example.com/a'));
		$response = RedirectHelper::followRedirects($client, $response);

		Assert::true($client->wereAllHttpRequestsSent());
		Assert::same($response->getStatusCode(), HttpStatusCode::S200_OK);
		Assert::same($response->getHeaders(), ['Content-Type' => ['text/plain']]);
		Assert::same($response->getBody(), 'OK');
	}

	public function testFollowTooManyRedirects()
	{
		$client = $this->getMockHttpClient();

		$response = $client->sendHttpRequest(new HttpRequest('http://example.com/a'));

		Assert::exception(static function () use ($client, $response) {
			RedirectHelper::followRedirects($client, $response, 1);
		}, TooManyRedirectsException::class, 'Maximum number of redirections exceeded.');
	}

	public function testFollowRedirectToInvalidUrl()
	{
		$client = $this->getMockHttpClientWithInvalidRedirectUrl();

		$response = $client->sendHttpRequest(new HttpRequest('http://example.com/a'));

		Assert::true($client->wereAllHttpRequestsSent());
		Assert::same($response->getStatusCode(), HttpStatusCode::S301_MOVED_PERMANENTLY);
		Assert::same($response->getHeaders(), ['Location' => ['invalid']]);
		Assert::same($response->getBody(), '');
	}

	private function getMockHttpClient(): MockHttpClient
	{
		$client = new MockHttpClient();

		$client->add(
			new HttpRequest('http://example.com/a'),
			new HttpResponse(
				HttpStatusCode::S301_MOVED_PERMANENTLY,
				['Location' => ['http://example.com/b']],
				''
			)
		);

		$client->add(
			new HttpRequest('http://example.com/b'),
			new HttpResponse(
				HttpStatusCode::S302_FOUND,
				['Location' => ['https://example.com/c']],
				''
			)
		);

		$client->add(
			new HttpRequest('https://example.com/c'),
			new HttpResponse(
				HttpStatusCode::S200_OK,
				['Content-Type' => ['text/plain']],
				'OK'
			)
		);

		return $client;
	}

	private function getMockHttpClientWithInvalidRedirectUrl(): MockHttpClient
	{
		$client = new MockHttpClient();

		$client->add(
			new HttpRequest('http://example.com/a'),
			new HttpResponse(
				HttpStatusCode::S301_MOVED_PERMANENTLY,
				['Location' => ['invalid']],
				''
			)
		);

		return $client;
	}

}

(new RedirectHelperTest())->run();
