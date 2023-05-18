<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\HttpMethod;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\InvalidArgumentException;
use GuzzleHttp\Cookie\CookieJar;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

final class HttpRequestTest extends TestCase
{

	public function testDefault(): void
	{
		$request = HttpRequest::from('test.cz');

		Assert::equal('test.cz', (string) $request->getUri());
		Assert::equal(HttpMethod::GET, $request->getMethod());
		Assert::equal(['verify' => ['1']], $request->getHeaders());

		Assert::exception(static function (): void {
			HttpRequest::from('test.cz', 'asdf');
		}, InvalidArgumentException::class);
	}

	/**
	 * @dataProvider getValidHeadersData
	 * @param array<mixed> $headers
	 */
	public function testValidHeaders(array $headers = []): void
	{
		HttpRequest::from('test.cz', HttpMethod::GET, $headers);

		Assert::true(true);
	}

	/**
	 * @dataProvider getInvalidHeadersData
	 * @param array<mixed> $headers
	 */
	public function testInvalidHeaders(array $headers = []): void
	{
		Assert::exception(static function () use ($headers): void {
			HttpRequest::from('test.cz', HttpMethod::GET, $headers);
		}, InvalidArgumentException::class);
	}

	/**
	 * @return array<mixed>
	 */
	public function getValidHeadersData(): array
	{
		return [
			[
				'headers' => [
					'form_params' => [
						0 => 'test',
						'test' => 'test',
					],
				],
			],
			[
				'headers' => [
					'headers' => [
						[
							'test',
						],
						'test',
					],
				],
			],
			[
				'headers' => [
					'auth' => [
						'username',
						'password',
					],
				],
			],
			[
				'headers' => [
					'body' => 'body text',
				],
			],
			[
				'headers' => [
					'json' => '{"json"}',
				],
			],
			[
				'headers' => [
					'cookies' => new CookieJar(),
				],
			],
			[
				'headers' => [
					'timeout' => 5,
				],
			],
			[
				'headers' => [
					'connect_timeout' => 5,
				],
			],
			[
				'headers' => [
					'cert' => __DIR__ . '/certs/private-key.key',
				],
			],
			[
				'headers' => [
					'ssl_key' => __DIR__ . '/certs/public-key.pem',
				],
			],
		];
	}

	/**
	 * @return array<mixed>
	 */
	public function getInvalidHeadersData(): array
	{
		return [
			[
				'headers' => [
					'form_params' => 'test',
				],
			],
			[
				'headers' => [
					'form_params' => ['t' => 5],
				],
			],
			[
				'headers' => [
					'headers' => 'test',
				],
			],
			[
				'headers' => [
					'auth' => [],
				],
			],
			[
				'headers' => [
					'auth' => '[]',
				],
			],
			[
				'headers' => [
					'auth' => [
						5,
					],
				],
			],
			[
				'headers' => [
					'auth' => [
						'5',
						5,
					],
				],
			],
			[
				'headers' => [
					'body' => ['body text'],
				],
			],
			[
				'headers' => [
					'json' => ["bad utf\xFF"],
				],
			],
			[
				'headers' => [
					'timeout' => 5.0,
				],
			],
			[
				'headers' => [
					'connect_timeout' => 5.0,
				],
			],
			[
				'headers' => [
					'cert' => 5,
				],
			],
			[
				'headers' => [
					'ssl_key' => 5,
				],
			],
			[
				'headers' => [
					'cert' => [1],
				],
			],
			[
				'headers' => [
					'ssl_key' => [1],
				],
			],
			[
				'headers' => [
					'cert' => [1, 1],
				],
			],
			[
				'headers' => [
					'ssl_key' => [1, 1],
				],
			],
		];
	}

}

(new HttpRequestTest())->run();
