<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use function microtime;

class LoggingHttpClient implements IHttpClient
{

	/** @var IHttpClient */
	private $httpClient;

	/** @var LoggerInterface */
	private $logger;

	/** @var ILoggingFormatter */
	private $formatter;

	public function __construct(IHttpClient $httpClient, LoggerInterface $logger, ?ILoggingFormatter $formatter = null)
	{
		$this->httpClient = $httpClient;
		$this->logger = $logger;

		if ($formatter === null) {
			$formatter = new BaseLoggingFormatter();
		}

		$this->formatter = $formatter;
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$startedAt = microtime(true);

		try {
			$response = $this->httpClient->sendRequest($request);
		} catch (HttpClientException $e) {
			$this->logFailedRequest($request, $e, microtime(true) - $startedAt);

			throw $e;
		}

		$this->logSuccessfulRequest($request, $response, microtime(true) - $startedAt);

		return $response;
	}

	private function logSuccessfulRequest(
		RequestInterface $request,
		ResponseInterface $response,
		float $elapsedTime
	): void
	{
		$this->log(
			$this->formatter->formatSuccessful($request, $response, $elapsedTime),
			LogLevel::INFO
		);
	}

	private function logFailedRequest(
		RequestInterface $request,
		HttpClientException $exception,
		float $elapsedTime
	): void
	{
		$this->log(
			$this->formatter->formatFailed($request, $exception, $elapsedTime),
			LogLevel::WARNING
		);
	}

	private function log(string $message, string $priority): void
	{
		$this->logger->log($priority, $message);
	}

}
