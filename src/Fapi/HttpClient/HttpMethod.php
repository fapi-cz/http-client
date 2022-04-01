<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use function in_array;

class HttpMethod
{

	public const HEAD = 'HEAD';

	public const GET = 'GET';

	public const POST = 'POST';

	public const PUT = 'PUT';

	public const DELETE = 'DELETE';

	/**
	 * @return array<string>
	 */
	public static function getAll(): array
	{
		return [
			self::HEAD,
			self::GET,
			self::POST,
			self::PUT,
			self::DELETE,
		];
	}

	public static function isValid(string $value): bool
	{
		return in_array($value, static::getAll(), true);
	}

}
