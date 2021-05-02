<?php declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React;
use React\Http\Response;
use function fflush;
use function fwrite;
use const STDOUT;

class MockHttpServer
{

	/** @var React\EventLoop\LoopInterface */
	private $eventLoop;

	/** @var React\Socket\Server */
	private $socketServer;

	/** @var React\Http\Server */
	private $httpServer;

	/** @var ApiRequestHandler */
	private $apiRequestHandler;

	/** @var AssignCookieRequestHandler */
	private $assignCookieRequestHandler;

	/** @var CheckCookieRequestHandler */
	private $checkCookieRequestHandler;

	/** @var DelayedRequestHandler */
	private $delayedRequestHandler;

	/** @var EmptyRequestHandler */
	private $emptyRequestHandler;

	/** @var LoginRequestHandler */
	private $loginRequestHandler;

	public function run(): void
	{
		$this->eventLoop = React\EventLoop\Factory::create();
		$this->socketServer = new React\Socket\Server('1337', $this->eventLoop);
		$this->httpServer = new React\Http\Server([$this, 'handleRequest']);

		$this->apiRequestHandler = new ApiRequestHandler();
		$this->assignCookieRequestHandler = new AssignCookieRequestHandler();
		$this->checkCookieRequestHandler = new CheckCookieRequestHandler();
		$this->delayedRequestHandler = new DelayedRequestHandler();
		$this->emptyRequestHandler = new EmptyRequestHandler();
		$this->loginRequestHandler = new LoginRequestHandler();

		$this->eventLoop->addTimer(0.001, [$this, 'startServer']);
		$this->eventLoop->addTimer(3.0, [$this, 'handleTimeout']);
		$this->eventLoop->run();
	}

	public function startServer(): void
	{
		$this->httpServer->listen($this->socketServer);

		fwrite(STDOUT, "Server running at http://127.0.0.1:1337/\n");
		fflush(STDOUT);
	}

	public function handleRequest(ServerRequestInterface $request): ResponseInterface
	{
		try {
			return $this->processRequest($request);
		} catch (InvalidHttpRequestException $e) {
			return new Response(400, ['Content-Type' => 'text/plain'], $e->getMessage());
		}
	}

	private function processRequest(
		ServerRequestInterface $request
	): ResponseInterface
	{
		$path = $request->getUri()->getPath();

		if ($path === '/api') {
			return $this->apiRequestHandler->handleRequest($request);
		}

		if ($path === '/assign-cookie') {
			return $this->assignCookieRequestHandler->handleRequest($request);
		}

		if ($path === '/check-cookie') {
			return $this->checkCookieRequestHandler->handleRequest($request);
		}

		if ($path === '/delayed') {
			return $this->delayedRequestHandler->handleRequest($request);
		}

		if ($path === '/empty') {
			return $this->emptyRequestHandler->handleRequest($request);
		}

		if ($path === '/login') {
			return $this->loginRequestHandler->handleRequest($request);
		}

		throw new InvalidHttpRequestException('Unexpected path.');
	}

	public function handleTimeout(): void
	{
		$this->socketServer->close();
		$this->eventLoop->stop();

		fwrite(STDOUT, "Time limit exceeded\n");
		fflush(STDOUT);
	}

}
