<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests;

use Fapi\HttpClient\Bridges\Tracy\TracyToPsrLogger;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Fapi\HttpClient\LoggingHttpClient;
use Fapi\HttpClient\MockHttpClient;
use Nette\Utils\FileSystem;
use Tester\Assert;
use Tester\TestCase;
use Tracy\Debugger;
use function is_file;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/MockHttpClients/SampleMockHttpClient.php';

final class LoggingHttpClientTest extends TestCase
{

	public function testLog(): void
	{
		$mockClient = $this->getMockHttpClient();

		Debugger::enable(false, __DIR__);
		$loggingClient = new LoggingHttpClient(
			$mockClient,
			new TracyToPsrLogger(Debugger::getLogger())
		);

		$loggingClient->sendRequest(new HttpRequest(
			'GET',
			'http://localhost/1',
			['Host' => ['localhost'], 'User-Agent' => ['Nette Tester']]
		));

		Assert::true(is_file(__DIR__ . '/info.log'));
	}

	private function getMockHttpClient(): MockHttpClient
	{
		$mockClient = new MockHttpClient();
		$mockClient->add(
			new HttpRequest(
				'GET',
				'http://localhost/1',
				['Host' => ['localhost'], 'User-Agent' => ['Nette Tester']]
			),
			new HttpResponse(
				200,
				['Content-Type' => ['text/plain']],
				"It works!\n"
			)
		);

		return $mockClient;
	}

	public function setUp(): void
	{
		$this->cleanUp();
	}

	public function tearDown(): void
	{
		$this->cleanUp();
	}

	private function cleanUp(): void
	{
		FileSystem::delete(__DIR__ . '/info.log');
	}

}

(new LoggingHttpClientTest())->run();
