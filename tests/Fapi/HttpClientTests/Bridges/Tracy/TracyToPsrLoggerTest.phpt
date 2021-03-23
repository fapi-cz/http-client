<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests\Bridges\Tracy;

use Exception;
use Fapi\HttpClient\Bridges\Tracy\TracyToPsrLogger;
use Nette\Utils\FileSystem;
use Psr\Log\LogLevel;
use Tester\Assert;
use Tester\TestCase;
use Tracy\Debugger;
use function file_exists;
use function file_get_contents;
use function glob;
use function unlink;

require_once __DIR__ . '/../../../../bootstrap.php';

final class TracyToPsrLoggerTest extends TestCase
{

	public function testLog(): void
	{
		Debugger::enable(false, __DIR__);
		$logger = new TracyToPsrLogger(Debugger::getLogger());

		$logger->log(LogLevel::INFO, 'test');
		$logger->log(LogLevel::INFO, new Exception('test'));
		$logger->log(LogLevel::INFO, 'test with context', [
			'user_id' => 5,
		]);
		$logger->log(LogLevel::INFO, 'test with context', [
			'exception' => new Exception('test'),
		]);

		Assert::true(file_exists(__DIR__ . '/info.log'));
		$logData = @file_get_contents(__DIR__ . '/info.log');

		Assert::type('string', $logData);
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
		foreach (glob(__DIR__ . '/exception*') as $f) {
			@unlink($f);
		}

		FileSystem::delete(__DIR__ . '/info.log');
	}

}

(new TracyToPsrLoggerTest())->run();
