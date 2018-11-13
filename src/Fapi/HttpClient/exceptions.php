<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use LogicException;
use RuntimeException;

/**
 * The exception that is thrown when the value of an argument is
 * outside the allowable range of values as defined by the invoked method.
 */
class ArgumentOutOfRangeException extends \InvalidArgumentException
{

}

/**
 * The exception that is thrown when a method call is invalid for the object's
 * current state, method has been invoked at an illegal or inappropriate time.
 */
class InvalidStateException extends RuntimeException
{

}

/**
 * The exception that is thrown when a requested method or operation is not implemented.
 */
class NotImplementedException extends LogicException
{

}

/**
 * The exception that is thrown when an invoked method is not supported. For scenarios where
 * it is sometimes possible to perform the requested operation, see InvalidStateException.
 */
class NotSupportedException extends LogicException
{

}

/**
 * The exception that is thrown when a requested method or operation is deprecated.
 */
class DeprecatedException extends NotSupportedException
{

}

/**
 * The exception that is thrown when an argument does not match with the expected value.
 */
class InvalidArgumentException extends \InvalidArgumentException
{

}

/**
 * The exception that is thrown when an illegal index was requested.
 */
class OutOfRangeException extends \OutOfRangeException
{

}

/**
 * The exception that is thrown when a value (typically returned by function) does not match with the expected value.
 */
class UnexpectedValueException extends \UnexpectedValueException
{

}

/**
 * The exception that is thrown when static class is instantiated.
 */
class StaticClassException extends LogicException
{

}

/**
 * The exception that is thrown when an HTTP client fails to make an HTTP request.
 */
class HttpClientException extends RuntimeException
{

}

/**
 * The exception that is thrown when an HTTP client fails to make an HTTP request due to an exceeded timeout.
 */
class TimeLimitExceededException extends HttpClientException
{

}

/**
 * The exception that is thrown when the maximum number of redirections is exceeded.
 */
class TooManyRedirectsException extends HttpClientException
{

}
