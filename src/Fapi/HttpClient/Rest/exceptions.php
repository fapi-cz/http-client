<?php
declare(strict_types = 1);

namespace Fapi\HttpClient\Rest;

use RuntimeException;

/**
 * The exception that is thrown when the REST client fails to perform the requested operation.
 */
class RestClientException extends RuntimeException
{

}

/**
 * The exception that is thrown when an HTTP response with unexpected HTTP status code is received.
 */
class InvalidStatusCodeException extends RestClientException
{

}

/**
 * The exception that is thrown when an HTTP response with invalid response body is received.
 */
class InvalidResponseBodyException extends RestClientException
{

}
