<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

/**
 * The exception that is thrown when an HTTP client fails to make an HTTP request due to an exceeded timeout.
 */
class TimeLimitExceededException extends HttpClientException
{

}
