<?php declare(strict_types = 1);

namespace Fapi\HttpClient\Utils;

use Throwable;
use function defined;
use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function str_replace;
use function strcasecmp;
use const JSON_BIGINT_AS_STRING;
use const JSON_ERROR_CTRL_CHAR;
use const JSON_ERROR_DEPTH;
use const JSON_ERROR_STATE_MISMATCH;
use const JSON_ERROR_SYNTAX;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_INT_SIZE;
use const PHP_VERSION_ID;

/**
 * JSON encoder and decoder.
 *
 * This solution is mostly based on Nette Framework (c) David Grudl (http://davidgrudl.com), new BSD license
 */
class Json
{

	public const FORCE_ARRAY = 1;

	public const PRETTY = 2;

	/** @var array<string> */
	private static array $messages = [
		JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
		JSON_ERROR_STATE_MISMATCH => 'Syntax error, malformed JSON',
		JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
		JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
		5 /*JSON_ERROR_UTF8*/ => 'Invalid UTF-8 sequence', // exists since 5.3.3, but is returned since 5.3.1
	];

	/**
	 * Returns the JSON representation of a value.
	 *
	 * @throws JsonException
	 * @throws Throwable
	 */
	public static function encode(mixed $value, int $options = 0): string
	{
		$flags = PHP_VERSION_ID >= 50400
			? (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES |
				(
				(bool) ($options & self::PRETTY)
					? JSON_PRETTY_PRINT
					: 0
				)
			)
			: 0;

		if (PHP_VERSION_ID < 50500) {
			$json = Callback::invokeSafe(
				'json_encode',
				[$value, $flags],
				static function ($message): void {
					// needed to receive 'recursion detected' error
					throw new JsonException($message);
				},
			);
		} else {
			$json = json_encode($value, $flags);
		}

		$error = json_last_error();

		if ((bool) $error) {
			$message = self::$messages[$error] ?? (PHP_VERSION_ID >= 50500 ? json_last_error_msg() : 'Unknown error');

			throw new JsonException($message, $error);
		}

		$json = str_replace(["\xe2\x80\xa8", "\xe2\x80\xa9"], ['\u2028', '\u2029'], $json);

		return $json;
	}

	/**
	 * Decodes a JSON string.
	 *
	 * @throws JsonException
	 */
	public static function decode(string $json, int $options = 0): mixed
	{
		if (!(bool) preg_match('##u', $json)) {
			throw new JsonException('Invalid UTF-8 sequence', 5); // workaround for PHP < 5.3.3 & PECL JSON-C
		}

		$forceArray = (bool) ($options & self::FORCE_ARRAY);

		if (
			!$forceArray
			&& (bool) preg_match('#(?<=[^\\\\]")\\\\u0000(?:[^"\\\\]|\\\\.)*+"\s*+:#', $json)
		) { // workaround for json_decode fatal error when object key starts with \u0000
			throw new JsonException(self::$messages[JSON_ERROR_CTRL_CHAR]);
		}

		$args = [$json, $forceArray, 512];

		if (
			PHP_VERSION_ID >= 50400
			&& !(
				defined('JSON_C_VERSION')
				&& PHP_INT_SIZE > 4
			)
		) { // not implemented in PECL JSON-C 1.3.2 for 64bit systems
			$args[] = JSON_BIGINT_AS_STRING;
		}

		$value = json_decode(...$args);

		if (
			$value === null
			&& $json !== ''
			&& (bool) strcasecmp($json, 'null')
		) { // '' is not clearing json_last_error
			$error = json_last_error();

			throw new JsonException(self::$messages[$error] ?? 'Unknown error', $error);
		}

		return $value;
	}

}
