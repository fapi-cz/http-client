<?php
declare(strict_types = 1);

/**
 * Test: Fapi\HttpClient\CapturingHttpClient
 *
 * @testCase Fapi\HttpClientTests\CapturingHttpClientTest
 */

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\GuzzleHttpClient;
use Fapi\HttpClient\HttpMethod;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpStatusCode;
use Fapi\HttpClient\IHttpClient;
use Fapi\HttpClientTests\MockHttpServer\MockHttpServerRunner;
use GuzzleHttp\Cookie\CookieJar;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/BaseHttpClient.php';
require __DIR__ . '/MockHttpServer/MockHttpServerRunner.php';

class GuzzleHttpClientTest extends BaseHttpClient
{

	protected function createHttpClient(): IHttpClient
	{
		return new GuzzleHttpClient();
	}

	public function testSendHttpRequestWithCookies()
	{
		$runner = new MockHttpServerRunner();
		$httpClient = $this->httpClient;

		$runner->onStarted[] = static function (MockHttpServerRunner $runner) use ($httpClient) {
			$cookieJar = new CookieJar();
			$httpRequest = new HttpRequest('http://127.0.0.1:1337/assign-cookie', HttpMethod::GET, [
				'cookies' => $cookieJar,
			]);
			$httpResponse = $httpClient->sendHttpRequest($httpRequest);
			$headers = $httpResponse->getHeaders();

			Assert::same(HttpStatusCode::S200_OK, $httpResponse->getStatusCode());
			Assert::same(['text/plain'], $headers['Content-Type']);
			Assert::same("OK\n", $httpResponse->getBody());

			$httpRequest = new HttpRequest('http://127.0.0.1:1337/check-cookie', HttpMethod::GET, [
				'cookies' => $cookieJar,
			]);
			$httpResponse = $httpClient->sendHttpRequest($httpRequest);
			$headers = $httpResponse->getHeaders();

			Assert::same(HttpStatusCode::S200_OK, $httpResponse->getStatusCode());
			Assert::same(['text/plain'], $headers['Content-Type']);
			Assert::same("OK\n", $httpResponse->getBody());

			$runner->stop();
		};

		$runner->run();
	}

}

(new GuzzleHttpClientTest())->run();
