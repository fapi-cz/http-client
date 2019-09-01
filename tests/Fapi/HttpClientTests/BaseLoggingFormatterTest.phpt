<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\BaseLoggingFormatter;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

final class BaseLoggingFormatterTest extends \Tester\TestCase
{

	public function testFormatFailed()
	{
		$formatter = new BaseLoggingFormatter();

		$formatted = $formatter->formatFailed(
			HttpRequest::from('test.cz'),
			new \Exception('test'),
			0.10013794898987
		);

		Assert::equal('Fapi\HttpClient: an HTTP request failed. Request URL: "test.cz" Request method: "GET" Request headers: [] Request body: "" Exception type: "Exception" Exception message: "test" Elapsed time: 100.14 ms', $formatted);
	}

	public function testFormatSuccessful()
	{
		$formatter = new BaseLoggingFormatter();

		$formatted = $formatter->formatSuccessful(
			HttpRequest::from('test.cz'),
			new HttpResponse(200, [], '{"test": "test"}'),
			0.10113794898987
		);

		Assert::equal('Fapi\HttpClient: an HTTP request has been sent. Request URL: "test.cz" Request method: "GET" Request headers: [] Request body: "" Response status code: 200 Response headers: [] Response body: "{\"test\": \"test\"}" Elapsed time: 101.14 ms', $formatted);
	}

}

(new BaseLoggingFormatterTest())->run();
