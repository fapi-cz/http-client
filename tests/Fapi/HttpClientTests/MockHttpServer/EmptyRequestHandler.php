<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React;

class EmptyRequestHandler
{

	public function handleRequest(ServerRequestInterface $request): ResponseInterface
	{
		return new React\Http\Message\Response(200, ['Content-Type' => 'text/plain'], '');
	}

}
