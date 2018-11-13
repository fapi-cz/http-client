<?php
declare(strict_types = 1);

namespace Fapi\HttpClient;

interface IHttpClient
{

	public function sendHttpRequest(HttpRequest $httpRequest): HttpResponse;

}
