<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use InvalidArgumentException;

/**
 * The exception that is thrown when the value of an argument is
 * outside the allowable range of values as defined by the invoked method.
 */
class ArgumentOutOfRangeException extends InvalidArgumentException
{

}
