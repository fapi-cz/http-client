<?php declare(strict_types = 1);

namespace Fapi\HttpClient;

use LogicException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tester\Dumper;
use function class_exists;
use function file_put_contents;
use function is_file;
use function preg_match;
use function spl_autoload;
use function str_replace;

class CapturingHttpClient implements IHttpClient
{

	/** @var array<RequestInterface> */
	private array $httpRequests = [];

	/** @var array<ResponseInterface> */
	private array $httpResponses = [];

	private int $closed = 0;

	public function __construct(private IHttpClient $httpClient, private string $file, private string $className)
	{
		if (!class_exists('Tester\Dumper')) {
			throw new InvalidStateException('Capturing HTTP client requires Nette Tester.');
		}

		if (is_file($file)) {
			require_once $file;
			spl_autoload($className);

			if (class_exists($className)) {
				/** @var IHttpClient $httpClient */
				$httpClient = new $className();

				$this->httpClient = $httpClient;
			}
		}
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$response = $this->httpClient->sendRequest($request);
		$this->capture($request, $response);

		return $response;
	}

	private function capture(RequestInterface $httpRequest, ResponseInterface $httpResponse): void
	{
		$this->httpRequests[] = $httpRequest;
		$this->httpResponses[] = $httpResponse;
	}

	public function close(): void
	{
		$this->closed++;

		if ($this->httpClient instanceof $this->className) {
			return;
		}

		$this->writeToPhpFile($this->file, $this->className);
	}

	private function writeToPhpFile(string $fileName, string $className): void
	{
		preg_match('#^(?:(.*)\\\\)?([^\\\\]+)\z#', $className, $match);
		$namespace = $match[1] ?? null;
		$className = $match[2] ?? null;

		$code = '<?php declare(strict_types = 1);' . "\n";
		$code .= "\n";

		if ((bool) $namespace) {
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
			$code .= "\t\t\t\t" . $this->exportValue($httpRequest->getMethod(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue((string) $httpRequest->getUri(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpRequest->getHeaders(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue((string) $httpRequest->getBody(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpRequest->getProtocolVersion(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t" . '),' . "\n";
			$code .= "\t\t\t" . 'new HttpResponse(' . "\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpResponse->getStatusCode(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue($httpResponse->getHeaders(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t\t" . $this->exportValue((string) $httpResponse->getBody(), "\t\t\t\t") . ",\n";
			$code .= "\t\t\t" . ')' . ",\n";
			$code .= "\t\t" . ');' . "\n";
		}

		$code .= "\t" . '}' . "\n";
		$code .= "\n";
		$code .= '}' . "\n";

		file_put_contents($fileName, $code);
	}

	private function exportValue(mixed $value, string $indent = ''): string
	{
		$s = Dumper::toPhp($value);
		$s = str_replace("\n", "\n" . $indent, $s);

		return $s;
	}

	public function __destruct()
	{
		if ($this->closed !== 1) {
			throw new LogicException(
				'Method: ' . self::class . '::closed Must be called exactly once. Called ' . $this->closed . ' times.',
			);
		}
	}

}
