<?php declare(strict_types = 1);

namespace Fapi\HttpClient\Bridges\Tracy;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Throwable;
use Tracy\ILogger;

final class TracyToPsrLogger extends AbstractLogger
{

	public const PRIORITY_MAP = [
		LogLevel::EMERGENCY => ILogger::CRITICAL,
		LogLevel::ALERT => ILogger::CRITICAL,
		LogLevel::CRITICAL => ILogger::CRITICAL,
		LogLevel::ERROR => ILogger::ERROR,
		LogLevel::WARNING => ILogger::WARNING,
		LogLevel::NOTICE => ILogger::WARNING,
		LogLevel::INFO => ILogger::INFO,
		LogLevel::DEBUG => ILogger::DEBUG,
	];

	/** @var ILogger */
	private $tracyLogger;

	public function __construct(ILogger $tracyLogger)
	{
		$this->tracyLogger = $tracyLogger;
	}

	/**
	 * @inheritdoc
	 */
	public function log($level, $message, array $context = [])
	{
		$priority = self::PRIORITY_MAP[$level] ?? ILogger::ERROR;

		if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
			$this->tracyLogger->log($context['exception'], $priority);
			unset($context['exception']);
		}

		if ($context !== []) {
			$message = [
				'message' => $message,
				'context' => $context,
			];
		}

		$this->tracyLogger->log($message, $priority);
	}

}
