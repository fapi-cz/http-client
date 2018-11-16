<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests\Bridges\Tracy;

use Fapi\HttpClient\Bridges\Tracy\TracyToPsrLogger;
use Nette\Utils\FileSystem;
use Psr\Log\LogLevel;
use Tester\Assert;
use Tracy\Debugger;

require_once __DIR__ . '/../../../../bootstrap.php';

final class TracyToPsrLoggerTest extends \Tester\TestCase
{

	public function testLog()
	{
		Debugger::enable(false, __DIR__);
		$logger = new TracyToPsrLogger(Debugger::getLogger());

		$logger->log(LogLevel::INFO, 'test');
		$logger->log(LogLevel::INFO, new \Exception('test'));
		$logger->log(LogLevel::INFO, 'test with context', [
			'user_id' => 5,
		]);

		Assert::true(\file_exists(__DIR__ . '/info.log'));
		$logData = @\file_get_contents(__DIR__ . '/info.log');

		Assert::type('string', $logData);
	}

	public function setUp()
	{
		$this->cleanUp();
	}

	public function tearDown()
	{
		$this->cleanUp();
	}

	private function cleanUp()
	{
		foreach (glob(__DIR__ . '/exception*') as $f) {
			@\unlink($f);
		}
		FileSystem::delete(__DIR__ . '/info.log');
	}

}

(new TracyToPsrLoggerTest())->run();
