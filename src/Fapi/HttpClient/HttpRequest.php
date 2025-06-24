<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use Fapi\HttpClient\Utils\Json;
use Fapi\HttpClient\Utils\JsonException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use function base64_encode;
use function count;
use function file_exists;
use function http_build_query;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_encode;
use function var_dump;

class HttpRequest extends Request
{

	/** @var array<mixed> */
	private static array $defaults = ['verify' => true];

	/**
	 * @param array<mixed> $headers
	 */
	public function __construct(
		string $method,
		UriInterface|string $uri,
		array $headers = [],
		StreamInterface|string|null $body = null,
		string $version = '1.1',
	)
	{
		if (!HttpMethod::isValid($method)) {
			throw new InvalidArgumentException('Parameter method must be an HTTP method.');
		}

		parent::__construct($method, $uri, $headers, $body, $version);
	}

	/**
	 * @param array<mixed> $options
	 */
	public static function from(UriInterface|string $uri, string $method = HttpMethod::GET, array $options = []): self
	{
		$body = '';
		$options = self::preProcessHeaders($options, $body);

		return new self($method, $uri, $options, $body);
	}

	/**
	 * @param array<mixed> $options
	 * @return array<mixed>
	 */
	private static function preProcessHeaders(array $options, StreamInterface|string &$body): array
	{
		$data = self::$defaults;

		if (isset($options['form_params'])) {
			$value = $options['form_params'];
			self::validateFormParamsOption($value);
			$body = http_build_query($value, '', '&');
			$data['Content-Type'] = 'application/x-www-form-urlencoded';
		}

		if (isset($options['headers'])) {
			$value = $options['headers'];
			self::validateHeadersOption($value);
			$data += $value;
		}

		if (isset($options['auth'])) {
			$value = $options['auth'];
			self::validateAuthOption($value);
			$data['Authorization'] = 'Basic ' . base64_encode($value[0] . ':' . $value[1]);
		}

		if (isset($options['body'])) {
			$value = $options['body'];
			self::validateBodyOption($value);
			$body = $value;
		}

		if (isset($options['json'])) {
			$value = $options['json'];
			self::validateJsonOption($value);
			$body = Json::encode($value);
			$data['Content-Type'] = 'application/json';
		}

		if (isset($options['timeout'])) {
			$value = $options['timeout'];
			self::validateTimeoutOption($value);
			$data['timeout'] = $value;
		}

		if (isset($options['connect_timeout'])) {
			$value = $options['connect_timeout'];
			self::validateConnectTimeoutOption($value);
			$data['connect_timeout'] = $value;
		}

		if (isset($options['verify'])) {
			$value = $options['verify'];
			self::validateVerify($value);
			if (is_bool($value)) {
				$value = $value ? 'true' : 'false';
			}

			$data['verify'] = $value;
		}

		if (isset($options['cert'])) {
			$value = $options['cert'];
			self::validateCertOption($value);
			$data['cert'] = $value;
		}

		if (isset($options['ssl_key'])) {
			$value = $options['ssl_key'];
			self::validateSslKeyOption($value);
			$data['ssl_key'] = $value;
		}

		return $data;
	}

	private static function validateFormParamsOption(mixed $formParams): void
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

	private static function validateHeadersOption(mixed $headers): void
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

	private static function validateAuthOption(mixed $auth): void
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

	private static function validateBodyOption(mixed $body): void
	{
		if (!is_string($body)) {
			throw new InvalidArgumentException('Body must be a string.');
		}
	}

	private static function validateJsonOption(mixed $json): void
	{
		try {
			Json::encode($json);
		} catch (JsonException $e) {
			throw new InvalidArgumentException('Option json must be serializable to JSON.', 0, $e);
		}
	}

	private static function validateTimeoutOption(mixed $timeout): void
	{
		if ($timeout !== null && !is_int($timeout)) {
			throw new InvalidArgumentException('Option timeout must be an integer or null.');
		}
	}

	private static function validateConnectTimeoutOption(mixed $connectTimeout): void
	{
		if ($connectTimeout !== null && !is_int($connectTimeout)) {
			throw new InvalidArgumentException('Option connectTimeout must be an integer or null.');
		}
	}

	private static function validateVerify(mixed $verify): void
	{
		if (!(is_bool($verify) || (is_string($verify) && file_exists($verify)))) {
			throw new InvalidArgumentException(
				'Option verify must be an bool or a string path to file, ' . $verify . ' given.',
			);
		}
	}

	private static function validateCertOption(mixed $value): void
	{
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException('Option cert must be a string.');
		}

		if (is_array($value)) {
			if (!isset($value[0]) || !isset($value[1])) {
				throw new InvalidArgumentException(
					'Option cert must be an array of two elements (cert and key). Provided array: ' . json_encode(
						$value,
					),
				);
			}

			if (!is_string($value[0]) || !is_string($value[1])) {
				throw new InvalidArgumentException(
					'Option cert must be an array of two strings (cert and key). Provided array: ' . json_encode(
						$value,
					),
				);
			}

			$file = $value[0];
		} else {
			$file = $value;
		}

		var_dump(file_exists($file));

		if (!file_exists($file)) {
			throw new InvalidArgumentException('Option cert file not found. Provided file: ' . $file);
		}
	}

	private static function validateSslKeyOption(mixed $value): void
	{
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException('Option ssl_key must be a string.');
		}

		if (is_array($value)) {
			if (!isset($value[0]) || !isset($value[1])) {
				throw new InvalidArgumentException(
					'Option ssl_key must be an array of two elements (key and password). Provided array: ' . json_encode(
						$value,
					) . '.',
				);
			}

			if (!is_string($value[0]) || !is_string($value[1])) {
				throw new InvalidArgumentException(
					'Option ssl_key must be an array of two strings (key and password). Provided array: ' . json_encode(
						$value,
					) . '.',
				);
			}

			$file = $value[0];
		} else {
			$file = $value;
		}

		if (!file_exists($file)) {
			throw new InvalidArgumentException('Option ssl_key file not found. Provided file: ' . $file);
		}
	}

}
