<?php
declare(strict_types = 1);

/**
 * Test: Fapi\HttpClient\CapturingHttpClient
 *
 * @testCase Fapi\HttpClientTests\CapturingHttpClientTest
 */

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\CapturingHttpClient;
use Fapi\HttpClient\HttpMethod;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Fapi\HttpClient\HttpStatusCode;
use Fapi\HttpClient\MockHttpClient;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class CapturingHttpClientTest extends TestCase
{

	public function testWriteToPhpFile()
	{
		$mockHttpRequest = new HttpRequest(
			'http://localhost/',
			HttpMethod::GET,
			[
				'headers' => [
					'User-Agent' => 'Nette Tester',
				],
			]
		);

		$mockHttpResponse = new HttpResponse(
			HttpStatusCode::S200_OK,
			[
				'Content-Type' => [
					'text/plain',
				],
			],
			"It works!\n"
		);

		$mockHttpClient = new MockHttpClient();
		$mockHttpClient->add($mockHttpRequest, $mockHttpResponse);

		$capturingHttpClient = new CapturingHttpClient($mockHttpClient);
		$capturingHttpClient->sendHttpRequest($mockHttpRequest);

		$fileName = FileMock::create('', '.php');
		$capturingHttpClient->writeToPhpFile($fileName, 'Fapi\\HttpClientTests\\MockHttpClients\\SampleMockHttpClient');

		$expected = \file_get_contents(__DIR__ . '/MockHttpClients/SampleMockHttpClient.php');
		$actual = \file_get_contents($fileName);
		Assert::equal($expected, $actual);
	}

}

(new CapturingHttpClientTest())->run();
