<?php
declare(strict_types = 1);

/**
 * Test: Fapi\HttpClient\CapturingHttpClient
 *
 * @testCase Fapi\HttpClientTests\CapturingHttpClientTest
 */

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\CurlHttpClient;
use Fapi\HttpClient\HttpMethod;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\IHttpClient;
use Fapi\HttpClient\NotSupportedException;
use GuzzleHttp\Cookie\CookieJar;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/BaseHttpClient.php';
require __DIR__ . '/MockHttpServer/MockHttpServerRunner.php';

class CurlHttpClientTest extends BaseHttpClient
{

	protected function createHttpClient(): IHttpClient
	{
		return new CurlHttpClient();
	}

	public function testThrowingOfNotSupportedExceptionWhenSendingHttpRequestWithCookies()
	{
		$httpClient = $this->httpClient;
		$cookieJar = new CookieJar();
		$httpRequest = new HttpRequest('http://127.0.0.1:1337/', HttpMethod::GET, [
			'cookies' => $cookieJar,
		]);

		Assert::exception(static function () use ($httpClient, $httpRequest) {
			$httpClient->sendHttpRequest($httpRequest);
		}, NotSupportedException::class, 'CurlHttpClient does not support option cookies.');
	}

}

(new CurlHttpClientTest())->run();
