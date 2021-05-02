<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

interface ILoggingFormatter
{

	public function formatSuccessful(
		RequestInterface $request,
		ResponseInterface $response,
		float $elapsedTime
	): string;

	public function formatFailed(RequestInterface $request, Throwable $exception, float $elapsedTime): string;

}
