<?php
declare(strict_types = 1);

namespace Fapi\HttpClient\Bidges\NetteDI;

use Fapi\HttpClient\Bridges\Tracy\BarHttpClient;
use Fapi\HttpClient\Bridges\Tracy\TracyToPsrLogger;
use Fapi\HttpClient\CurlHttpClient;
use Fapi\HttpClient\GuzzleHttpClient;
use Fapi\HttpClient\IHttpClient;
use Fapi\HttpClient\InvalidStateException;
use Fapi\HttpClient\LoggingHttpClient;
use Nette\DI\CompilerExtension;

class HttpClientExtension extends CompilerExtension
{

	/** @var mixed[] */
	public $defaults = [
		'type' => 'guzzle',
		'logging' => false,
		'bar' => false,
	];

	/** @var string[] */
	private $typeClasses = [
		'curl' => CurlHttpClient::class,
		'guzzle' => GuzzleHttpClient::class,
	];

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		if (!isset($this->typeClasses[$config['type']])) {
			throw new InvalidStateException("Type '" . $config['type'] . "' is not supported.'");
		}

		$httpClientClass = $this->typeClasses[$config['type']];

		if ($config['bar']) {
			$container->addDefinition($this->prefix('barHttpClient'))
				->setType($httpClientClass)
				->setAutowired(false);

			$container->addDefinition($this->prefix('httpClient'))
				->setType(IHttpClient::class)
				->setFactory(BarHttpClient::class, [
					$this->prefix('@barHttpClient'),
				]);

			return;
		}

		if ($config['logging']) {
			$container->addDefinition($this->prefix('loggingHttpClient'))
				->setType($httpClientClass)
				->setAutowired(false);

			$container->addDefinition($this->prefix('tracyToPsrLogger'))
				->setType(TracyToPsrLogger::class)
				->setAutowired(false);

			$container->addDefinition($this->prefix('httpClient'))
				->setType(IHttpClient::class)
				->setFactory(LoggingHttpClient::class, [
					$this->prefix('@loggingHttpClient'),
				]);

			return;
		}

		$container->addDefinition($this->prefix('httpClient'))
			->setType(IHttpClient::class)
			->setFactory($httpClientClass);
	}

}
