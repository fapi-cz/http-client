<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpServer;

use React;

class MockHttpServerRunner
{

	/** @var callable[]  function (MockHttpServerRunner $sender); Occurs when the mock HTTP server is started */
	public $onStarted = [];

	/** @var string */
	private $serverRunningMessage = "Server running at http://127.0.0.1:1337/\n";

	/** @var React\EventLoop\LoopInterface */
	private $eventLoop;

	/** @var React\ChildProcess\Process */
	private $process;

	/** @var string */
	private $stdoutBuffer;

	public function run()
	{
		$this->eventLoop = React\EventLoop\Factory::create();
		$this->process = new React\ChildProcess\Process(\escapeshellarg(__DIR__ . '/bin/run-mock-http-server'));
		$this->eventLoop->addTimer(0.0001, [$this, 'startChildProcess']);
		$this->eventLoop->addTimer(3.0, [$this, 'handleTimeout']);
		$this->eventLoop->run();
	}

	public function stop()
	{
		$this->process->close();
		$this->process->terminate();
		$this->eventLoop->stop();
	}

	public function startChildProcess()
	{
		$this->process->start($this->eventLoop);

		$this->stdoutBuffer = '';

		/** @var \React\Stream\ReadableResourceStream $stdoutStream */
		$stdoutStream = $this->process->stdout;
		$stdoutStream->on('data', [$this, 'handleStdoutData']);
	}

	public function handleStdoutData(string $data)
	{
		$this->stdoutBuffer .= $data;

		if (\strlen($this->stdoutBuffer) < \strlen($this->serverRunningMessage)) {
			return;
		}

		if ($this->stdoutBuffer !== $this->serverRunningMessage) {
			throw new HttpServerException("Unexpected stdout data '$this->stdoutBuffer'.");
		}

		$this->fireStarted();
	}

	public function handleTimeout()
	{
		$this->process->close();

		throw new HttpServerException('Time limit exceeded');
	}

	private function fireStarted()
	{
		foreach ($this->onStarted as $callback) {
			\call_user_func($callback, $this);
		}
	}

}

class HttpServerException extends \RuntimeException
{

}
