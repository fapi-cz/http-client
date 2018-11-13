<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

class HttpMethod
{

	const HEAD = 'HEAD';
	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';

	/**
	 * @return string[]
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
		return \in_array($value, static::getAll(), true);
	}

}
