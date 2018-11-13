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
		if (!isset($this->httpRequests[0]) || !$this->matchHttpRequest($this->httpRequests[0], $httpRequest)) {
			throw new InvalidArgumentException('Invalid HTTP request.');
		}

		\array_shift($this->httpRequests);
		/** @var HttpResponse $response */
		$response = \array_shift($this->httpResponses);

		return $response;
	}

	public function wereAllHttpRequestsSent(): bool
	{
		return !$this->httpRequests;
	}

	private function matchHttpRequest(HttpRequest $expected, HttpRequest $actual): bool
	{
		return $expected->getUrl() === $actual->getUrl()
			&& $expected->getMethod() === $actual->getMethod()
			&& $expected->getOptions() === $actual->getOptions();
	}

}
