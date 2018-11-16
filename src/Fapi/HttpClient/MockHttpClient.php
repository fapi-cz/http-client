<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

class MockHttpClient implements IHttpClient
{

	/** @var HttpRequest[] */
	private $httpRequests = [];

	/** @var HttpResponse[] */
	private $httpResponses = [];

	public function add(HttpRequest $httpRequest, HttpResponse $httpResponse)
	{
		$this->httpRequests[] = $httpRequest;
		$this->httpResponses[] = $httpResponse;
	}

	public function sendHttpRequest(HttpRequest $httpRequest): HttpResponse
	{
		if (!isset($this->httpRequests[0])) {
			throw new InvalidArgumentException('Invalid HTTP request. No more requests found.');
		}

		$expectedHttpRequest = $this->httpRequests[0];
		$this->assertHttpRequestUrl($expectedHttpRequest, $httpRequest);
		$this->assertHttpRequestMethod($expectedHttpRequest, $httpRequest);
		$this->assertHttpRequestOptions($expectedHttpRequest, $httpRequest);

		\array_shift($this->httpRequests);
		/** @var HttpResponse $response */
		$response = \array_shift($this->httpResponses);

		return $response;
	}

	public function wereAllHttpRequestsSent(): bool
	{
		return !$this->httpRequests;
	}

	private function assertHttpRequestUrl(HttpRequest $expected, HttpRequest $actual)
	{
		if ($expected->getUrl() === $actual->getUrl()) {
			return;
		}

		$expectedUrl = $this->formatUrl($expected->getUrl());
		$actualUrl = $this->formatUrl($actual->getUrl());

		throw new InvalidArgumentException(
			'Invalid HTTP request. Url not matched. Expected "'
			. $expectedUrl . '" got "' . $actualUrl . '".'
		);
	}

	private function assertHttpRequestMethod(HttpRequest $expected, HttpRequest $actual)
	{
		if ($expected->getMethod() === $actual->getMethod()) {
			return;
		}

		throw new InvalidArgumentException(
			'Invalid HTTP request. Method not matched. Expected "'
			. $expected->getMethod() . '" got "' . $actual->getMethod() . '".'
		);
	}

	private function assertHttpRequestOptions(HttpRequest $expected, HttpRequest $actual)
	{
		if ($expected->getOptions() === $actual->getOptions()) {
			return;
		}

		throw new InvalidArgumentException(
			'Invalid HTTP request. Options not matched.'
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
