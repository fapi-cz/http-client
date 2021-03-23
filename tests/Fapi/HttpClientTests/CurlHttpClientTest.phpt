<?php declare(strict_types = 1);

/**
 * Test: Fapi\HttpClient\CapturingHttpClient
 *
 * @testCase Fapi\HttpClientTests\CapturingHttpClientTest
 */

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\CurlHttpClient;
use Fapi\HttpClient\IHttpClient;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/BaseHttpClient.php';
require __DIR__ . '/MockHttpServer/MockHttpServerRunner.php';

class CurlHttpClientTest extends BaseHttpClient
{

	protected function createHttpClient(): IHttpClient
	{
		return new CurlHttpClient();
	}

}

(new CurlHttpClientTest())->run();
