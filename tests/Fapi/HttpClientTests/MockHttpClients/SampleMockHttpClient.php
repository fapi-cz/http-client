<?php
declare(strict_types = 1);

namespace Fapi\HttpClientTests\MockHttpClients;

use Fapi\HttpClient\HttpRequest;
use Fapi\HttpClient\HttpResponse;
use Fapi\HttpClient\MockHttpClient;

final class SampleMockHttpClient extends MockHttpClient
{

	public function __construct()
	{
		$this->add(
			new HttpRequest(
				'http://localhost/',
				'GET',
				['headers' => ['User-Agent' => 'Nette Tester']]
			),
			new HttpResponse(
				200,
				['Content-Type' => ['text/plain']],
				"It works!\n"
			)
		);
	}

}
