<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Fapi\HttpClient\Utils\Json;
use Fapi\HttpClient\Utils\JsonException;
use GuzzleHttp\Cookie\CookieJarInterface;

class HttpRequest
{

	/** @var string */
	private $url;

	/** @var string */
	private $method;

	/** @var mixed[] */
	private $options;

	/**
	 * @param string $url
	 * @param string $method
	 * @param mixed[] $options
	 */
	public function __construct(string $url, string $method = HttpMethod::GET, array $options = [])
	{
		if (!\is_string($url)) {
			throw new InvalidArgumentException('Parameter url must be a string.');
		}

		if (!HttpMethod::isValid($method)) {
			throw new InvalidArgumentException('Parameter method must be an HTTP method.');
		}

		static::validateOptions($options);

		$this->url = $url;
		$this->method = $method;
		$this->options = $options;
	}

	/**
	 * @param mixed[] $options
	 * @return void
	 */
	private static function validateOptions(array $options)
	{
		foreach ($options as $key => $value) {
			if ($key === 'form_params') {
				static::validateFormParamsOption($value);
			} elseif ($key === 'headers') {
				static::validateHeadersOption($value);
			} elseif ($key === 'auth') {
				static::validateAuthOption($value);
			} elseif ($key === 'body') {
				static::validateBodyOption($value);
			} elseif ($key === 'json') {
				static::validateJsonOption($value);
			} elseif ($key === 'cookies') {
				static::validateCookiesOption($value);
			} elseif ($key === 'timeout') {
				static::validateTimeoutOption($value);
			} elseif ($key === 'connect_timeout') {
				static::validateConnectTimeoutOption($value);
			} else {
				throw new InvalidArgumentException("Option '$key' is not supported.");
			}
		}
	}

	/**
	 * @param mixed $formParams
	 * @return void
	 */
	private static function validateFormParamsOption($formParams)
	{
		if (!\is_array($formParams)) {
			throw new InvalidArgumentException('Form params must be an array.');
		}

		foreach ($formParams as $value) {
			if (!\is_string($value)) {
				throw new InvalidArgumentException('Form param must be a string.');
			}
		}
	}

	/**
	 * @param mixed $headers
	 * @return void
	 */
	private static function validateHeadersOption($headers)
	{
		if (!\is_array($headers)) {
			throw new InvalidArgumentException('Headers must be an array.');
		}

		foreach ($headers as $values) {
			if (\is_array($values)) {
				foreach ($values as $value) {
					if (!\is_string($value)) {
						throw new InvalidArgumentException('Header value must be a string.');
					}
				}
			} elseif (!\is_string($values)) {
				throw new InvalidArgumentException('Header must be an array or string.');
			}
		}
	}

	/**
	 * @param mixed $auth
	 * @return void
	 */
	private static function validateAuthOption($auth)
	{
		if (!\is_array($auth)) {
			throw new InvalidArgumentException('Parameter auth must be an array.');
		}

		if (\count($auth) !== 2 || !isset($auth[0], $auth[1])) {
			throw new InvalidArgumentException('Parameter auth must be an array of two elements (username and password).');
		}

		if (!\is_string($auth[0])) {
			throw new InvalidArgumentException('Username is not a string.');
		}

		if (!\is_string($auth[1])) {
			throw new InvalidArgumentException('Password is not a string.');
		}
	}

	/**
	 * @param mixed $body
	 * @return void
	 */
	private static function validateBodyOption($body)
	{
		if (!\is_string($body)) {
			throw new InvalidArgumentException('Body must be a string.');
		}
	}

	/**
	 * @param mixed $json
	 * @return void
	 */
	private static function validateJsonOption($json)
	{
		try {
			Json::encode($json);
		} catch (JsonException $e) {
			throw new InvalidArgumentException('Option json must be serializable to JSON.', 0, $e);
		}
	}

	/**
	 * @param mixed $cookies
	 * @return void
	 */
	private static function validateCookiesOption($cookies)
	{
		if ($cookies !== null && !$cookies instanceof CookieJarInterface) {
			throw new InvalidArgumentException('Option cookies must be an instance of CookieJarInterface or null.');
		}
	}

	/**
	 * @param mixed $timeout
	 * @return void
	 */
	private static function validateTimeoutOption($timeout)
	{
		if ($timeout !== null && !\is_int($timeout)) {
			throw new InvalidArgumentException('Option timeout must be an integer or null.');
		}
	}

	/**
	 * @param mixed $connectTimeout
	 * @return void
	 */
	private static function validateConnectTimeoutOption($connectTimeout)
	{
		if ($connectTimeout !== null && !\is_int($connectTimeout)) {
			throw new InvalidArgumentException('Option connectTimeout must be an integer or null.');
		}
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * @return mixed[]
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

}
