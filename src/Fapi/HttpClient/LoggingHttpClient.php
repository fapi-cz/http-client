<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Fapi\HttpClient\Utils\Json;
use Fapi\HttpClient\Utils\JsonException;
use Tracy\ILogger;

class LoggingHttpClient implements IHttpClient
{

	/** @var IHttpClient */
	private $httpClient;

	/** @var ILogger */
	private $logger;

	public function __construct(IHttpClient $httpClient, ILogger $logger)
	{
		$this->httpClient = $httpClient;
		$this->logger = $logger;
	}

	public function sendHttpRequest(HttpRequest $httpRequest): HttpResponse
	{
		$startedAt = \microtime(true);

		try {
			$httpResponse = $this->httpClient->sendHttpRequest($httpRequest);
		} catch (HttpClientException $e) {
			$this->logFailedRequest($httpRequest, $e, \microtime(true) - $startedAt);

			throw $e;
		}

		$this->logSuccessfulRequest($httpRequest, $httpResponse, \microtime(true) - $startedAt);

		return $httpResponse;
	}

	private function logSuccessfulRequest(HttpRequest $httpRequest, HttpResponse $httpResponse, float $elapsedTime)
	{
		$this->log('an HTTP request has been sent.'
			. $this->dumpHttpRequest($httpRequest)
			. $this->dumpHttpResponse($httpResponse)
			. $this->dumpElapsedTime($elapsedTime), ILogger::INFO);
	}

	private function logFailedRequest(HttpRequest $httpRequest, HttpClientException $exception, float $elapsedTime)
	{
		$this->log('an HTTP request failed.'
			. $this->dumpHttpRequest($httpRequest)
			. $this->dumpException($exception)
			. $this->dumpElapsedTime($elapsedTime), ILogger::WARNING);
	}

	private function dumpHttpRequest(HttpRequest $httpRequest): string
	{
		return ' Request URL: ' . $this->dumpValue($httpRequest->getUrl())
			. ' Request method: ' . $this->dumpValue($httpRequest->getMethod())
			. ' Request options: ' . $this->dumpValue($httpRequest->getOptions());
	}

	private function dumpHttpResponse(HttpResponse $httpResponse): string
	{
		return ' Response status code: ' . $this->dumpValue($httpResponse->getStatusCode())
			. ' Response headers: ' . $this->dumpValue($httpResponse->getHeaders())
			. ' Response body: ' . $this->dumpValue($httpResponse->getBody());
	}

	private function dumpException(\Throwable $exception): string
	{
		$dump = ' Exception type: ' . $this->dumpValue(\get_class($exception))
			. ' Exception message: ' . $this->dumpValue($exception->getMessage());

		if ($exception->getPrevious() !== null) {
			$previousException = $exception->getPrevious();

			$dump .= ' Previous exception type: ' . $this->dumpValue(\get_class($previousException))
				. ' Previous exception message: ' . $this->dumpValue($previousException->getMessage());
		}

		return $dump;
	}

	private function dumpElapsedTime(float $elapsedTime): string
	{
		return ' Elapsed time: ' . $this->dumpValue($elapsedTime);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private function dumpValue($value): string
	{
		try {
			return Json::encode($value, \JSON_UNESCAPED_UNICODE);
		} catch (JsonException $e) {
			return '(serialized) ' . \base64_encode(\serialize($value));
		}
	}

	private function log(string $message, string $priority)
	{
		$this->logger->log('Fapi\HttpClient: ' . $message, $priority);
	}

}
