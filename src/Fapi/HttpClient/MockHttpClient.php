<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use Exception;
use Fapi\HttpClient\Utils\Json;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function array_keys;
use function array_shift;
use function assert;
use function count;
use function implode;
use function ini_set;
use function preg_last_error;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function rtrim;
use function str_replace;
use function strlen;
use function substr;

class MockHttpClient implements IHttpClient
{

	/** @var array<RequestInterface> */
	private array $requests = [];

	/** @var array<ResponseInterface> */
	private array $responses = [];

	/** @var array<string,string> */
	public static array $patterns = [
		'%%' => '%', // one % character
		'%a%' => '[^\r\n]+', // one or more of anything except the end of line characters
		'%a\?%' => '[^\r\n]*', // zero or more of anything except the end of line characters
		'%A%' => '.+', // one or more of anything including the end of line characters
		'%A\?%' => '.*', // zero or more of anything including the end of line characters
		'%s%' => '[\t ]+', // one or more white space characters except the end of line characters
		'%s\?%' => '[\t ]*', // zero or more white space characters except the end of line characters
		'%S%' => '\S+', // one or more of characters except the white space
		'%S\?%' => '\S*', // zero or more of characters except the white space
		'%c%' => '[^\r\n]', // a single character of any sort (except the end of line)
		'%d%' => '[0-9]+', // one or more digits
		'%d\?%' => '[0-9]*', // zero or more digits
		'%i%' => '[+-]?[0-9]+', // signed integer value
		'%f%' => '[+-]?\.?\d+\.?\d*(?:[Ee][+-]?\d+)?', // floating point number
		'%h%' => '[0-9a-fA-F]+', // one or more HEX digits
		'%w%' => '[0-9a-zA-Z_]+', //one or more alphanumeric characters
		'%ds%' => '[\\\\/]', // directory separator
		'%(\[.+\][+*?{},\d]*)%' => '$1', // range
	];

	public function add(RequestInterface $request, ResponseInterface $response): void
	{
		$this->requests[] = $request;
		$this->responses[] = $response;
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		if (!isset($this->requests[0])) {
			throw new InvalidArgumentException('Invalid HTTP request. No more requests found.');
		}

		$expectedRequest = $this->requests[0];
		$this->assertHttpRequestUrl($expectedRequest, $request);
		$this->assertHttpRequestMethod($expectedRequest, $request);
		$this->assertHttpRequestOptions($expectedRequest, $request);
		$this->assertHttpRequestBody($expectedRequest, $request);

		array_shift($this->requests);
		$response = array_shift($this->responses);
		assert($response instanceof HttpResponse);

		return $response;
	}

	public function wereAllHttpRequestsSent(): bool
	{
		return count($this->requests) === 0;
	}

	private function assertHttpRequestUrl(RequestInterface $expected, RequestInterface $actual): void
	{
		if ((string) $expected->getUri() === (string) $actual->getUri()) {
			return;
		}

		$expectedUrl = $this->formatUrl((string) $expected->getUri());
		$actualUrl = $this->formatUrl((string) $actual->getUri());

		throw new InvalidArgumentException(
			'Invalid HTTP request. Url not matched. Expected "'
			. $expectedUrl . '" got "' . $actualUrl . '".',
		);
	}

	private function assertHttpRequestMethod(RequestInterface $expected, RequestInterface $actual): void
	{
		if ($expected->getMethod() === $actual->getMethod()) {
			return;
		}

		throw new InvalidArgumentException(
			'Invalid HTTP request. Method not matched. Expected "'
			. $expected->getMethod() . '" got "' . $actual->getMethod() . '".',
		);
	}

	private function assertHttpRequestOptions(RequestInterface $expected, RequestInterface $actual): void
	{
		if ($expected->getHeaders() === $actual->getHeaders()) {
			return;
		}

		throw new InvalidArgumentException(
			'Invalid HTTP request. Options not matched. Expected: "'
			. Json::encode($expected->getHeaders())
			. '", got: "'
			. Json::encode($actual->getHeaders())
			. '".',
		);
	}

	private function assertHttpRequestBody(RequestInterface $expected, RequestInterface $actual): void
	{
		if (self::isMatching((string) $expected->getBody(), (string) $actual->getBody())) {
			return;
		}

		throw new InvalidArgumentException(
			'Invalid HTTP request. Body not matched. Expected: "'
			. $expected->getBody()
			. '", got: "'
			. $actual->getBody()
			. '".',
		);
	}

	private function formatUrl(string $url): string
	{
		if (strlen($url) > 250) {
			return substr($url, 200) . '...';
		}

		return $url;
	}

	public static function isMatching(string $pattern, string $actual, bool $strict = false): bool
	{
		$old = ini_set('pcre.backtrack_limit', '10000000');

		if (!self::isPcre($pattern)) {
			$utf8 = (bool) preg_match('#\x80-\x{10FFFF}]#u', $pattern) ? 'u' : '';
			$suffix = ($strict ? '$#DsU' : '\s*$#sU') . $utf8;
			$patterns = static::$patterns + [
				'[.\\\\+*?[^$(){|\#]' => '\$0', // preg quoting
				'\x00' => '\x00',
				'[\t ]*\r?\n' => '[\t ]*\r?\n', // right trim
			];
			$pattern = '#^' . preg_replace_callback(
				'#' . implode('|', array_keys($patterns)) . '#U' . $utf8,
				static function ($m) use ($patterns) {
					foreach ($patterns as $re => $replacement) {
						$s = preg_replace("#^$re$#D", str_replace('\\', '\\\\', $replacement), $m[0], 1, $count);
						if ((bool) $count) {
							return $s;
						}
					}
				},
				rtrim($pattern, " \t\n\r"),
			) . $suffix;
		}

		$res = preg_match($pattern, $actual);
		ini_set('pcre.backtrack_limit', $old);
		if ($res === false || (bool) preg_last_error()) {
			throw new Exception(
				'Error while executing regular expression. (PREG Error Code ' . preg_last_error() . ')',
			);
		}

		return (bool) $res;
	}

	private static function isPcre(string $pattern): bool
	{
		return (bool) preg_match('/^([~#]).+(\1)[imsxUu]*$/Ds', $pattern);
	}

}
