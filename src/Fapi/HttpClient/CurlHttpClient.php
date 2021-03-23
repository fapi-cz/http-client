<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use Composer\CaBundle\CaBundle;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function assert;
use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function curl_setopt_array;
use function explode;
use function extension_loaded;
use function is_array;
use function is_dir;
use function is_link;
use function is_string;
use function preg_match;
use function readlink;
use function substr;
use function trim;
use const CURLE_OPERATION_TIMEOUTED;
use const CURLINFO_HEADER_SIZE;
use const CURLINFO_HTTP_CODE;
use const CURLOPT_CAINFO;
use const CURLOPT_CAPATH;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_ENCODING;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;

class CurlHttpClient implements IHttpClient
{

	public function __construct()
	{
		if (!extension_loaded('curl')) {
			throw new NotSupportedException('cURL extension must be installed.');
		}
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$handle = $this->initializeCurl($request);
		$this->processOptions($request, $handle);
		$request = $this->processHeaders($request, $handle);

		if ($request->getBody()->getSize() > 0) {
			curl_setopt($handle, CURLOPT_POSTFIELDS, (string) $request->getBody());
		}

		$result = curl_exec($handle);
		assert(is_string($result) || $result === false);

		if ($result === false) {
			$error = curl_error($handle);
			$errno = curl_errno($handle);
			curl_close($handle);

			if ($errno === CURLE_OPERATION_TIMEOUTED) {
				throw new TimeLimitExceededException($error, $errno);
			}

			throw new HttpClientException($error, $errno);
		}

		$headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
		$header = substr($result, 0, $headerSize);

		$headers = $this->parseHeaders($header);
		$body = substr($result, $headerSize);
		$statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		$httpResponse = new HttpResponse($statusCode, $headers, $body);
		curl_close($handle);

		return $httpResponse;
	}

	/**
	 * @return resource
	 */
	private function initializeCurl(RequestInterface $httpRequest)
	{
		$handle = curl_init();

		curl_setopt_array($handle, [
			CURLOPT_URL => (string) $httpRequest->getUri(),
			CURLOPT_CUSTOMREQUEST => $httpRequest->getMethod(),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_ENCODING => 'gzip',
		]);

		return $handle;
	}

	/**
	 * @param resource $handle
	 */
	private function processOptions(RequestInterface $request, $handle): void
	{
		if ($request->hasHeader('timeout')) {
			curl_setopt($handle, CURLOPT_TIMEOUT, (int) $request->getHeaderLine('timeout'));
		} elseif ($request->hasHeader('connect_timeout')) {
			curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, (int) $request->getHeaderLine('connect_timeout'));
		}

		$this->processVerifyOption($request->getHeaderLine('verify'), $handle);
	}

	/**
	 * @param resource $handle
	 */
	private function processVerifyOption(string $verify, $handle): void
	{
		if ((bool) $verify) {
			$caPathOrFile = CaBundle::getSystemCaRootBundlePath();

			if (is_dir($caPathOrFile) || (is_link($caPathOrFile) && is_dir((string) readlink($caPathOrFile)))) {
				curl_setopt($handle, CURLOPT_CAPATH, $caPathOrFile);
			} else {
				curl_setopt($handle, CURLOPT_CAINFO, $caPathOrFile);
			}

			return;
		}

		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	}

	/**
	 * @param resource $handle
	 */
	private function processHeaders(RequestInterface $request, $handle): RequestInterface
	{
		$request = $request->withoutHeader('timeout')
			->withoutHeader('connect_timeout')
			->withoutHeader('verify');

		curl_setopt($handle, CURLOPT_HTTPHEADER, $this->formatHeaders($request->getHeaders()));

		return $request;
	}

	/**
	 * @param array<mixed> $headers
	 * @return array<mixed>
	 */
	private function formatHeaders(array $headers): array
	{
		$result = [];

		foreach ($headers as $key => $values) {
			$values = is_array($values)
				? $values
				: [$values];

			foreach ($values as $value) {
				$result[] = $key . ': ' . $value;
			}
		}

		return $result;
	}

	/**
	 * @return array<mixed>
	 */
	private function parseHeaders(string $header): array
	{
		$headers = [];

		foreach (explode("\n", $header) as $line) {
			$line = trim($line);
			preg_match('#^([A-Za-z\-]+): (.*)\z#', $line, $match);

			if (!$match) {
				continue;
			}

			$headers[$match[1]][] = $match[2];
		}

		return $headers;
	}

}
