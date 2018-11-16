<?php
declare(strict_types = 1);

namespace Fapi\HttpClient\Bridges\Tracy;

use Fapi\HttpClient\HttpClientException;
use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Fapi\HttpClient\IHttpClient;
use Tracy\Debugger;

final class BarHttpClient implements IHttpClient, \Tracy\IBarPanel
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

	public function sendHttpRequest(HttpRequest $httpRequest): HttpResponse
	{
		$this->count++;
		$startedAt = \microtime(true);

		try {
			$httpResponse = $this->httpClient->sendHttpRequest($httpRequest);
		} catch (HttpClientException $e) {
			$this->captureFailed($httpRequest, $e, \microtime(true) - $startedAt);

			throw $e;
		}

		$this->captureSuccess($httpRequest, $httpResponse, \microtime(true) - $startedAt);

		return $httpResponse;
	}

	private function captureFailed(HttpRequest $httpRequest, \Throwable $exception, float $time)
	{
		if ($this->count >= $this->maxRequests) {
			return;
		}

		$this->totalTime += $time;

		$options = $httpRequest->getOptions();
		unset($options['auth']);

		$this->requests[] = [
			'status' => 'failed',
			'request' => [
				'url' => $httpRequest->getUrl(),
				'method' => $httpRequest->getMethod(),
				'options' => $options,
			],
			'exception' => $exception->getMessage(),
			'time' => $time,
		];
	}

	private function captureSuccess(HttpRequest $httpRequest, HttpResponse $httpResponse, float $time)
	{
		if ($this->count >= $this->maxRequests) {
			return;
		}

		$this->totalTime += $time;
		$options = $httpRequest->getOptions();
		unset($options['auth']);

		$this->requests[] = [
			'status' => 'success',
			'request' => [
				'url' => $httpRequest->getUrl(),
				'method' => $httpRequest->getMethod(),
				'options' => $options,
			],
			'response' => [
				'status_code' => $httpResponse->getStatusCode(),
				'headers' => $httpResponse->getHeaders(),
				'body' => $httpResponse->getBody(),
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
