<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React;

class AssignCookieRequestHandler
{

	public function handleRequest(ServerRequestInterface $request): ResponseInterface
	{
		return new React\Http\Response(200, [
			'Content-Type' => 'text/plain',
			'Set-Cookie' => 'sample-name=sample-value; path=/',
		], "OK\n");
	}

}
