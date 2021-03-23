<?php declare(strict_types = 1);

namespace Fapi\HttpClient\Rest;

use Fapi\HttpClient\HttpClientException;
use Fapi\HttpClient\HttpMethod;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpStatusCode;
use Fapi\HttpClient\IHttpClient;
use Fapi\HttpClient\Utils\Json;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function rtrim;

class RestClient
{

	private string $username;

	private string $password;

	private string $apiUrl;

	private IHttpClient $httpClient;

	public function __construct(string $username, string $password, string $apiUrl, IHttpClient $httpClient)
	{
		$this->username = $username;
		$this->password = $password;
		$this->apiUrl = rtrim($apiUrl, '/');
		$this->httpClient = $httpClient;
	}

	/**
	 * @param array<mixed> $parameters
	 * @return array<mixed>
	 */
	public function getResources(string $path, array $parameters = []): array
	{
		if ($parameters !== []) {
			$path .= '?' . $this->formatUrlParameters($parameters);
		}

		$httpResponse = $this->sendHttpRequest(HttpMethod::GET, $path);

		if ($httpResponse->getStatusCode() === HttpStatusCode::S200_OK) {
			return $this->getArrayOfArrayResponseData($httpResponse);
		}

		throw new InvalidStatusCodeException(
			'Expected return status code ' . HttpStatusCode::S200_OK . ', got ' . $httpResponse->getStatusCode() . '.',
		);
	}

	/**
	 * @param array<mixed> $parameters
	 */
	private function formatUrlParameters(array $parameters): string
	{
		return http_build_query($parameters, '', '&');
	}

	/**
	 * @param array<mixed>|null $data
	 */
	private function sendHttpRequest(string $method, string $path, ?array $data = null): ResponseInterface
	{
		$url = $this->apiUrl . $path;

		$options = [
			'auth' => [$this->username, $this->password],
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			],
		];

		if ($data !== null) {
			$options['json'] = $data;
		}

		try {
			$httpRequest = HttpRequest::from($url, $method, $options);

			return $this->httpClient->sendRequest($httpRequest);
		} catch (HttpClientException $e) {
			throw new RestClientException('Failed to send an HTTP request.', 0, $e);
		}
	}

	/**
	 * @return array<mixed>
	 */
	private function getArrayOfArrayResponseData(ResponseInterface $httpResponse): array
	{
		$responseData = $this->getArrayResponseData($httpResponse);

		foreach ($responseData as $value) {
			if (!is_array($value)) {
				throw new InvalidResponseBodyException('Response data is not an array of array.');
			}
		}

		return $responseData;
	}

	/**
	 * @return array<mixed>
	 */
	private function getArrayResponseData(ResponseInterface $httpResponse): array
	{
		$responseData = $this->getResponseData($httpResponse);

		if (!is_array($responseData)) {
			throw new InvalidResponseBodyException('Response data is not an array.');
		}

		return $responseData;
	}

	/**
	 * @return mixed
	 */
	private function getResponseData(ResponseInterface $httpResponse)
	{
		try {
			return Json::decode((string) $httpResponse->getBody(), Json::FORCE_ARRAY);
		} catch (Throwable $e) {
			throw new InvalidResponseBodyException('Response body is not a valid JSON.', 0, $e);
		}
	}

	/**
	 * @param array<mixed> $parameters
	 * @return array<mixed>|null
	 */
	public function getResource(string $path, int $id, array $parameters = []): ?array
	{
		$path .= '/' . $id;

		if ($parameters !== []) {
			$path .= '?' . $this->formatUrlParameters($parameters);
		}

		$httpResponse = $this->sendHttpRequest(HttpMethod::GET, $path);

		if ($httpResponse->getStatusCode() === HttpStatusCode::S200_OK) {
			return $this->getArrayResponseData($httpResponse);
		}

		if ($httpResponse->getStatusCode() === HttpStatusCode::S404_NOT_FOUND) {
			return null;
		}

		throw new InvalidStatusCodeException(
			'Expected return status code ' . HttpStatusCode::S200_OK . ', got ' . $httpResponse->getStatusCode() . '.',
		);
	}

	/**
	 * @param array<mixed> $parameters
	 * @return array<mixed>
	 */
	public function getSingularResource(string $path, array $parameters = []): array
	{
		if ($parameters !== []) {
			$path .= '?' . $this->formatUrlParameters($parameters);
		}

		$httpResponse = $this->sendHttpRequest(HttpMethod::GET, $path);

		if ($httpResponse->getStatusCode() === HttpStatusCode::S200_OK) {
			return $this->getArrayResponseData($httpResponse);
		}

		throw new InvalidStatusCodeException(
			'Expected return status code ' . HttpStatusCode::S200_OK . ', got ' . $httpResponse->getStatusCode() . '.',
		);
	}

	/**
	 * @param array<mixed> $data
	 * @return array<mixed>
	 */
	public function createResource(string $path, array $data): array
	{
		$httpResponse = $this->sendHttpRequest(HttpMethod::POST, $path, $data);

		if ($httpResponse->getStatusCode() === HttpStatusCode::S201_CREATED) {
			return $this->getArrayResponseData($httpResponse);
		}

		throw new InvalidStatusCodeException(
			'Expected return status code ' . HttpStatusCode::S201_CREATED . ', got ' . $httpResponse->getStatusCode() . '.',
		);
	}

	/**
	 * @param array<mixed> $data
	 * @return array<mixed>
	 */
	public function updateResource(string $path, int $id, array $data): array
	{
		$httpResponse = $this->sendHttpRequest(HttpMethod::PUT, $path . '/' . $id, $data);

		if ($httpResponse->getStatusCode() === HttpStatusCode::S200_OK) {
			return $this->getArrayResponseData($httpResponse);
		}

		throw new InvalidStatusCodeException(
			'Expected return status code ' . HttpStatusCode::S200_OK . ', got ' . $httpResponse->getStatusCode() . '.',
		);
	}

	public function deleteResource(string $path, int $id): void
	{
		$httpResponse = $this->sendHttpRequest(HttpMethod::DELETE, $path . '/' . $id);

		$inArray = !in_array(
			$httpResponse->getStatusCode(),
			[HttpStatusCode::S200_OK, HttpStatusCode::S204_NO_CONTENT],
			true,
		);

		if ($inArray) {
			throw new InvalidStatusCodeException(
				'Expected return status code [' . implode(
					', ',
					[
						HttpStatusCode::S200_OK,
						HttpStatusCode::S204_NO_CONTENT,
					],
				) . '], got ' . $httpResponse->getStatusCode() . '.',
			);
		}
	}

}
