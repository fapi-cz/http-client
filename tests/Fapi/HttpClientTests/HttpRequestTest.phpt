<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\HttpMethod;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\InvalidArgumentException;
use GuzzleHttp\Cookie\CookieJar;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

final class HttpRequestTest extends \Tester\TestCase
{

	public function testDefault()
	{
		$request = new HttpRequest('test.cz');

		Assert::equal('test.cz', $request->getUrl());
		Assert::equal(HttpMethod::GET, $request->getMethod());
		Assert::equal([], $request->getOptions());

		Assert::exception(static function () {
			new HttpRequest('test.cz', 'asdf');
		}, InvalidArgumentException::class);
	}

	/**
	 * @dataProvider getValidHeadersData
	 * @param mixed[] $headers
	 */
	public function testValidHeaders(array $headers = [])
	{
		new HttpRequest('test.cz', HttpMethod::GET, $headers);

		Assert::true(true);
	}

	/**
	 * @dataProvider getInvalidHeadersData
	 * @param mixed[] $headers
	 */
	public function testInvalidHeaders(array $headers = [])
	{
		Assert::exception(static function () use ($headers) {
			new HttpRequest('test.cz', HttpMethod::GET, $headers);
		}, InvalidArgumentException::class);
	}

	/**
	 * @return mixed[]
	 */
	public function getValidHeadersData(): array
	{
		return [
			[
				'headers' => [
					'form_params' => [
						'test',
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
		];
	}

	/**
	 * @return mixed[]
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
					'cookies' => '',
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
		];
	}

}

(new HttpRequestTest())->run();
