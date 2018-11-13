<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

class HttpResponse
{

	/** @var int */
	private $statusCode;

	/** @var string[][] */
	private $headers;

	/** @var string */
	private $body;

	/**
	 * @param int $statusCode
	 * @param string[][] $headers
	 * @param string $body
	 */
	public function __construct(int $statusCode, array $headers, string $body)
	{
		if (!HttpStatusCode::isValid($statusCode)) {
			throw new InvalidArgumentException('Parameter statusCode must be an HTTP status code.');
		}

		static::validateHeaders($headers);

		if (!\is_string($body)) {
			throw new InvalidArgumentException('Parameter body must be a string.');
		}

		$this->statusCode = $statusCode;
		$this->headers = $headers;
		$this->body = $body;
	}

	/**
	 * @param string[][] $headers
	 * @return void
	 */
	private static function validateHeaders(array $headers)
	{
		foreach ($headers as $values) {
			if (!\is_array($values)) {
				throw new InvalidArgumentException('Header values must be an array.');
			}

			foreach ($values as $value) {
				if (!\is_string($value)) {
					throw new InvalidArgumentException('Header value must be a string.');
				}
			}
		}
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * @return string[][]
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getBody(): string
	{
		return $this->body;
	}

}
