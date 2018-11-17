<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RedirectHelper
{

	public static function followRedirects(
		IHttpClient $httpClient,
		ResponseInterface $response,
		RequestInterface $request,
		int $limit = 5
	): ResponseInterface {
		for ($count = 0; $count < $limit; $count++) {
			$redirectUrl = static::getRedirectUrl($response);

			if ($redirectUrl === null) {
				return $response;
			}

			$request = $request->withUri(new Uri($redirectUrl));
			$response = $httpClient->sendRequest($request);
		}

		throw new TooManyRedirectsException('Maximum number of redirections exceeded.');
	}

	/**
	 * @return string|null
	 */
	private static function getRedirectUrl(ResponseInterface $httpResponse)
	{
		if (!static::isRedirectionStatusCode($httpResponse->getStatusCode())) {
			return null;
		}

		$url = static::getLocationHeader($httpResponse->getHeaders());

		if ($url === null) {
			return null;
		}

		if (!static::isValidRedirectUrl($url)) {
			return null;
		}

		return $url;
	}

	private static function isRedirectionStatusCode(int $code): bool
	{
		return \in_array($code, [HttpStatusCode::S301_MOVED_PERMANENTLY, HttpStatusCode::S302_FOUND], true);
	}

	/**
	 * @param mixed[][] $headers
	 * @return string|null
	 */
	private static function getLocationHeader(array $headers)
	{
		$headers = \array_change_key_case($headers, \CASE_LOWER);

		return $headers['location'][0] ?? null;
	}

	private static function isValidRedirectUrl(string $url): bool
	{
		foreach (['http://', 'https://'] as $prefix) {
			if (\strncasecmp($url, $prefix, \strlen($prefix)) === 0) {
				return true;
			}
		}

		return false;
	}

}
