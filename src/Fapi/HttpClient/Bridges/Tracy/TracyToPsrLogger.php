<?php declare(strict_types = 1);

namespace Fapi\HttpClient\Bridges\Tracy;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
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

	public function __construct(private ILogger $tracyLogger)
	{
	}

	/**
	 * @param array<mixed> $context
	 *
	 * @inheritdoc
	 */
	public function log($level, string|Stringable $message, array $context = []): void
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
