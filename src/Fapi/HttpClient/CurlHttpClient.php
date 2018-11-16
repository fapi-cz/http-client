<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Composer\CaBundle\CaBundle;
use Fapi\HttpClient\Utils\Json;

class CurlHttpClient implements IHttpClient
{

	public function __construct()
	{
		if (!\extension_loaded('curl')) {
			throw new NotSupportedException('cURL extension must be installed.');
		}
	}

	public function sendHttpRequest(HttpRequest $httpRequest): HttpResponse
	{
		$handle = $this->initializeCurl($httpRequest);
		$this->processOptions($httpRequest->getOptions(), $handle);

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

	private function initializeCurl(HttpRequest $httpRequest)
	{
		$handle = \curl_init();

		\curl_setopt_array($handle, [
			\CURLOPT_URL => $httpRequest->getUrl(),
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
		if (isset($options['headers'])) {
			if (isset($options['form_params'])) {
				static::setDefaultContentType($options['headers'], 'application/x-www-form-urlencoded');
			}

			if (isset($options['json'])) {
				static::setDefaultContentType($options['headers'], 'application/json');
			}
		}

		foreach ($options as $key => $value) {
			if ($key === 'form_params') {
				\curl_setopt($handle, \CURLOPT_POSTFIELDS, \http_build_query($value, '', '&', \PHP_QUERY_RFC1738));
			} elseif ($key === 'headers') {
				\curl_setopt($handle, \CURLOPT_HTTPHEADER, $this->formatHeaders($value));
			} elseif ($key === 'auth') {
				\curl_setopt($handle, \CURLOPT_USERPWD, $value[0] . ':' . $value[1]);
			} elseif ($key === 'body') {
				\curl_setopt($handle, \CURLOPT_POSTFIELDS, $value);

				if (\defined('CURLOPT_SAFE_UPLOAD')) {
					\curl_setopt($handle, \CURLOPT_SAFE_UPLOAD, true);
				}
			} elseif ($key === 'json') {
				\curl_setopt($handle, \CURLOPT_POSTFIELDS, Json::encode($value));

				if (\defined('CURLOPT_SAFE_UPLOAD')) {
					\curl_setopt($handle, \CURLOPT_SAFE_UPLOAD, true);
				}
			} elseif ($key === 'cookies') {
				if ($value !== null) {
					throw new NotSupportedException('CurlHttpClient does not support option cookies.');
				}
			} elseif ($key === 'timeout') {
				if ($value !== null) {
					\curl_setopt($handle, \CURLOPT_TIMEOUT, $value);
				}
			} elseif ($key === 'connect_timeout') {
				if ($value !== null) {
					\curl_setopt($handle, \CURLOPT_CONNECTTIMEOUT, $value);
				}
			} else {
				throw new InvalidArgumentException("Option '$key' is not supported.");
			}
		}

		return $options;
	}

	/**
	 * @param mixed[] $headers
	 * @return mixed[]
	 */
	private function formatHeaders(array $headers): array
	{
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
	private function parseHeaders(string $header): array
	{
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

	/**
	 * @param mixed[] $headers
	 * @param string $contentType
	 * @return void
	 */
	private static function setDefaultContentType(array &$headers, string $contentType)
	{
		$keys = \array_keys($headers);

		foreach ($keys as $key) {
			if (\strcasecmp($key, 'Content-Type') === 0) {
				return;
			}
		}

		$headers['Content-Type'] = $contentType;
	}

}
