<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use function class_exists;
use function defined;
use function strlen;
use function strncmp;
use const CURLE_OPERATION_TIMEOUTED;

class GuzzleHttpClient implements IHttpClient
{

	/** @var Client */
	private $client;

	public function __construct()
	{
		if (!class_exists('GuzzleHttp\\Client')) {
			throw new InvalidStateException('Guzzle HTTP client requires Guzzle library to be installed.');
		}

		$this->client = new Client();
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$options = $this->processOptions($request);
		$request = $request->withoutHeader('timeout')
			->withoutHeader('connect_timeout')
			->withoutHeader('verify')
			->withHeader('Accept-Encoding', 'gzip');

		try {
			$response = $this->client->send($request, $options + $this->getDefaultOptions());
			$response = new HttpResponse(
				$response->getStatusCode(),
				$response->getHeaders(),
				$response->getBody(),
				$response->getProtocolVersion(),
				$response->getReasonPhrase()
			);
		} catch (TransferException $e) {
			if ($this->isTimeoutException($e)) {
				throw new TimeLimitExceededException('Time limit for HTTP request exceeded.', $e->getCode(), $e);
			}

			throw new HttpClientException('Failed to make an HTTP request.', $e->getCode(), $e);
		}

		return $response;
	}

	/**
	 * @return array<mixed>
	 */
	private function getDefaultOptions(): array
	{
		return [
			RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath(),
			RequestOptions::ALLOW_REDIRECTS => false,
			RequestOptions::HTTP_ERRORS => false,
		];
	}

	private function isTimeoutException(Throwable $e): bool
	{
		if (!$e instanceof ConnectException) {
			return false;
		}

		if (!defined('CURLE_OPERATION_TIMEOUTED')) {
			return false;
		}

		$messagePrefix = 'cURL error ' . CURLE_OPERATION_TIMEOUTED . ':';

		return strncmp($e->getMessage(), $messagePrefix, strlen($messagePrefix)) === 0;
	}

	/**
	 * @return array<mixed>
	 */
	private function processOptions(RequestInterface $request): array
	{
		$options = [];

		if ($request->hasHeader('timeout')) {
			$options['timeout'] = (int) ($request->getHeaderLine('timeout') ?? 5);
		}

		if ($request->hasHeader('connect_timeout')) {
			$options['connect_timeout'] = (int) ($request->getHeaderLine('connect_timeout') ?? 5);
		}

		if ($request->hasHeader('verify') && !(bool) $request->getHeaderLine('verify')) {
			$options['verify'] = false;
		}

		return $options;
	}

}
