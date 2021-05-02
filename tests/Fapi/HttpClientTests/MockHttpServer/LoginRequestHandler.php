<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React;

class LoginRequestHandler
{

	public function handleRequest(
		ServerRequestInterface $request
	): ResponseInterface
	{
		$method = $request->getMethod();
		$headers = $request->getHeaders();

		if ($method !== 'POST') {
			throw new InvalidHttpRequestException('Unexpected HTTP method.');
		}

		if (!isset($headers['Content-Type'])) {
			throw new InvalidHttpRequestException('Header Content-Type is not present.');
		}

		if ($headers['Content-Type'][0] !== 'application/x-www-form-urlencoded') {
			throw new InvalidHttpRequestException('Header Content-Type has an unexpected value.');
		}

		if (!isset($headers['X-Foo'])) {
			throw new InvalidHttpRequestException('Header X-Foo is not present.');
		}

		if ($headers['X-Foo'] !== ['Bar', 'Baz']) {
			throw new InvalidHttpRequestException('Header X-Foo has an unexpected value.');
		}

		if (!isset($headers['Content-Length'])) {
			throw new InvalidHttpRequestException('Header Content-Length is not present.');
		}

		if ($headers['Content-Length'][0] !== '27') {
			throw new InvalidHttpRequestException('Header Content-Length has an unexpected value.');
		}

		/** @var array<mixed> $data */
		$data = $request->getParsedBody();

		if (!(isset($data['username'], $data['password'])
			&& $data['username'] === 'admin'
			&& $data['password'] === 'xxx'
		)) {
			throw new InvalidHttpRequestException('Response body is not valid.');
		}

		return new React\Http\Response(200, ['Content-Type' => 'text/plain'], "OK\n");
	}

}
