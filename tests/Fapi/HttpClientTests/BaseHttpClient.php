<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\HttpMethod;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpStatusCode;
use Fapi\HttpClient\IHttpClient;
use Fapi\HttpClient\TimeLimitExceededException;
use Fapi\HttpClientTests\MockHttpServer\MockHttpServerRunner;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;
use const LOCKS_DIR;

abstract class BaseHttpClient extends TestCase
{

	/** @var IHttpClient */
	protected $httpClient;

	protected function setUp(): void
	{
		parent::setUp();
		Environment::lock('MockHttpServer', LOCKS_DIR);
		$this->httpClient = $this->createHttpClient();
	}

	abstract protected function createHttpClient(): IHttpClient;

	/**
	 * @dataProvider getSampleHttpRequests
	 * @param array<mixed> $options
	 */
	public function testSendHttpRequest(string $url, string $method, array $options, string $expectedBody): void
	{
		$runner = new MockHttpServerRunner();
		$httpRequest = HttpRequest::from($url, $method, $options);
		$httpClient = $this->httpClient;

		$runner->onStarted[] = static function (MockHttpServerRunner $runner) use ($httpClient, $httpRequest, $expectedBody): void {
			$httpResponse = $httpClient->sendRequest($httpRequest);
			$headers = $httpResponse->getHeaders();

			Assert::same($expectedBody, (string) $httpResponse->getBody());
			Assert::same(HttpStatusCode::S200_OK, $httpResponse->getStatusCode());
			Assert::same(['text/plain'], $headers['Content-Type']);

			$runner->stop();
		};

		$runner->run();
	}

	/**
	 * @return array<mixed>
	 */
	public function getSampleHttpRequests(): array
	{
		return [
			[
				'http://127.0.0.1:1337/login',
				HttpMethod::POST,
				[
					'headers' => [
						'X-Foo' => [
							'Bar',
							'Baz',
						],
					],
					'form_params' => [
						'username' => 'admin',
						'password' => 'xxx',
					],
				],
				"OK\n",
			],
			[
				'http://127.0.0.1:1337/api',
				HttpMethod::POST,
				[
					'headers' => [
						'Content-Type' => 'application/json',
						'User-Agent' => 'ApiClient/1.0',
					],
					'auth' => ['admin', 'xxx'],
					'body' => '{"foo":"bar"}',
				],
				"OK\n",
			],
			[
				'http://127.0.0.1:1337/api',
				HttpMethod::POST,
				[
					'headers' => [
						'User-Agent' => 'ApiClient/1.0',
					],
					'auth' => ['admin', 'xxx'],
					'json' => [
						'foo' => 'bar',
					],
				],
				"OK\n",
			],
			[
				'http://127.0.0.1:1337/empty',
				HttpMethod::GET,
				[],
				'',
			],
		];
	}

	public function testSendHttpRequestWithNotExceededTimeout(): void
	{
		$runner = new MockHttpServerRunner();
		$httpClient = $this->httpClient;

		$runner->onStarted[] = static function (MockHttpServerRunner $runner) use ($httpClient): void {
			$httpRequest = HttpRequest::from('http://127.0.0.1:1337/delayed', HttpMethod::GET, [
				'timeout' => 3,
			]);

			$httpResponse = $httpClient->sendRequest($httpRequest);
			$headers = $httpResponse->getHeaders();

			Assert::same("OK\n", (string) $httpResponse->getBody());
			Assert::same(HttpStatusCode::S200_OK, $httpResponse->getStatusCode());
			Assert::same(['text/plain'], $headers['Content-Type']);

			$runner->stop();
		};

		$runner->run();
	}

	public function testSendHttpRequestWithExceededTimeout(): void
	{
		$runner = new MockHttpServerRunner();
		$httpClient = $this->httpClient;

		$runner->onStarted[] = static function (MockHttpServerRunner $runner) use ($httpClient): void {
			$httpRequest = HttpRequest::from('http://127.0.0.1:1337/delayed', HttpMethod::GET, [
				'timeout' => 1,
			]);

			Assert::exception(static function () use ($httpClient, $httpRequest): void {
				$httpClient->sendRequest($httpRequest);
			}, TimeLimitExceededException::class);

			$runner->stop();
		};

		$runner->run();
	}

	public function testSendHttpRequestWithNotExceededConnectTimeout(): void
	{
		$runner = new MockHttpServerRunner();
		$httpClient = $this->httpClient;

		$runner->onStarted[] = static function (MockHttpServerRunner $runner) use ($httpClient): void {
			$httpRequest = HttpRequest::from('http://127.0.0.1:1337/delayed', HttpMethod::GET, [
				'connect_timeout' => 1,
			]);

			$httpResponse = $httpClient->sendRequest($httpRequest);
			$headers = $httpResponse->getHeaders();

			Assert::same(HttpStatusCode::S200_OK, $httpResponse->getStatusCode());
			Assert::same(['text/plain'], $headers['Content-Type']);
			Assert::same("OK\n", (string) $httpResponse->getBody());

			$runner->stop();
		};

		$runner->run();
	}

	public function testVerify(): void
	{
		$runner = new MockHttpServerRunner();
		$httpClient = $this->httpClient;

		$runner->onStarted[] = static function (MockHttpServerRunner $runner) use ($httpClient): void {
			$httpRequest = HttpRequest::from(
				'http://127.0.0.1:1337/api',
				HttpMethod::POST,
				[
					'headers' => [
						'Content-Type' => 'application/json',
						'User-Agent' => 'ApiClient/1.0',
					],
					'auth' => ['admin', 'xxx'],
					'body' => '{"foo":"bar"}',
					'verify' => false,
				]
			);

			$httpResponse = $httpClient->sendRequest($httpRequest);
			$headers = $httpResponse->getHeaders();

			Assert::same("OK\n", (string) $httpResponse->getBody());
			Assert::same(HttpStatusCode::S200_OK, $httpResponse->getStatusCode());
			Assert::same(['text/plain'], $headers['Content-Type']);

			$runner->stop();
		};

		$runner->run();
	}

	//  public function testSendHttpRequestWithExceededConnectTimeout()
	//  {
	//      $httpClient = $this->httpClient;
	//      $httpRequest = HttpRequest::from(HttpMethod::GET, 'http://127.0.0.1:1337/delayed', [
	//          'connect_timeout' => 1,
	//      ]);
	//
	//      Assert::exception(static function () use ($httpClient, $httpRequest) {
	//          $httpClient->sendRequest($httpRequest);
	//      }, TimeLimitExceededException::class);
	//  }

}
