<?php declare(strict_types = 1);

namespace Fapi\HttpClient\LoggingFormatters;

use Fapi\HttpClient\ILoggingFormatter;
use Fapi\HttpClient\Utils\Json;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use function extension_loaded;
use function function_exists;
use function get_class;
use function iconv_substr;
use function mb_substr;
use function sprintf;
use function strlen;
use function substr;

class JsonLoggingFormatter implements ILoggingFormatter
{

	public function __construct(private int $maxBodyLength = 40000)
	{
	}

	public function formatSuccessful(RequestInterface $request, ResponseInterface $response, float $elapsedTime): string
	{
		return Json::encode([
			'class' => 'Fapi\HttpClient',
			'description' => 'an HTTP request has been sent.',
			'request' => [
				'url' => (string) $request->getUri(),
				'method' => $request->getMethod(),
				'headers' => $request->getHeaders(),
				'body' => $this->processBody((string) $request->getBody()),
			],
			'response' => [
				'statusCode' => $response->getStatusCode(),
				'headers' => $response->getHeaders(),
				'body' => $this->processBody((string) $response->getBody()),
			],
			'elapsedTime' => $this->formatElapsedTime($elapsedTime),
		]);
	}

	public function formatFailed(RequestInterface $request, Throwable $exception, float $elapsedTime): string
	{
		return Json::encode([
			'class' => 'Fapi\HttpClient',
			'description' => 'an HTTP request failed.',
			'request' => [
				'url' => (string) $request->getUri(),
				'method' => $request->getMethod(),
				'headers' => $request->getHeaders(),
				'body' => $this->processBody((string) $request->getBody()),
			],
			'exception' => [
				'type' => $exception::class,
				'message' => $exception->getMessage(),
				'previous' => $exception->getPrevious() !== null ? [
					'type' => get_class($exception->getPrevious()),
					'message' => $exception->getPrevious()->getMessage(),
				] : null,
			],
			'elapsedTime' => $this->formatElapsedTime($elapsedTime),
		]);
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

	private function formatElapsedTime(float $elapsedTime): string
	{
		return sprintf('%0.2f', $elapsedTime * 1000);
	}

}
