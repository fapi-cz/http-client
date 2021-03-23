<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests;

use Exception;
use Fapi\HttpClient\BaseLoggingFormatter;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

final class BaseLoggingFormatterTest extends TestCase
{

	public function testFormatFailed(): void
	{
		$formatter = new BaseLoggingFormatter();

		$formatted = $formatter->formatFailed(
			new HttpRequest('GET', 'test.cz'),
			new Exception('test'),
			0.10013794898987,
		);

		Assert::equal(
			'Fapi\HttpClient: an HTTP request failed. Request URL: "test.cz" Request method: "GET" Request headers: [] Request body: "" Exception type: "Exception" Exception message: "test" Elapsed time: 100.14 ms',
			$formatted,
		);
	}

	public function testFormatSuccessful(): void
	{
		$formatter = new BaseLoggingFormatter();

		$formatted = $formatter->formatSuccessful(
			new HttpRequest('GET', 'test.cz'),
			new HttpResponse(200, [], '{"test": "test"}'),
			0.10113794898987,
		);

		Assert::equal(
			'Fapi\HttpClient: an HTTP request has been sent. Request URL: "test.cz" Request method: "GET" Request headers: [] Request body: "" Response status code: 200 Response headers: [] Response body: "{\"test\": \"test\"}" Elapsed time: 101.14 ms',
			$formatted,
		);
	}

}

(new BaseLoggingFormatterTest())->run();
