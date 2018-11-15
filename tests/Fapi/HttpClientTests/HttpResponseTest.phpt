<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\HttpResponse;
use Fapi\HttpClient\HttpStatusCode;
use Fapi\HttpClient\InvalidArgumentException;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

final class HttpResponseTest extends TestCase
{

	public function testDefault()
	{
		$response = new HttpResponse(HttpStatusCode::S200_OK, [], 'test');

		Assert::equal(HttpStatusCode::S200_OK, $response->getStatusCode());
		Assert::equal('test', $response->getBody());
		Assert::equal([], $response->getHeaders());

		Assert::exception(static function () {
			new HttpResponse(-1, [], '');
		}, InvalidArgumentException::class);
	}

	public function testHeaders()
	{
		new HttpResponse(HttpStatusCode::S200_OK, [
			'test' => [
				'tests' => 'more tests',
			],
		], 'test');

		Assert::exception(static function () {
			new HttpResponse(HttpStatusCode::S200_OK, [
				'test' => [
					'tests' => 5,
				],
			], 'test');
		}, InvalidArgumentException::class);

		Assert::exception(static function () {
			new HttpResponse(HttpStatusCode::S200_OK, [
				'test' => 't',
			], 'test');
		}, InvalidArgumentException::class);
	}

}

(new HttpResponseTest())->run();
