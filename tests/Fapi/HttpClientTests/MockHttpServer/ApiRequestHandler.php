<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpServer;

use Psr\Http\Message\ServerRequestInterface;
use React;

class ApiRequestHandler
{

	public function handleRequest(ServerRequestInterface $request): React\Http\Response
	{
		$method = $request->getMethod();
		$headers = $request->getHeaders();

		if ($method !== 'POST') {
			throw new InvalidHttpRequestException('Unexpected HTTP method.');
		}

		if (!isset($headers['Content-Type'])) {
			throw new InvalidHttpRequestException('Header Content-Type is not present.');
		}

		if ($headers['Content-Type'][0] !== 'application/json') {
			throw new InvalidHttpRequestException('Header Content-Type has an unexpected value.');
		}

		if (!isset($headers['User-Agent'])) {
			throw new InvalidHttpRequestException('Header User-Agent is not present.');
		}

		if ($headers['User-Agent'][0] !== 'ApiClient/1.0') {
			throw new InvalidHttpRequestException('Header User-Agent has an unexpected value.');
		}

		if (!isset($headers['Authorization'])) {
			throw new InvalidHttpRequestException('Header Authorization is not present.');
		}

		if ($headers['Authorization'][0] !== 'Basic ' . \base64_encode('admin:xxx')) {
			throw new InvalidHttpRequestException('Header Authorization has an unexpected value.');
		}

		/** @var int $size */
		$size = $request->getBody()->getSize();
		$data = \json_decode($request->getBody()->read($size), true);

		if (!\is_array($data) || $data['foo'] !== 'bar') {
			throw new InvalidHttpRequestException('Response body is not valid. ' . \json_encode($data));
		}

		return new React\Http\Response(200, ['Content-Type' => 'text/plain'], "OK\n");
	}

}
