<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

class RedirectHelper
{

	/**
	 * @param IHttpClient $httpClient
	 * @param HttpResponse $httpResponse
	 * @param int $limit
	 * @param mixed[] $options
	 * @param string $method
	 * @return HttpResponse
	 * @throws HttpClientException
	 * @throws TooManyRedirectsException
	 */
	public static function followRedirects(
		IHttpClient $httpClient,
		HttpResponse $httpResponse,
		int $limit = 5,
		array $options = [],
		string $method = HttpMethod::GET
	): HttpResponse {
		for ($count = 0; $count < $limit; $count++) {
			$redirectUrl = static::getRedirectUrl($httpResponse);

			if ($redirectUrl === null) {
				return $httpResponse;
			}

			$httpRequest = new HttpRequest($redirectUrl, $method, $options);
			$httpResponse = $httpClient->sendHttpRequest($httpRequest);
		}

		throw new TooManyRedirectsException('Maximum number of redirections exceeded.');
	}

	/**
	 * @return string|null
	 */
	private static function getRedirectUrl(HttpResponse $httpResponse)
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
