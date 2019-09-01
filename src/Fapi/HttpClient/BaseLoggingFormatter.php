<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Fapi\HttpClient\Utils\Json;
use Fapi\HttpClient\Utils\JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class BaseLoggingFormatter implements ILoggingFormatter
{

	public function formatSuccessful(RequestInterface $request, ResponseInterface $response, float $elapsedTime): string
	{
		return 'Fapi\HttpClient: an HTTP request has been sent.'
			. $this->dumpHttpRequest($request)
			. $this->dumpHttpResponse($response)
			. $this->dumpElapsedTime($elapsedTime);
	}

	public function formatFailed(RequestInterface $request, \Throwable $exception, float $elapsedTime): string
	{
		return 'Fapi\HttpClient: an HTTP request failed.'
			. $this->dumpHttpRequest($request)
			. $this->dumpException($exception)
			. $this->dumpElapsedTime($elapsedTime);
	}

	private function dumpHttpRequest(RequestInterface $request): string
	{
		return ' Request URL: ' . $this->dumpValue((string) $request->getUri())
			. ' Request method: ' . $this->dumpValue($request->getMethod())
			. ' Request headers: ' . $this->dumpValue($request->getHeaders())
			. ' Request body: ' . $this->dumpValue((string) $request->getBody());
	}

	private function dumpHttpResponse(ResponseInterface $response): string
	{
		return ' Response status code: ' . $this->dumpValue($response->getStatusCode())
			. ' Response headers: ' . $this->dumpValue($response->getHeaders())
			. ' Response body: ' . $this->dumpValue((string) $response->getBody());
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
		$elapsedTime *= 1000;

		return ' Elapsed time: ' . \sprintf('%0.2f', $elapsedTime) . ' ms';
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

}
