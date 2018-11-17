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
use Nette\Utils\FileSystem;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class CapturingHttpClientTest extends TestCase
{

	/** @var string */
	private $file;

	public function setUp()
	{
		$this->file = __DIR__ . '/MockHttpClients/SampleMockHttpClient2.php';

	}

	public function testWriteToMockPhpFile()
	{
		$mockHttpRequest = HttpRequest::from(
			'http://localhost/1',
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

		$fileName = FileMock::create('', '.php');
		$capturingHttpClient = new CapturingHttpClient($mockHttpClient, $fileName, 'Fapi\\HttpClientTests\\MockHttpClients\\SampleMockHttpClient');
		$capturingHttpClient->sendRequest($mockHttpRequest);

		$capturingHttpClient->close();

		$expected = \file_get_contents(__DIR__ . '/MockHttpClients/SampleMockHttpClient.php');
		$actual = \file_get_contents($fileName);
		Assert::equal($expected, $actual);
	}

	public function testWriteToPhpFile()
	{
		$mockHttpRequest = HttpRequest::from(
			'http://localhost/2',
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

		$capturingHttpClient = new CapturingHttpClient($mockHttpClient, $this->file, 'Fapi\\HttpClientTests\\MockHttpClients\\SampleMockHttpClient2');
		$capturingHttpClient->sendRequest($mockHttpRequest);

		$capturingHttpClient->close();

		$reflectionClass = new \ReflectionClass(\get_class($capturingHttpClient));
		$reflectionProperty = $reflectionClass->getProperty('httpClient');
		$reflectionProperty->setAccessible(true);
		$httpClient = $reflectionProperty->getValue($capturingHttpClient);

		Assert::type($mockHttpClient, $httpClient);

		$capturingHttpClient = new CapturingHttpClient($mockHttpClient, $this->file, 'Fapi\\HttpClientTests\\MockHttpClients\\SampleMockHttpClient2');
		$capturingHttpClient->sendRequest($mockHttpRequest);

		$capturingHttpClient->close();

		$reflectionClass = new \ReflectionClass(\get_class($capturingHttpClient));
		$reflectionProperty = $reflectionClass->getProperty('httpClient');
		$reflectionProperty->setAccessible(true);
		$httpClient = $reflectionProperty->getValue($capturingHttpClient);

		Assert::type('Fapi\\HttpClientTests\\MockHttpClients\\SampleMockHttpClient2', $httpClient);

	}

	public function tearDown()
	{
		FileSystem::delete($this->file);
	}

}

(new CapturingHttpClientTest())->run();
