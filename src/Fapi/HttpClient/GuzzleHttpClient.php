<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class GuzzleHttpClient implements IHttpClient
{

	/** @var Client */
	private $client;

	public function __construct()
	{
		if (!\class_exists('GuzzleHttp\\Client')) {
			throw new InvalidStateException('Guzzle HTTP client requires Guzzle library to be installed.');
		}

		$this->client = new Client();
	}

	public function sendHttpRequest(HttpRequest $httpRequest): HttpResponse
	{
		$options = $httpRequest->getOptions() + $this->getDefaultOptions();

		try {
			$response = $this->client->request(
				$httpRequest->getMethod(),
				$httpRequest->getUrl(),
				$options
			);

			$httpResponse = new HttpResponse(
				$response->getStatusCode(),
				$response->getHeaders(),
				(string) $response->getBody()
			);

		} catch (\GuzzleHttp\Exception\TransferException $e) {
			if ($this->isTimeoutException($e)) {
				throw new TimeLimitExceededException('Time limit for HTTP request exceeded.', $e->getCode(), $e);
			}

			throw new HttpClientException('Failed to make an HTTP request.', $e->getCode(), $e);
		}

		return $httpResponse;
	}

	/**
	 * @return mixed[]
	 */
	private function getDefaultOptions(): array
	{
		return [
			RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath(),
			RequestOptions::ALLOW_REDIRECTS => false,
		];
	}

	private function isTimeoutException(\Throwable $e): bool
	{
		if (!$e instanceof \GuzzleHttp\Exception\ConnectException) {
			return false;
		}

		if (!\defined('CURLE_OPERATION_TIMEOUTED')) {
			return false;
		}

		$messagePrefix = 'cURL error ' . \CURLE_OPERATION_TIMEOUTED . ':';

		return \strncmp($e->getMessage(), $messagePrefix, \strlen($messagePrefix)) === 0;
	}

}
