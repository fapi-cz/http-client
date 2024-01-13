<?php declare(strict_types = 1);

namespace Fapi\HttpClient\LoggingFormatters;

use Fapi\HttpClient\ILoggingFormatter;
use Fapi\HttpClient\Utils\Json;
use Fapi\HttpClient\Utils\JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use function base64_encode;
use function extension_loaded;
use function function_exists;
use function iconv_substr;
use function mb_substr;
use function serialize;
use function sprintf;
use function strlen;
use function substr;
use const JSON_UNESCAPED_UNICODE;

class BaseLoggingFormatter implements ILoggingFormatter
{

	public function __construct(private int $maxBodyLength = 40000)
	{
	}

	public function formatSuccessful(RequestInterface $request, ResponseInterface $response, float $elapsedTime): string
	{
		return 'Fapi\HttpClient: an HTTP request has been sent.'
			. $this->dumpHttpRequest($request)
			. $this->dumpHttpResponse($response)
			. $this->dumpElapsedTime($elapsedTime);
	}

	public function formatFailed(RequestInterface $request, Throwable $exception, float $elapsedTime): string
	{
		return 'Fapi\HttpClient: an HTTP request failed.'
			. $this->dumpHttpRequest($request)
			. $this->dumpException($exception)
			. $this->dumpElapsedTime($elapsedTime);
	}

	private function dumpHttpRequest(RequestInterface $request): string
	{
		$body = $this->processBody((string) $request->getBody());

		return ' Request URL: ' . $this->dumpValue((string) $request->getUri())
			. ' Request method: ' . $this->dumpValue($request->getMethod())
			. ' Request headers: ' . $this->dumpValue($request->getHeaders())
			. ' Request body: ' . $this->dumpValue($body);
	}

	private function dumpHttpResponse(ResponseInterface $response): string
	{
		$body = $this->processBody((string) $response->getBody());

		return ' Response status code: ' . $this->dumpValue($response->getStatusCode())
			. ' Response headers: ' . $this->dumpValue($response->getHeaders())
			. ' Response body: ' . $this->dumpValue($body);
	}

	private function dumpException(Throwable $exception): string
	{
		$dump = ' Exception type: ' . $this->dumpValue($exception::class)
			. ' Exception message: ' . $this->dumpValue($exception->getMessage());

		if ($exception->getPrevious() !== null) {
			$previousException = $exception->getPrevious();

			$dump .= ' Previous exception type: ' . $this->dumpValue($previousException::class)
				. ' Previous exception message: ' . $this->dumpValue($previousException->getMessage());
		}

		return $dump;
	}

	private function dumpElapsedTime(float $elapsedTime): string
	{
		$elapsedTime *= 1000;

		return ' Elapsed time: ' . sprintf('%0.2f', $elapsedTime) . ' ms';
	}

	private function dumpValue(mixed $value): string
	{
		try {
			return Json::encode($value, JSON_UNESCAPED_UNICODE);
		} catch (JsonException) {
			return '(serialized) ' . base64_encode(serialize($value));
		}
	}

	private function processBody(string $body): string
	{
		if (strlen($body) <= $this->maxBodyLength) {
			return $body;
		}

		if (function_exists('mb_substr')) {
			return mb_substr($body, 0, $this->maxBodyLength, 'UTF-8');
		}

		if (!extension_loaded('iconv')) {
			return (string) iconv_substr($body, 0, $this->maxBodyLength, 'UTF-8');
		}

		return substr($body, 0, $this->maxBodyLength);
	}

}
