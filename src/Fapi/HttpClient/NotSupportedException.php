<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use LogicException;

/**
 * The exception that is thrown when an invoked method is not supported. For scenarios where
 * it is sometimes possible to perform the requested operation, see InvalidStateException.
 */
class NotSupportedException extends LogicException
{

}
