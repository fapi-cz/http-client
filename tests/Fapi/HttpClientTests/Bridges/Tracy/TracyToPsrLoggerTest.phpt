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

		$logData = @\file_get_contents(__DIR__ . '/info.log');

		Assert::type('string', $logData);
	}

	public function tearDown()
	{
		FileSystem::delete(__DIR__ . '/info.log');
	}

}

(new TracyToPsrLoggerTest())->run();
