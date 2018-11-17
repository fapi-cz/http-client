<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

class HttpResponse extends \GuzzleHttp\Psr7\Response
{

	/**
	 * @inheritdoc
	 */
	public function __construct(int $status = 200, array $headers = [], $body = null, string $version = '1.1', string $reason = null)
	{
		if (!HttpStatusCode::isValid($status)) {
			throw new InvalidArgumentException('Parameter statusCode must be an HTTP status code.');
		}

		static::validateHeaders($headers);

		parent::__construct($status, $headers, $body, $version, $reason);
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

}
