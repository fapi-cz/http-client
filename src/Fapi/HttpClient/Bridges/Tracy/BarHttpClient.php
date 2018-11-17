<?php
declare(strict_types = 1);

namespace Fapi\HttpClient\Bridges\Tracy;

use Fapi\HttpClient\HttpClientException;
use Fapi\HttpClient\IHttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tracy\Debugger;
use Tracy\IBarPanel;

final class BarHttpClient implements IHttpClient, IBarPanel
{

	/** @var int */
	private $maxRequests = 100;

	/** @var IHttpClient */
	private $httpClient;

	/** @var mixed[] */
	private $requests = [];

	/** @var int */
	private $count = 0;

	/** @var float */
	private $totalTime = 0.0;

	public function __construct(IHttpClient $httpClient)
	{
		$this->httpClient = $httpClient;
		Debugger::getBar()->addPanel($this);
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$this->count++;
		$startedAt = \microtime(true);

		try {
			$response = $this->httpClient->sendRequest($request);
		} catch (HttpClientException $e) {
			$this->captureFailed($request, $e, \microtime(true) - $startedAt);

			throw $e;
		}

		$this->captureSuccess($request, $response, \microtime(true) - $startedAt);

		return $response;
	}

	private function captureFailed(RequestInterface $httpRequest, \Throwable $exception, float $time)
	{
		if ($this->count >= $this->maxRequests) {
			return;
		}

		$this->totalTime += $time;

		$options = $httpRequest->getHeaders();
		unset($options['auth']);

		$this->requests[] = [
			'status' => 'failed',
			'request' => [
				'url' => (string) $httpRequest->getUri(),
				'method' => $httpRequest->getMethod(),
				'options' => $options,
			],
			'exception' => $exception->getMessage(),
			'time' => $time,
		];
	}

	private function captureSuccess(RequestInterface $httpRequest, ResponseInterface $httpResponse, float $time)
	{
		if ($this->count >= $this->maxRequests) {
			return;
		}

		$this->totalTime += $time;
		$options = $httpRequest->getHeaders();
		unset($options['auth']);

		$this->requests[] = [
			'status' => 'success',
			'request' => [
				'url' => (string) $httpRequest->getUri(),
				'method' => $httpRequest->getMethod(),
				'options' => $options,
			],
			'response' => [
				'status_code' => $httpResponse->getStatusCode(),
				'headers' => $httpResponse->getHeaders(),
				'body' => (string) $httpResponse->getBody(),
			],
			'time' => $time,
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getTab()
	{
		// @codingStandardsIgnoreStart
		$count = $this->count;
		$totalTime = $this->totalTime;
		// @codingStandardsIgnoreEnd

		\ob_start();
		require __DIR__ . '/RequestPanel.tab.phtml';

		return \ob_get_clean();
	}

	/**
	 * @inheritdoc
	 */
	public function getPanel()
	{
		// @codingStandardsIgnoreStart
		$count = $this->count;
		$totalTime = $this->totalTime;
		$requests = $this->requests;
		// @codingStandardsIgnoreEnd

		\ob_start();
		require __DIR__ . '/RequestPanel.panel.phtml';

		return \ob_get_clean();
	}

}
