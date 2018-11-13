<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

use Tester\Dumper;

class CapturingHttpClient implements IHttpClient
{

	/** @var IHttpClient */
	private $httpClient;

	/** @var HttpRequest[] */
	private $httpRequests = [];

	/** @var HttpResponse[] */
	private $httpResponses = [];

	public function __construct(IHttpClient $httpClient)
	{
		if (!\class_exists('Tester\Dumper')) {
			throw new InvalidStateException('Capturing HTTP client requires Nette Tester.');
		}

		$this->httpClient = $httpClient;
	}

	public function sendHttpRequest(HttpRequest $httpRequest): HttpResponse
	{
		$httpResponse = $this->httpClient->sendHttpRequest($httpRequest);
		$this->capture($httpRequest, $httpResponse);

		return $httpResponse;
	}

	private function capture(HttpRequest $httpRequest, HttpResponse $httpResponse)
	{
		$this->httpRequests[] = $httpRequest;
		$this->httpResponses[] = $httpResponse;
	}

	public function writeToPhpFile(string $fileName, string $className)
	{
		\preg_match('#^(?:(.*)\\\\)?([^\\\\]+)\z#', $className, $match);
		list(, $namespace, $className) = $match;

		$code = '<?php' . "\n";
		$code .= 'declare(strict_types = 1);' . "\n";
		$code .= "\n";

		if ($namespace) {
			$code .= 'namespace ' . $namespace . ';' . "\n";
			$code .= "\n";
		}

		$code .= 'use Fapi\HttpClient\HttpRequest;' . "\n";
		$code .= 'use Fapi\HttpClient\HttpResponse;' . "\n";
		$code .= 'use Fapi\HttpClient\MockHttpClient;' . "\n";
		$code .= "\n";
		$code .= 'final class ' . $className . ' extends MockHttpClient' . "\n";
		$code .= '{' . "\n";
		$code .= "\n";
		$code .= "\t" . 'public function __construct()' . "\n";
		$code .= "\t" . '{' . "\n";

		foreach ($this->httpRequests as $index => $httpRequest) {
			$httpResponse = $this->httpResponses[$index];

			$code .= "\t\t" . '$this->add(' . "\n";
			$code .= "\t\t\t" . 'new HttpRequest(' . "\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpRequest->getUrl(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpRequest->getMethod(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpRequest->getOptions(), "\t\t\t\t") . "\n";
			$code .= "\t\t\t" . '),' . "\n";
			$code .= "\t\t\t" . 'new HttpResponse(' . "\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpResponse->getStatusCode(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpResponse->getHeaders(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpResponse->getBody(), "\t\t\t\t") . "\n";
			$code .= "\t\t\t" . ')' . "\n";
			$code .= "\t\t" . ');' . "\n";
		}

		$code .= "\t" . '}' . "\n";
		$code .= "\n";
		$code .= '}' . "\n";

		\file_put_contents($fileName, $code);
	}

	/**
	 * @param mixed $value
	 */
	private function exportValue($value, string $indent = ''): string
	{
		$s = Dumper::toPhp($value);
		$s = \str_replace("\n", "\n" . $indent, $s);

		return $s;
	}

}
