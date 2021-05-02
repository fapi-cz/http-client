<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React;

class CheckCookieRequestHandler
{

	public function handleRequest(ServerRequestInterface $request): ResponseInterface
	{
		$headers = $request->getHeaders();

		if (!isset($headers['Cookie'])) {
			throw new InvalidHttpRequestException('Header Cookie is not present.');
		}

		if ($headers['Cookie'][0] !== 'sample-name=sample-value') {
			throw new InvalidHttpRequestException('Header Cookie has an unexpected value.');
		}

		return new React\Http\Message\Response(200, ['Content-Type' => 'text/plain'], "OK\n");
	}

}
