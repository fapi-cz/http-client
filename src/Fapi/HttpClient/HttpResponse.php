<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\StreamInterface;
use function is_array;
use function is_string;

class HttpResponse extends Response
{

	/**
	 * @param array<mixed> $headers
	 */
	public function __construct(
		int $status = 200,
		array $headers = [],
		StreamInterface|string|null $body = null,
		string $version = '1.1',
		string|null $reason = null,
	)
	{
		if (!HttpStatusCode::isValid($status)) {
			throw new InvalidArgumentException('Parameter statusCode must be an HTTP status code.');
		}

		self::validateHeaders($headers);

		parent::__construct($status, $headers, $body, $version, $reason);
	}

	/**
	 * @param array<string|array<mixed>> $headers
	 */
	private static function validateHeaders(array $headers): void
	{
		foreach ($headers as $values) {
			if (!is_array($values)) {
				throw new InvalidArgumentException('Header values must be an array.');
			}

			foreach ($values as $value) {
				if (!is_string($value)) {
					throw new InvalidArgumentException('Header value must be a string.');
				}
			}
		}
	}

}
