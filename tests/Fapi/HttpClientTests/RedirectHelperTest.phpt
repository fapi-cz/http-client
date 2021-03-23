<?php declare(strict_types = 1);

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

	public function testFollowRedirects(): void
	{
		$client = $this->getMockHttpClient();

		$request = new HttpRequest('GET', 'http://example.com/a');
		$response = $client->sendRequest($request);
		$response = RedirectHelper::followRedirects($client, $response, $request);

		Assert::true($client->wereAllHttpRequestsSent());
		Assert::same(HttpStatusCode::S200_OK, $response->getStatusCode());
		Assert::same(['Content-Type' => ['text/plain']], $response->getHeaders());
		Assert::same('OK', (string) $response->getBody());
	}

	public function testFollowTooManyRedirects(): void
	{
		$client = $this->getMockHttpClient();

		$request = new HttpRequest('GET', 'http://example.com/a');
		$response = $client->sendRequest($request);

		Assert::exception(static function () use ($client, $response, $request): void {
			RedirectHelper::followRedirects($client, $response, $request, 1);
		}, TooManyRedirectsException::class, 'Maximum number of redirections exceeded.');
	}

	public function testFollowRedirectToInvalidUrl(): void
	{
		$client = $this->getMockHttpClientWithInvalidRedirectUrl();

		$request = new HttpRequest('GET', 'http://example.com/a');
		$response = $client->sendRequest($request);

		$response = RedirectHelper::followRedirects($client, $response, $request);

		Assert::true($client->wereAllHttpRequestsSent());
		Assert::same(HttpStatusCode::S301_MOVED_PERMANENTLY, $response->getStatusCode());
		Assert::same(['Location' => ['invalid']], $response->getHeaders());
		Assert::same('', (string) $response->getBody());
	}

	public function testFollowRedirectToEmptyUrl(): void
	{
		$client = $this->getMockHttpClientWithEmptyInvalidRedirectUrl();

		$request = new HttpRequest('GET', 'http://example.com/a');
		$response = $client->sendRequest($request);

		$response = RedirectHelper::followRedirects($client, $response, $request);

		Assert::true($client->wereAllHttpRequestsSent());
		Assert::same(HttpStatusCode::S301_MOVED_PERMANENTLY, $response->getStatusCode());
		Assert::same([], $response->getHeaders());
		Assert::same('', (string) $response->getBody());
	}

	private function getMockHttpClient(): MockHttpClient
	{
		$client = new MockHttpClient();

		$client->add(
			new HttpRequest('GET', 'http://example.com/a'),
			new HttpResponse(
				HttpStatusCode::S301_MOVED_PERMANENTLY,
				['Location' => ['http://example.com/b']],
				'',
			),
		);

		$client->add(
			new HttpRequest('GET', 'http://example.com/b'),
			new HttpResponse(
				HttpStatusCode::S302_FOUND,
				['Location' => ['https://example.com/c']],
				'',
			),
		);

		$client->add(
			new HttpRequest('GET', 'https://example.com/c'),
			new HttpResponse(
				HttpStatusCode::S200_OK,
				['Content-Type' => ['text/plain']],
				'OK',
			),
		);

		return $client;
	}

	private function getMockHttpClientWithInvalidRedirectUrl(): MockHttpClient
	{
		$client = new MockHttpClient();

		$client->add(
			new HttpRequest('GET', 'http://example.com/a'),
			new HttpResponse(
				HttpStatusCode::S301_MOVED_PERMANENTLY,
				['Location' => ['http://example.com/a2']],
				'',
			),
		);

		$client->add(
			new HttpRequest('GET', 'http://example.com/a2'),
			new HttpResponse(
				HttpStatusCode::S301_MOVED_PERMANENTLY,
				['Location' => ['invalid']],
				'',
			),
		);

		return $client;
	}

	private function getMockHttpClientWithEmptyInvalidRedirectUrl(): MockHttpClient
	{
		$client = new MockHttpClient();

		$client->add(
			new HttpRequest('GET', 'http://example.com/a'),
			new HttpResponse(
				HttpStatusCode::S301_MOVED_PERMANENTLY,
				[],
				'',
			),
		);

		return $client;
	}

}

(new RedirectHelperTest())->run();
