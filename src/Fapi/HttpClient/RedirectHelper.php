<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use function array_change_key_case;
use function in_array;
use function strlen;
use function strncasecmp;
use const CASE_LOWER;

class RedirectHelper
{

	public static function followRedirects(
		IHttpClient $httpClient,
		ResponseInterface $response,
		RequestInterface $request,
		int $limit = 5,
	): ResponseInterface
	{
		for ($count = 0; $count < $limit; $count++) {
			$redirectUrl = self::getRedirectUrl($response, $request->getUri());

			if ($redirectUrl === null) {
				return $response;
			}

			$request = $request->withUri(new Uri($redirectUrl));
			$response = $httpClient->sendRequest($request);
		}

		throw new TooManyRedirectsException('Maximum number of redirections exceeded.');
	}

	private static function getRedirectUrl(ResponseInterface $httpResponse, UriInterface $requestUri): string|null
	{
		if (!self::isRedirectionStatusCode($httpResponse->getStatusCode())) {
			return null;
		}

		$url = self::getLocationHeader($httpResponse->getHeaders());

		if ($url === null) {
			return null;
		}

		if ($url[0] === '/') {
			$url = $requestUri->getScheme() . '://' . $requestUri->getHost() . $url;
		}

		if (!self::isValidRedirectUrl($url)) {
			return null;
		}

		return $url;
	}

	private static function isRedirectionStatusCode(int $code): bool
	{
		return in_array($code, [HttpStatusCode::S301_MOVED_PERMANENTLY, HttpStatusCode::S302_FOUND], true);
	}

	/**
	 * @param array<array<mixed>> $headers
	 */
	private static function getLocationHeader(array $headers): string|null
	{
		$headers = array_change_key_case($headers, CASE_LOWER);

		return $headers['location'][0] ?? null;
	}

	private static function isValidRedirectUrl(string $url): bool
	{
		foreach (['http://', 'https://'] as $prefix) {
			if (strncasecmp($url, $prefix, strlen($prefix)) === 0) {
				return true;
			}
		}

		return false;
	}

}
