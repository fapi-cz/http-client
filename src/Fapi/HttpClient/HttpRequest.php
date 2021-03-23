<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use Fapi\HttpClient\Utils\Json;
use Fapi\HttpClient\Utils\JsonException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use function base64_encode;
use function count;
use function http_build_query;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

class HttpRequest extends Request
{

	/** @var array<mixed> */
	private static array $defaults = ['verify' => true];

	/**
	 * @inheritdoc
	 */
	public function __construct(string $method, $uri, array $headers = [], $body = null, string $version = '1.1')
	{
		if (!HttpMethod::isValid($method)) {
			throw new InvalidArgumentException('Parameter method must be an HTTP method.');
		}

		parent::__construct($method, $uri, $headers, $body, $version);
	}

	/**
	 * @param UriInterface|string $uri
	 * @param array<mixed> $options
	 */
	public static function from($uri, string $method = HttpMethod::GET, array $options = []): HttpRequest
	{
		$body = null;
		$options = static::preProcessHeaders($options, $body);

		return new self($method, $uri, $options, $body);
	}

	/**
	 * @param array<mixed> $options
	 * @param StreamInterface|string $body
	 * @return array<mixed>
	 */
	private static function preProcessHeaders(array $options, &$body): array
	{
		$data = self::$defaults;

		if (isset($options['form_params'])) {
			$value = $options['form_params'];
			static::validateFormParamsOption($value);
			$body = http_build_query($value, '', '&');
			$data['Content-Type'] = 'application/x-www-form-urlencoded';
		}

		if (isset($options['headers'])) {
			$value = $options['headers'];
			static::validateHeadersOption($value);
			$data += $value;
		}

		if (isset($options['auth'])) {
			$value = $options['auth'];
			static::validateAuthOption($value);
			$data['Authorization'] = 'Basic ' . base64_encode($value[0] . ':' . $value[1]);
		}

		if (isset($options['body'])) {
			$value = $options['body'];
			static::validateBodyOption($value);
			$body = $value;
		}

		if (isset($options['json'])) {
			$value = $options['json'];
			static::validateJsonOption($value);
			$body = Json::encode($value);
			$data['Content-Type'] = 'application/json';
		}

		if (isset($options['timeout'])) {
			$value = $options['timeout'];
			static::validateTimeoutOption($value);
			$data['timeout'] = $value;
		}

		if (isset($options['connect_timeout'])) {
			$value = $options['connect_timeout'];
			static::validateConnectTimeoutOption($value);
			$data['connect_timeout'] = $value;
		}

		if (isset($options['verify'])) {
			$value = $options['verify'];
			static::validateVerify($value);
			$data['verify'] = (bool) $value;
		}

		return $data;
	}

	/**
	 * @param mixed $formParams
	 */
	private static function validateFormParamsOption($formParams): void
	{
		if (!is_array($formParams)) {
			throw new InvalidArgumentException('Form params must be an array.');
		}

		foreach ($formParams as $value) {
			if (!is_string($value)) {
				throw new InvalidArgumentException('Form param must be a string.');
			}
		}
	}

	/**
	 * @param mixed $headers
	 */
	private static function validateHeadersOption($headers): void
	{
		if (!is_array($headers)) {
			throw new InvalidArgumentException('Headers must be an array.');
		}

		foreach ($headers as $values) {
			if (is_array($values)) {
				foreach ($values as $value) {
					if (!is_string($value)) {
						throw new InvalidArgumentException('Header value must be a string.');
					}
				}
			} elseif (!is_string($values)) {
				throw new InvalidArgumentException('Header must be an array or string.');
			}
		}
	}

	/**
	 * @param mixed $auth
	 */
	private static function validateAuthOption($auth): void
	{
		if (!is_array($auth)) {
			throw new InvalidArgumentException('Parameter auth must be an array.');
		}

		if (count($auth) !== 2 || !isset($auth[0], $auth[1])) {
			throw new InvalidArgumentException(
				'Parameter auth must be an array of two elements (username and password).',
			);
		}

		if (!is_string($auth[0])) {
			throw new InvalidArgumentException('Username is not a string.');
		}

		if (!is_string($auth[1])) {
			throw new InvalidArgumentException('Password is not a string.');
		}
	}

	/**
	 * @param mixed $body
	 */
	private static function validateBodyOption($body): void
	{
		if (!is_string($body)) {
			throw new InvalidArgumentException('Body must be a string.');
		}
	}

	/**
	 * @param mixed $json
	 */
	private static function validateJsonOption($json): void
	{
		try {
			Json::encode($json);
		} catch (JsonException $e) {
			throw new InvalidArgumentException('Option json must be serializable to JSON.', 0, $e);
		}
	}

	/**
	 * @param mixed $timeout
	 */
	private static function validateTimeoutOption($timeout): void
	{
		if ($timeout !== null && !is_int($timeout)) {
			throw new InvalidArgumentException('Option timeout must be an integer or null.');
		}
	}

	/**
	 * @param mixed $connectTimeout
	 */
	private static function validateConnectTimeoutOption($connectTimeout): void
	{
		if ($connectTimeout !== null && !is_int($connectTimeout)) {
			throw new InvalidArgumentException('Option connectTimeout must be an integer or null.');
		}
	}

	/**
	 * @param mixed $verify
	 */
	private static function validateVerify($verify): void
	{
		if (!is_bool($verify)) {
			throw new InvalidArgumentException('Option verify must be an bool.');
		}
	}

}
