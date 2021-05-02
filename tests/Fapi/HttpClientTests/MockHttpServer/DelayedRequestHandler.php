<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React;
use function sleep;

class DelayedRequestHandler
{

	public function handleRequest(ServerRequestInterface $request): ResponseInterface
	{
		sleep(2);

		return new React\Http\Response(200, ['Content-Type' => 'text/plain'], "OK\n");
	}

}
