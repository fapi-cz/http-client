<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Fapi\HttpClient\Utils\Json;
use Fapi\HttpClient\Utils\JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggingHttpClient implements IHttpClient
{

	/** @var IHttpClient */
	private $httpClient;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IHttpClient $httpClient, LoggerInterface $logger)
	{
		$this->httpClient = $httpClient;
		$this->logger = $logger;
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$startedAt = \microtime(true);

		try {
			$response = $this->httpClient->sendRequest($request);
		} catch (HttpClientException $e) {
			$this->logFailedRequest($request, $e, \microtime(true) - $startedAt);

			throw $e;
		}

		$this->logSuccessfulRequest($request, $response, \microtime(true) - $startedAt);

		return $response;
	}

	private function logSuccessfulRequest(RequestInterface $httpRequest, ResponseInterface $httpResponse, float $elapsedTime)
	{
		$this->log('an HTTP request has been sent.'
			. $this->dumpHttpRequest($httpRequest)
			. $this->dumpHttpResponse($httpResponse)
			. $this->dumpElapsedTime($elapsedTime), LogLevel::INFO);
	}

	private function logFailedRequest(RequestInterface $httpRequest, HttpClientException $exception, float $elapsedTime)
	{
		$this->log('an HTTP request failed.'
			. $this->dumpHttpRequest($httpRequest)
			. $this->dumpException($exception)
			. $this->dumpElapsedTime($elapsedTime), LogLevel::WARNING);
	}

	private function dumpHttpRequest(RequestInterface $httpRequest): string
	{
		return ' Request URL: ' . $this->dumpValue((string) $httpRequest->getUri())
			. ' Request method: ' . $this->dumpValue($httpRequest->getMethod())
			. ' Request options: ' . $this->dumpValue($httpRequest->getHeaders());
	}

	private function dumpHttpResponse(ResponseInterface $httpResponse): string
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
		$this->logger->log($priority, 'Fapi\HttpClient: ' . $message);
	}

}
