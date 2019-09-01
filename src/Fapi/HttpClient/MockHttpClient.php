<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockHttpClient implements IHttpClient
{

	/** @var RequestInterface[] */
	private $requests = [];

	/** @var ResponseInterface[] */
	private $responses = [];

	public function add(RequestInterface $request, ResponseInterface $response)
	{
		$this->requests[] = $request;
		$this->responses[] = $response;
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		if (!isset($this->requests[0])) {
			throw new InvalidArgumentException('Invalid HTTP request. No more requests found.');
		}

		$expectedRequest = $this->requests[0];
		$this->assertHttpRequestUrl($expectedRequest, $request);
		$this->assertHttpRequestMethod($expectedRequest, $request);
		$this->assertHttpRequestOptions($expectedRequest, $request);
		$this->assertHttpRequestBody($expectedRequest, $request);

		\array_shift($this->requests);
		/** @var HttpResponse $response */
		$response = \array_shift($this->responses);

		return $response;
	}

	public function wereAllHttpRequestsSent(): bool
	{
		return !$this->requests;
	}

	private function assertHttpRequestUrl(RequestInterface $expected, RequestInterface $actual)
	{
		if ((string) $expected->getUri() === (string) $actual->getUri()) {
			return;
		}

		$expectedUrl = $this->formatUrl((string) $expected->getUri());
		$actualUrl = $this->formatUrl((string) $actual->getUri());

		throw new InvalidArgumentException(
			'Invalid HTTP request. Url not matched. Expected "'
			. $expectedUrl . '" got "' . $actualUrl . '".'
		);
	}

	private function assertHttpRequestMethod(RequestInterface $expected, RequestInterface $actual)
	{
		if ($expected->getMethod() === $actual->getMethod()) {
			return;
		}

		throw new InvalidArgumentException(
			'Invalid HTTP request. Method not matched. Expected "'
			. $expected->getMethod() . '" got "' . $actual->getMethod() . '".'
		);
	}

	private function assertHttpRequestOptions(RequestInterface $expected, RequestInterface $actual)
	{
		if ($expected->getHeaders() === $actual->getHeaders()) {
			return;
		}

		throw new InvalidArgumentException(
			'Invalid HTTP request. Options not matched.'
		);
	}

	private function assertHttpRequestBody(RequestInterface $expected, RequestInterface $actual)
	{
		if ((string) $expected->getBody() === (string) $actual->getBody()) {
			return;
		}

		throw new InvalidArgumentException(
			'Invalid HTTP request. Body not matched.'
		);
	}

	private function formatUrl(string $url): string
	{
		if (\strlen($url) > 250) {
			return \substr($url, 200) . '...';
		}

		return $url;
	}

}
