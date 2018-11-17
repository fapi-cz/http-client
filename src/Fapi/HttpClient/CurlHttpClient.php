<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Composer\CaBundle\CaBundle;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CurlHttpClient implements IHttpClient
{

	public function __construct()
	{
		if (!\extension_loaded('curl')) {
			throw new NotSupportedException('cURL extension must be installed.');
		}
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$handle = $this->initializeCurl($request);
		$this->processOptions($request->getHeaders(), $handle);

		if ($request->getBody()->getSize() > 0) {
			\curl_setopt($handle, \CURLOPT_POSTFIELDS, (string) $request->getBody());
		}

		/** @var string|false $result */
		$result = \curl_exec($handle);

		if ($result === false) {
			$error = \curl_error($handle);
			$errno = \curl_errno($handle);
			\curl_close($handle);

			if ($errno === \CURLE_OPERATION_TIMEOUTED) {
				throw new TimeLimitExceededException($error, $errno);
			}

			throw new HttpClientException($error, $errno);
		}

		$headerSize = \curl_getinfo($handle, \CURLINFO_HEADER_SIZE);
		$header = \substr($result, 0, $headerSize);

		$headers = $this->parseHeaders($header);
		$body = \substr($result, $headerSize);
		$statusCode = \curl_getinfo($handle, \CURLINFO_HTTP_CODE);

		$httpResponse = new HttpResponse($statusCode, $headers, $body);
		\curl_close($handle);

		return $httpResponse;
	}

	private function initializeCurl(RequestInterface $httpRequest)
	{
		$handle = \curl_init();

		\curl_setopt_array($handle, [
			\CURLOPT_URL => (string) $httpRequest->getUri(),
			\CURLOPT_CUSTOMREQUEST => $httpRequest->getMethod(),
			\CURLOPT_RETURNTRANSFER => true,
			\CURLOPT_HEADER => true,
		]);

		$caPathOrFile = CaBundle::getSystemCaRootBundlePath();

		if (\is_dir($caPathOrFile) || (\is_link($caPathOrFile) && \is_dir(\readlink($caPathOrFile)))) {
			\curl_setopt($handle, \CURLOPT_CAPATH, $caPathOrFile);
		} else {
			\curl_setopt($handle, \CURLOPT_CAINFO, $caPathOrFile);
		}

		return $handle;
	}

	/**
	 * @param mixed[] $options
	 * @param resource $handle
	 * @return mixed[]
	 */
	private function processOptions(array $options, $handle): array
	{
		foreach ($options as $key => $option) {
			if ($key === 'timeout') {
				\curl_setopt($handle, \CURLOPT_TIMEOUT, (int) $option[0]);
				unset($options[$key]);

			} elseif ($key === 'connect_timeout') {
				\curl_setopt($handle, \CURLOPT_CONNECTTIMEOUT, (int) $option[0]);
				unset($options[$key]);
			}
		}

		\curl_setopt($handle, \CURLOPT_HTTPHEADER, $this->formatHeaders($options));

		return $options;
	}

	/**
	 * @param mixed[] $headers
	 * @return mixed[]
	 */
	private function formatHeaders(
		array $headers
	): array {
		$result = [];

		foreach ($headers as $key => $values) {
			$values = \is_array($values)
				? $values
				: [$values];

			foreach ($values as $value) {
				$result[] = $key . ': ' . $value;
			}
		}

		return $result;
	}

	/**
	 * @param string $header
	 * @return mixed[]
	 */
	private function parseHeaders(
		string $header
	): array {
		$headers = [];

		foreach (\explode("\n", $header) as $line) {
			$line = \trim($line);
			\preg_match('#^([A-Za-z\-]+): (.*)\z#', $line, $match);

			if (!$match) {
				continue;
			}

			$headers[$match[1]][] = $match[2];
		}

		return $headers;
	}

}
